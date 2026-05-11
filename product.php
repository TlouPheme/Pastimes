<?php
global $conn;
include 'includes/db.php';
include 'includes/auth.php';
include 'includes/categories.php';

$categories = get_categories($conn);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$product = null;
$message = '';
$messageType = '';
$favoriteMessage = '';
$favoriteMessageType = '';
$cartMessage = '';
$cartMessageType = '';
$isFavorite = false;
$isInCart = false;

if ($id) {
    // Load the product with its category label for the detail view.
    $stmt = $conn->prepare('SELECT products.*, categories.name AS category_name
        FROM products
        LEFT JOIN categories ON categories.id = products.category_id
        WHERE products.id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
}

if ($product && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = current_user();

    // One product page handles cart, favorite, and inquiry submissions.
    if (!verify_csrf()) {
        $message = 'Security check failed. Please try again.';
        $messageType = 'error';
        $favoriteMessage = $message;
        $favoriteMessageType = 'error';
    } elseif (($_POST['action'] ?? '') === 'cart') {
        if (!$user) {
            $cartMessage = 'Please log in to add items to your cart.';
            $cartMessageType = 'error';
        } elseif (($product['status'] ?? 'available') === 'sold') {
            $cartMessage = 'This item has already been sold.';
            $cartMessageType = 'error';
        } else {
            $userId = (int)$user['id'];
            $stmt = $conn->prepare('INSERT IGNORE INTO cart_items (user_id, product_id) VALUES (?, ?)');
            $stmt->bind_param('ii', $userId, $id);

            if ($stmt->execute()) {
                $cartMessage = 'Added to cart.';
                $cartMessageType = 'success';
            } else {
                $cartMessage = 'This item could not be added to your cart.';
                $cartMessageType = 'error';
            }
        }
    } elseif (($_POST['action'] ?? '') === 'favorite') {
        if (!$user) {
            $favoriteMessage = 'Please log in to save favorites.';
            $favoriteMessageType = 'error';
        } else {
            $userId = (int)$user['id'];

            if (($_POST['favorite_action'] ?? '') === 'remove') {
                $stmt = $conn->prepare('DELETE FROM favorites WHERE user_id = ? AND product_id = ?');
                $stmt->bind_param('ii', $userId, $id);
                $stmt->execute();
                $favoriteMessage = 'Removed from favorites.';
                $favoriteMessageType = 'success';
            } else {
                $stmt = $conn->prepare('INSERT IGNORE INTO favorites (user_id, product_id) VALUES (?, ?)');
                $stmt->bind_param('ii', $userId, $id);
                $stmt->execute();
                $favoriteMessage = 'Saved to favorites.';
                $favoriteMessageType = 'success';
            }
        }
    } else {
        $name = trim($_POST['name'] ?? ($user['name'] ?? ''));
        $email = trim($_POST['email'] ?? ($user['email'] ?? ''));
        $inquiryMessage = trim($_POST['message'] ?? '');
        $userId = $user ? (int)$user['id'] : null;

        if (($product['status'] ?? 'available') === 'sold') {
            $message = 'This item has already been sold.';
            $messageType = 'error';
        } elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $inquiryMessage === '') {
            $message = 'Please enter your name, email, and message.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare('INSERT INTO inquiries (product_id, user_id, name, email, message) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('iisss', $id, $userId, $name, $email, $inquiryMessage);

            if ($stmt->execute()) {
                $message = 'Your inquiry was sent. We will get back to you soon.';
                $messageType = 'success';
                $_POST = [];
            } else {
                $message = 'Your inquiry could not be sent. Please try again.';
                $messageType = 'error';
            }
        }
    }
}

if ($product && current_user()) {
    // Preload customer-specific state so the buttons show the right action.
    $userId = (int)current_user()['id'];
    $stmt = $conn->prepare('SELECT id FROM favorites WHERE user_id = ? AND product_id = ? LIMIT 1');
    $stmt->bind_param('ii', $userId, $id);
    $stmt->execute();
    $isFavorite = $stmt->get_result()->fetch_assoc() !== null;

    $stmt = $conn->prepare('SELECT id FROM cart_items WHERE user_id = ? AND product_id = ? LIMIT 1');
    $stmt->bind_param('ii', $userId, $id);
    $stmt->execute();
    $isInCart = $stmt->get_result()->fetch_assoc() !== null;
}

$galleryImages = [];

if ($product) {
    // The gallery starts with the primary image, then adds any extra product images.
    if (!empty($product['image'])) {
        $galleryImages[] = $product['image'];
    }

    $stmt = $conn->prepare('SELECT image FROM product_images WHERE product_id = ? ORDER BY created_at ASC');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $extraImages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($extraImages as $extraImage) {
        $galleryImages[] = $extraImage['image'];
    }
}

