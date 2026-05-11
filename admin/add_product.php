<?php
global $conn;
include '../includes/db.php';
include '../includes/auth.php';
include '../includes/categories.php';
include '../includes/images.php';

require_admin('../index.php');

$categories = get_categories($conn);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $category = filter_input(INPUT_POST, 'category', FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? 'available';
    $featured = isset($_POST['featured']) ? 1 : 0;
    $image = '';

    // Validate product fields before accepting any uploaded image files.
    if (!verify_csrf()) {
        $message = 'Security check failed. Please try again.';
        $messageType = 'error';
    } elseif ($name === '' || $price === false || $price < 0 || !array_key_exists((int)$category, $categories) || !in_array($status, ['available', 'sold'], true)) {
        $message = 'Please complete the required fields with valid product details.';
        $messageType = 'error';
    } else {
        $imageError = null;
        $image = save_product_image($_FILES['image'] ?? [], '../assets/images', $imageError);

        if ($imageError) {
            $message = $imageError;
            $messageType = 'error';
        } else {
            $galleryError = null;
            $galleryImages = save_product_images($_FILES['gallery_images'] ?? [], '../assets/images', $galleryError);

            if ($galleryError) {
                // Remove the primary image if gallery upload fails before saving the product.
                if ($image !== '') {
                    $imagePath = '../assets/images/' . $image;

                    if (is_file($imagePath)) {
                        unlink($imagePath);
                    }
                }

                $message = $galleryError;
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare('INSERT INTO products (name, description, price, image, category_id, status, featured) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('ssdsisi', $name, $desc, $price, $image, $category, $status, $featured);

                if ($stmt->execute()) {
                    $productId = $stmt->insert_id;

                    // Save optional gallery images after the product id is available.
                    foreach ($galleryImages as $galleryImage) {
                        $stmt = $conn->prepare('INSERT INTO product_images (product_id, image) VALUES (?, ?)');
                        $stmt->bind_param('is', $productId, $galleryImage);
                        $stmt->execute();
                    }

                    $message = 'Product added successfully.';
                    $messageType = 'success';
                    $_POST = [];
                } else {
                    // Database save failed, so remove uploaded files to avoid orphaned images.
                    if ($image !== '') {
                        $imagePath = '../assets/images/' . $image;

                        if (is_file($imagePath)) {
                            unlink($imagePath);
                        }
                    }

                    foreach ($galleryImages as $galleryImage) {
                        $galleryPath = '../assets/images/' . $galleryImage;

                        if (is_file($galleryPath)) {
                            unlink($galleryPath);
                        }
                    }

                    $message = 'The product could not be saved. Please try again.';
                    $messageType = 'error';
                }
            }
        }
    }
}

include '../includes/header.php';
?>

<main>
    <section class="admin-page">
        <div class="admin-intro">
            <p class="eyebrow">Inventory</p>
            <h1>Add a thrift item</h1>
            <p>Create a product listing with a category, price, image, and clear item notes.</p>
            <div class="admin-actions">
                <a class="button secondary" href="products.php">Manage Products</a>
                <a class="button secondary" href="categories.php">Manage Categories</a>
                <a class="button secondary" href="../index.php">Back to Shop</a>
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
                    placeholder="Vintage denim jacket"
                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                    required
                >
            </label>

            <label>
                <span>Description</span>
                <textarea name="description" rows="5" placeholder="Condition, sizing notes, standout details..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </label>

            <div class="form-grid">
                <label>
                    <span>Price</span>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="price"
                        placeholder="350.00"
                        value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>"
                        required
                    >
                </label>

                <label>
                    <span>Category</span>
                    <select name="category" required>
                        <?php if (!$categories): ?>
                            <option value="">No categories found</option>
                        <?php endif; ?>
                        <?php foreach ($categories as $id => $label): ?>
                            <option value="<?php echo $id; ?>" <?php echo (int)($_POST['category'] ?? 1) === $id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span>Status</span>
                    <select name="status" required>
                        <option value="available" <?php echo ($_POST['status'] ?? 'available') === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="sold" <?php echo ($_POST['status'] ?? '') === 'sold' ? 'selected' : ''; ?>>Sold</option>
                    </select>
                </label>

                <label class="checkbox-label feature-toggle">
                    <input type="checkbox" name="featured" value="1" <?php echo isset($_POST['featured']) ? 'checked' : ''; ?>>
                    <span>Feature this product</span>
                </label>
            </div>

            <label>
                <span>Primary product image</span>
                <input type="file" name="image" accept="image/*" data-image-preview="#primary-preview">
            </label>
            <div id="primary-preview" class="preview-grid"></div>

            <label>
                <span>Gallery images</span>
                <input type="file" name="gallery_images[]" accept="image/*" multiple data-image-preview="#gallery-preview">
            </label>
            <div id="gallery-preview" class="preview-grid"></div>

            <button type="submit">Add Product</button>
        </form>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
