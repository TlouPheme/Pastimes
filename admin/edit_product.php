<?php
global $conn;
include '../includes/db.php';
include '../includes/auth.php';
include '../includes/categories.php';
include '../includes/images.php';

require_admin('../index.php');

$categories = get_categories($conn);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$product = null;
$message = '';
$messageType = '';

if ($id) {
    // Load the product being edited before rendering or accepting updates.
    $stmt = $conn->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
}

if (!$product) {
    include '../includes/header.php';
    ?>
    <main>
        <section class="empty-state">
            <h1>Product not found</h1>
            <p>This item may have been removed or the link may be incorrect.</p>
            <a class="button primary" href="products.php">Back to Products</a>
        </section>
    </main>
    <?php
    include '../includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $category = filter_input(INPUT_POST, 'category', FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? 'available';
    $featured = isset($_POST['featured']) ? 1 : 0;
    $image = $product['image'] ?? '';

    // This form can either remove one gallery image or update product details.
    if (!verify_csrf()) {
        $message = 'Security check failed. Please try again.';
        $messageType = 'error';
    } elseif (($_POST['action'] ?? '') === 'delete_gallery_image') {
        $imageId = filter_input(INPUT_POST, 'image_id', FILTER_VALIDATE_INT);

        if ($imageId) {
            $stmt = $conn->prepare('SELECT image FROM product_images WHERE id = ? AND product_id = ?');
            $stmt->bind_param('ii', $imageId, $id);
            $stmt->execute();
            $galleryImage = $stmt->get_result()->fetch_assoc();

            $stmt = $conn->prepare('DELETE FROM product_images WHERE id = ? AND product_id = ?');
            $stmt->bind_param('ii', $imageId, $id);

            if ($stmt->execute()) {
                if (!empty($galleryImage['image'])) {
                    $galleryPath = '../assets/images/' . $galleryImage['image'];

                    if (is_file($galleryPath)) {
                        unlink($galleryPath);
                    }
                }

                $message = 'Gallery image removed.';
                $messageType = 'success';
            }
        }
    } elseif ($name === '' || $price === false || $price < 0 || !array_key_exists((int)$category, $categories) || !in_array($status, ['available', 'sold'], true)) {
        $message = 'Please complete the required fields with valid product details.';
        $messageType = 'error';
    } else {
        $imageError = null;
        $newImage = save_product_image($_FILES['image'] ?? [], '../assets/images', $imageError);

        if ($imageError) {
            $message = $imageError;
            $messageType = 'error';
        } else {
            if ($newImage !== '') {
                $image = $newImage;
            }

            $galleryError = null;
            $galleryImages = save_product_images($_FILES['gallery_images'] ?? [], '../assets/images', $galleryError);

            if ($galleryError) {
                // If gallery upload fails, remove the newly uploaded replacement image.
                if ($newImage !== '') {
                    $newImagePath = '../assets/images/' . $newImage;

                    if (is_file($newImagePath)) {
                        unlink($newImagePath);
                    }
                }

                $message = $galleryError;
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare('UPDATE products SET name = ?, description = ?, price = ?, image = ?, category_id = ?, status = ?, featured = ? WHERE id = ?');
                $stmt->bind_param('ssdsisii', $name, $desc, $price, $image, $category, $status, $featured, $id);

                if ($stmt->execute()) {
                    // A successful replacement lets us remove the old primary image.
                    if ($newImage !== '' && !empty($product['image'])) {
                        $oldImagePath = '../assets/images/' . $product['image'];

                        if (is_file($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }

                    foreach ($galleryImages as $galleryImage) {
                        $stmt = $conn->prepare('INSERT INTO product_images (product_id, image) VALUES (?, ?)');
                        $stmt->bind_param('is', $id, $galleryImage);
                        $stmt->execute();
                    }

                    $message = 'Product updated successfully.';
                    $messageType = 'success';
                    $product = [
                        'id' => $id,
                        'name' => $name,
                        'description' => $desc,
                        'price' => $price,
                        'image' => $image,
                        'category_id' => $category,
                        'status' => $status,
                        'featured' => $featured,
                    ];
                } else {
                    // Database update failed, so remove newly uploaded files.
                    if ($newImage !== '') {
                        $newImagePath = '../assets/images/' . $newImage;

                        if (is_file($newImagePath)) {
                            unlink($newImagePath);
                        }
                    }

                    foreach ($galleryImages as $galleryImage) {
                        $galleryPath = '../assets/images/' . $galleryImage;

                        if (is_file($galleryPath)) {
                            unlink($galleryPath);
                        }
                    }

                    $message = 'The product could not be updated. Please try again.';
                    $messageType = 'error';
                }
            }
        }
    }
}

$stmt = $conn->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY created_at ASC');
$stmt->bind_param('i', $id);
$stmt->execute();
$galleryImages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>

<main>
    <section class="admin-page">
        <div class="admin-intro">
            <p class="eyebrow">Inventory</p>
            <h1>Edit product</h1>
            <p>Update the product details, price, category, or upload a replacement image.</p>
            <div class="admin-actions">
                <a class="button secondary" href="products.php">Manage Products</a>
                <a class="button secondary" href="categories.php">Manage Categories</a>
                <a class="button secondary" href="../product.php?id=<?php echo (int)$product['id']; ?>">View Product</a>
            </div>
        </div>

        <form class="product-form" method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <?php if ($message): ?>
                <div class="form-message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <label>
                <span>Product name</span>
                <input
                    type="text"
                    name="name"
                    value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>"
                    required
                >
            </label>

            <label>
                <span>Description</span>
                <textarea name="description" rows="5"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
            </label>

            <div class="form-grid">
                <label>
                    <span>Price</span>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="price"
                        value="<?php echo htmlspecialchars((string)($product['price'] ?? '')); ?>"
                        required
                    >
                </label>

                <label>
                    <span>Category</span>
                    <select name="category" required>
                        <?php foreach ($categories as $categoryId => $label): ?>
                            <option value="<?php echo $categoryId; ?>" <?php echo (int)($product['category_id'] ?? 0) === $categoryId ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span>Status</span>
                    <select name="status" required>
                        <option value="available" <?php echo ($product['status'] ?? 'available') === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="sold" <?php echo ($product['status'] ?? '') === 'sold' ? 'selected' : ''; ?>>Sold</option>
                    </select>
                </label>

                <label class="checkbox-label feature-toggle">
                    <input type="checkbox" name="featured" value="1" <?php echo (int)($product['featured'] ?? 0) === 1 ? 'checked' : ''; ?>>
                    <span>Feature this product</span>
                </label>
            </div>

            <?php if (!empty($product['image'])): ?>
                <div class="current-image">
                    <span>Current image</span>
                    <img src="../assets/images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
            <?php endif; ?>

            <label>
                <span>Replace product image</span>
                <input type="file" name="image" accept="image/*" data-image-preview="#primary-preview">
            </label>
            <div id="primary-preview" class="preview-grid"></div>

            <?php if ($galleryImages): ?>
                <div class="gallery-manager">
                    <span>Gallery images</span>
                    <div class="gallery-grid">
                        <?php foreach ($galleryImages as $galleryImage): ?>
                            <div class="gallery-admin-item">
                                <img src="../assets/images/<?php echo htmlspecialchars($galleryImage['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <button
                                    class="danger-button"
                                    type="submit"
                                    name="action"
                                    value="delete_gallery_image"
                                    formaction="edit_product.php?id=<?php echo (int)$product['id']; ?>"
                                    onclick="this.form.image_id.value='<?php echo (int)$galleryImage['id']; ?>'; return confirm('Remove this gallery image?');"
                                >
                                    Remove
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="image_id" value="">
                </div>
            <?php endif; ?>

            <label>
                <span>Add gallery images</span>
                <input type="file" name="gallery_images[]" accept="image/*" multiple data-image-preview="#gallery-preview">
            </label>
            <div id="gallery-preview" class="preview-grid"></div>

            <button type="submit">Save Changes</button>
        </form>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