include 'includes/header.php';
?>

<main>
    <?php if ($product): ?>
        <?php
        $image = $product['image'] ?? '';
        $imagePath = 'assets/images/' . $image;
        $hasImage = $image !== '' && file_exists($imagePath);
        $categoryLabel = $product['category_name'] ?? 'Uncategorized';
        ?>
        <article class="product-page">
            <div class="product-media">
                <div class="product-detail-image">
                    <?php if ($hasImage): ?>
                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <span>No image available</span>
                    <?php endif; ?>
                </div>
                <?php if (count($galleryImages) > 1): ?>
                    <div class="product-gallery">
                        <?php foreach ($galleryImages as $galleryImage): ?>
                            <?php $galleryPath = 'assets/images/' . $galleryImage; ?>
                            <?php if ($galleryImage !== '' && file_exists($galleryPath)): ?>
                                <a href="<?php echo htmlspecialchars($galleryPath); ?>" target="_blank" aria-label="Open product image">
                                    <img src="<?php echo htmlspecialchars($galleryPath); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="product-detail-copy">
                <a class="back-link" href="index.php">Back to shop</a>
                <div class="pill-row">
                    <span class="category-pill"><?php echo htmlspecialchars($categoryLabel); ?></span>
                    <?php if ((int)($product['featured'] ?? 0) === 1): ?>
                        <span class="featured-pill">Featured</span>
                    <?php endif; ?>
                    <span class="status-pill <?php echo htmlspecialchars($product['status'] ?? 'available'); ?>">
                        <?php echo ucfirst(htmlspecialchars($product['status'] ?? 'available')); ?>
                    </span>
                </div>
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p><?php echo nl2br(htmlspecialchars($product['description'] ?? '')); ?></p>
                <p class="detail-price">R<?php echo number_format((float)$product['price'], 2); ?></p>
                <?php if (!is_admin()): ?>
                    <form class="favorite-form" method="POST">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="favorite">
                        <input type="hidden" name="favorite_action" value="<?php echo $isFavorite ? 'remove' : 'add'; ?>">
                        <button class="<?php echo $isFavorite ? 'button secondary' : 'button primary'; ?>" type="submit">
                            <?php echo $isFavorite ? 'Remove Favorite' : 'Save Favorite'; ?>
                        </button>
                        <?php if ($favoriteMessage): ?>
                            <span class="inline-message <?php echo $favoriteMessageType; ?>"><?php echo htmlspecialchars($favoriteMessage); ?></span>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
                <?php if (($product['status'] ?? 'available') === 'sold'): ?>
                    <p class="sold-note">This item has already been sold.</p>
                <?php else: ?>
                    <?php if (!is_admin()): ?>
                        <form class="favorite-form" method="POST">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="cart">
                            <?php if ($isInCart): ?>
                                <a class="button secondary" href="cart.php">View Cart</a>
                            <?php else: ?>
                                <button class="button primary" type="submit">Add to Cart</button>
                            <?php endif; ?>
                            <?php if ($cartMessage): ?>
                                <span class="inline-message <?php echo $cartMessageType; ?>"><?php echo htmlspecialchars($cartMessage); ?></span>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                    <form class="inquiry-form" method="POST">
                        <?php echo csrf_field(); ?>
                        <h2>Ask about this item</h2>
                        <?php if ($message): ?>
                            <div class="form-message <?php echo $messageType; ?>">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        <div class="form-grid">
                            <label>
                                <span>Name</span>
                                <input
                                    type="text"
                                    name="name"
                                    value="<?php echo htmlspecialchars($_POST['name'] ?? (current_user()['name'] ?? '')); ?>"
                                    required
                                >
                            </label>
                            <label>
                                <span>Email</span>
                                <input
                                    type="email"
                                    name="email"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? (current_user()['email'] ?? '')); ?>"
                                    required
                                >
                            </label>
                        </div>
                        <label>
                            <span>Message</span>
                            <textarea name="message" rows="4" placeholder="I would like to reserve this item or ask about sizing..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </label>
                        <button type="submit">Send Inquiry</button>
                    </form>
                <?php endif; ?>
                <?php if (is_admin()): ?>
                    <div class="detail-actions">
                        <a class="button primary" href="admin/edit_product.php?id=<?php echo (int)$product['id']; ?>">Edit Product</a>
                        <a class="button secondary" href="admin/products.php">Manage Products</a>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    <?php else: ?>
        <section class="empty-state">
            <h1>Product not found</h1>
            <p>This item may have been removed or the link may be incorrect.</p>
            <a class="button primary" href="index.php">Back to Shop</a>
        </section>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
