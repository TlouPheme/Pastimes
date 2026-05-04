<?php
global $conn;
include 'includes/db.php';
include 'includes/auth.php';

require_login('login.php');

$user = current_user();
$message = '';
$messageType = '';

$stmt = $conn->prepare('SELECT products.*
    FROM cart_items
    INNER JOIN products ON products.id = cart_items.product_id
    WHERE cart_items.user_id = ? AND products.status = "available"
    ORDER BY cart_items.created_at DESC');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$cartTotal = array_sum(array_map(fn ($item) => (float)$item['price'], $cartItems));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $deliveryMethod = $_POST['delivery_method'] ?? 'pickup';
    $address = trim($_POST['address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!verify_csrf()) {
        $message = 'Security check failed. Please try again.';
        $messageType = 'error';
    } elseif (!$cartItems) {
        $message = 'Your cart has no available items.';
        $messageType = 'error';
    } elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || !in_array($deliveryMethod, ['pickup', 'delivery'], true)) {
        $message = 'Please complete your checkout details.';
        $messageType = 'error';
    } elseif ($deliveryMethod === 'delivery' && $address === '') {
        $message = 'Please enter a delivery address.';
        $messageType = 'error';
    } else {
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare('INSERT INTO orders (user_id, name, email, phone, delivery_method, address, notes, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('issssssd', $user['id'], $name, $email, $phone, $deliveryMethod, $address, $notes, $cartTotal);
            $stmt->execute();
            $orderId = $stmt->insert_id;

            foreach ($cartItems as $item) {
                $stmt = $conn->prepare('INSERT INTO order_items (order_id, product_id, product_name, price, image) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('iisds', $orderId, $item['id'], $item['name'], $item['price'], $item['image']);
                $stmt->execute();

                $stmt = $conn->prepare('UPDATE products SET status = "sold" WHERE id = ?');
                $stmt->bind_param('i', $item['id']);
                $stmt->execute();
            }

            $stmt = $conn->prepare('DELETE FROM cart_items WHERE user_id = ?');
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();

            $conn->commit();
            header('Location: order_success.php?id=' . $orderId);
            exit;
        } catch (Throwable $exception) {
            $conn->rollback();
            $message = 'Checkout could not be completed. Please try again.';
            $messageType = 'error';
        }
    }
}

include 'includes/header.php';
?>

<main>
    <section class="admin-page">
        <div class="admin-intro">
            <p class="eyebrow">Checkout</p>
            <h1>Confirm order</h1>
            <p>Complete your contact details. Items are marked sold after checkout.</p>
            <a class="button secondary" href="cart.php">Back to Cart</a>
        </div>

        <form class="product-form" method="POST">
            <?php echo csrf_field(); ?>
            <?php if ($message): ?>
                <div class="form-message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="summary-line">
                <span>Total</span>
                <strong>R<?php echo number_format($cartTotal, 2); ?></strong>
            </div>

            <div class="form-grid">
                <label>
                    <span>Name</span>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? $user['name']); ?>" required>
                </label>
                <label>
                    <span>Email</span>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? $user['email']); ?>" required>
                </label>
            </div>

            <label>
                <span>Phone</span>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
            </label>

            <label>
                <span>Fulfilment</span>
                <select name="delivery_method" required>
                    <option value="pickup" <?php echo ($_POST['delivery_method'] ?? 'pickup') === 'pickup' ? 'selected' : ''; ?>>Pickup</option>
                    <option value="delivery" <?php echo ($_POST['delivery_method'] ?? '') === 'delivery' ? 'selected' : ''; ?>>Delivery</option>
                </select>
            </label>

            <label>
                <span>Delivery address</span>
                <textarea name="address" rows="3" placeholder="Only required for delivery"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
            </label>

            <label>
                <span>Order notes</span>
                <textarea name="notes" rows="3" placeholder="Pickup time, sizing question, or delivery notes..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
            </label>

            <button type="submit" <?php echo !$cartItems ? 'disabled' : ''; ?>>Place Order</button>
        </form>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
