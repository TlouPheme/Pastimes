<?php
global $conn;
include 'includes/db.php';
include 'includes/auth.php';

require_login('login.php');

$user = current_user();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

    if (!verify_csrf()) {
        $message = 'Security check failed. Please try again.';
        $messageType = 'error';
    } elseif ($productId) {
        $stmt = $conn->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?');
        $stmt->bind_param('ii', $user['id'], $productId);

        if ($stmt->execute()) {
            $message = 'Item removed from cart.';
            $messageType = 'success';
        }
    }
}

$stmt = $conn->prepare('SELECT products.*, categories.name AS category_name
    FROM cart_items
    INNER JOIN products ON products.id = cart_items.product_id
    LEFT JOIN categories ON categories.id = products.category_id
    WHERE cart_items.user_id = ?
    ORDER BY cart_items.created_at DESC');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$cartTotal = array_sum(array_map(fn ($item) => (float)$item['price'], $cartItems));
$availableItems = array_filter($cartItems, fn ($item) => ($item['status'] ?? 'available') === 'available');

include 'includes/header.php';
?>

<main>
    <section class="admin-list-page">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Checkout</p>
                <h1>Your cart</h1>
            </div>
            <a class="button secondary" href="index.php#products">Back to Shop</a>
        </div>

        <?php if ($message): ?>
            <div class="form-message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($cartItems): ?>
            <div class="cart-layout">
                <div class="product-table">
                    <?php foreach ($cartItems as $product): ?>
                        <?php
                        $image = $product['image'] ?? '';
                        $imagePath = 'assets/images/' . $image;
                        $hasImage = $image !== '' && file_exists($imagePath);
                        ?>
                        <article class="product-row">
                            <div class="row-image">
                                <?php if ($hasImage): ?>
                                    <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <span>No image</span>
                                <?php endif; ?>
                            </div>
                            <div class="row-copy">
                                <div class="pill-row">
                                    <span class="category-pill"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                                    <span class="status-pill <?php echo htmlspecialchars($product['status'] ?? 'available'); ?>">
                                        <?php echo ucfirst(htmlspecialchars($product['status'] ?? 'available')); ?>
                                    </span>
                                </div>
                                <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                                <p>R<?php echo number_format((float)$product['price'], 2); ?></p>
                            </div>
                            <div class="row-actions">
                                <a class="button secondary" href="product.php?id=<?php echo (int)$product['id']; ?>">View</a>
                                <form method="POST">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                    <button class="danger-button" type="submit">Remove</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <aside class="summary-panel">
                    <p class="eyebrow">Order summary</p>
                    <span class="summary-total">R<?php echo number_format($cartTotal, 2); ?></span>
                    <p><?php echo count($availableItems); ?> available item<?php echo count($availableItems) === 1 ? '' : 's'; ?> ready for checkout.</p>
                    <?php if ($availableItems): ?>
                        <a class="button primary" href="checkout.php">Checkout</a>
                    <?php else: ?>
                        <p class="sold-note">No available items can be checked out.</p>
                    <?php endif; ?>
                </aside>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2>Your cart is empty</h2>
                <p>Add available items from product pages and they will appear here.</p>
                <a class="button primary" href="index.php#products">Browse Products</a>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
