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
    $markSold = isset($_POST['mark_sold']);

    if (!verify_csrf()) {
        $message = 'Security check failed. Please try again.';
        $messageType = 'error';
    } elseif ($id && in_array($status, ['new', 'contacted', 'closed'], true)) {
        $stmt = $conn->prepare('UPDATE inquiries SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $id);

        if ($stmt->execute()) {
            if ($markSold) {
                $stmt = $conn->prepare('UPDATE products
                    INNER JOIN inquiries ON inquiries.product_id = products.id
                    SET products.status = "sold"
                    WHERE inquiries.id = ?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
            }

            $message = 'Inquiry updated successfully.';
            $messageType = 'success';
        } else {
            $message = 'The inquiry could not be updated.';
            $messageType = 'error';
        }
    }
}

$result = $conn->query('SELECT inquiries.*, products.name AS product_name, products.price, products.status AS product_status
    FROM inquiries
    LEFT JOIN products ON products.id = inquiries.product_id
    ORDER BY inquiries.created_at DESC');
$inquiries = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

include '../includes/header.php';
?>

<main>
    <section class="admin-list-page">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Customer requests</p>
                <h1>Inquiries</h1>
            </div>
            <a class="button secondary" href="products.php">Manage Products</a>
        </div>

        <?php if ($message): ?>
            <div class="form-message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($inquiries): ?>
            <div class="inquiry-list">
                <?php foreach ($inquiries as $inquiry): ?>
                    <article class="inquiry-card">
                        <div class="inquiry-main">
                            <div class="pill-row">
                                <span class="status-pill <?php echo htmlspecialchars($inquiry['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($inquiry['status'])); ?>
                                </span>
                                <span class="category-pill"><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($inquiry['created_at']))); ?></span>
                            </div>
                            <h2><?php echo htmlspecialchars($inquiry['product_name'] ?? 'Deleted product'); ?></h2>
                            <p class="price">R<?php echo number_format((float)($inquiry['price'] ?? 0), 2); ?></p>
                            <p><?php echo nl2br(htmlspecialchars($inquiry['message'])); ?></p>
                            <p class="inquiry-contact">
                                <?php echo htmlspecialchars($inquiry['name']); ?> &middot;
                                <a href="mailto:<?php echo htmlspecialchars($inquiry['email']); ?>"><?php echo htmlspecialchars($inquiry['email']); ?></a>
                            </p>
                        </div>

                        <form class="inquiry-actions" method="POST">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo (int)$inquiry['id']; ?>">
                            <label>
                                <span>Status</span>
                                <select name="status">
                                    <option value="new" <?php echo $inquiry['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                    <option value="contacted" <?php echo $inquiry['status'] === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                                    <option value="closed" <?php echo $inquiry['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </label>
                            <button type="submit">Update</button>
                            <?php if (($inquiry['product_status'] ?? '') !== 'sold'): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="mark_sold" value="1">
                                    <span>Mark product sold</span>
                                </label>
                            <?php endif; ?>
                            <?php if (!empty($inquiry['product_id'])): ?>
                                <a class="button secondary" href="../product.php?id=<?php echo (int)$inquiry['product_id']; ?>">View Item</a>
                            <?php endif; ?>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2>No inquiries yet</h2>
                <p>Customer messages from product pages will appear here.</p>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
