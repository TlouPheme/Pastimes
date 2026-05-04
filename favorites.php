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
        $stmt = $conn->prepare('DELETE FROM favorites WHERE user_id = ? AND product_id = ?');
        $stmt->bind_param('ii', $user['id'], $productId);

        if ($stmt->execute()) {
            $message = 'Favorite removed.';
            $messageType = 'success';
        }
    }
}

$stmt = $conn->prepare('SELECT products.*, categories.name AS category_name
    FROM favorites
    INNER JOIN products ON products.id = favorites.product_id
    LEFT JOIN categories ON categories.id = products.category_id
    WHERE favorites.user_id = ?
    ORDER BY favorites.created_at DESC');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$favorites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<main>
    <section class="admin-list-page">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Saved items</p>
                <h1>Your favorites</h1>
            </div>
            <a class="button secondary" href="index.php#products">Back to Shop</a>
        </div>

        <?php if ($message): ?>
            <div class="form-message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($favorites): ?>
            <div class="container">
                <?php foreach ($favorites as $product): ?>
                    <?php
                    $image = $product['image'] ?? '';
                    $imagePath = 'assets/images/' . $image;
                    $hasImage = $image !== '' && file_exists($imagePath);
                    ?>
                    <article class="card">
                        <a class="product-image" href="product.php?id=<?php echo (int)$product['id']; ?>">
                            <?php if ($hasImage): ?>
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <span>No image</span>
                            <?php endif; ?>
                        </a>
                        <div class="card-body">
                            <div class="pill-row">
                                <span class="category-pill"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                                <span class="status-pill <?php echo htmlspecialchars($product['status'] ?? 'available'); ?>">
                                    <?php echo ucfirst(htmlspecialchars($product['status'] ?? 'available')); ?>
                                </span>
                            </div>
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="price">R<?php echo number_format((float)$product['price'], 2); ?></p>
                            <a class="button product-link" href="product.php?id=<?php echo (int)$product['id']; ?>">View Item</a>
                            <form method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                <button class="danger-button full-button" type="submit">Remove</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2>No favorites yet</h2>
                <p>Save items from product pages and they will appear here.</p>
                <a class="button primary" href="index.php#products">Browse Products</a>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
