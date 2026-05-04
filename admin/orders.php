<?php
global $conn;
include '../includes/db.php';
include '../includes/auth.php';

require_admin('../index.php');

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';

    if (!verify_csrf()) {
        $message = 'Security check failed. Please try again.';
        $messageType = 'error';
    } elseif ($id && in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
        $stmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $id);

        if ($stmt->execute()) {
            $message = 'Order updated.';
            $messageType = 'success';
        } else {
            $message = 'Order could not be updated.';
            $messageType = 'error';
        }
    }
}

$result = $conn->query('SELECT orders.*, users.name AS account_name
    FROM orders
    LEFT JOIN users ON users.id = orders.user_id
    ORDER BY orders.created_at DESC');
$orders = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

include '../includes/header.php';
?>

<main>
    <section class="admin-list-page">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Checkout</p>
                <h1>Orders</h1>
            </div>
            <a class="button secondary" href="dashboard.php">Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="form-message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($orders): ?>
            <div class="inquiry-list">
                <?php foreach ($orders as $order): ?>
                    <?php
                    $stmt = $conn->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC');
                    $stmt->bind_param('i', $order['id']);
                    $stmt->execute();
                    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    ?>
                    <article class="inquiry-card">
                        <div class="inquiry-main">
                            <div class="pill-row">
                                <span class="status-pill <?php echo htmlspecialchars($order['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                </span>
                                <span class="category-pill"><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($order['created_at']))); ?></span>
                            </div>
                            <h2>Order #<?php echo (int)$order['id']; ?> · R<?php echo number_format((float)$order['total'], 2); ?></h2>
                            <p class="inquiry-contact">
                                <?php echo htmlspecialchars($order['name']); ?> &middot;
                                <a href="mailto:<?php echo htmlspecialchars($order['email']); ?>"><?php echo htmlspecialchars($order['email']); ?></a>
                                &middot; <?php echo htmlspecialchars($order['phone']); ?>
                            </p>
                            <p><?php echo ucfirst(htmlspecialchars($order['delivery_method'])); ?><?php echo $order['address'] ? ': ' . htmlspecialchars($order['address']) : ''; ?></p>
                            <?php if ($order['notes']): ?>
                                <p><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                            <?php endif; ?>
                            <div class="order-items">
                                <?php foreach ($items as $item): ?>
                                    <span><?php echo htmlspecialchars($item['product_name']); ?> - R<?php echo number_format((float)$item['price'], 2); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <form class="inquiry-actions" method="POST">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo (int)$order['id']; ?>">
                            <label>
                                <span>Status</span>
                                <select name="status">
                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </label>
                            <button type="submit">Update</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2>No orders yet</h2>
                <p>Customer checkouts will appear here.</p>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
