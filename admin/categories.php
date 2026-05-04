<?php
global $conn;
include '../includes/db.php';
include '../includes/auth.php';
include '../includes/categories.php';

require_admin('../index.php');

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');

    if (!verify_csrf()) {
        $message = 'Security check failed. Please try again.';
        $messageType = 'error';
    } elseif (in_array($action, ['create', 'update'], true) && $name === '') {
        $message = 'Please enter a category name.';
        $messageType = 'error';
    } elseif ($action === 'create') {
        $stmt = $conn->prepare('INSERT INTO categories (name) VALUES (?)');
        $stmt->bind_param('s', $name);

        if ($stmt->execute()) {
            $message = 'Category added successfully.';
            $messageType = 'success';
            $_POST = [];
        } else {
            $message = 'That category could not be added. It may already exist.';
            $messageType = 'error';
        }
    } elseif ($action === 'update' && $id) {
        $stmt = $conn->prepare('UPDATE categories SET name = ? WHERE id = ?');
        $stmt->bind_param('si', $name, $id);

        if ($stmt->execute()) {
            $message = 'Category updated successfully.';
            $messageType = 'success';
        } else {
            $message = 'That category could not be updated.';
            $messageType = 'error';
        }
    } elseif ($action === 'delete' && $id) {
        $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM products WHERE category_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $productCount = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);

        if ($productCount > 0) {
            $message = 'Move or delete products in this category before deleting it.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare('DELETE FROM categories WHERE id = ?');
            $stmt->bind_param('i', $id);

            if ($stmt->execute()) {
                $message = 'Category deleted successfully.';
                $messageType = 'success';
            } else {
                $message = 'That category could not be deleted.';
                $messageType = 'error';
            }
        }
    }
}

$result = $conn->query('SELECT categories.id, categories.name, COUNT(products.id) AS product_count
    FROM categories
    LEFT JOIN products ON products.category_id = categories.id
    GROUP BY categories.id, categories.name
    ORDER BY categories.name ASC');
$categories = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

include '../includes/header.php';
?>

<main>
    <section class="admin-page">
        <div class="admin-intro">
            <p class="eyebrow">Categories</p>
            <h1>Manage categories</h1>
            <p>Add, rename, or remove product categories used across the shop filters and inventory forms.</p>
            <div class="admin-actions">
                <a class="button secondary" href="products.php">Manage Products</a>
                <a class="button secondary" href="add_product.php">Add Product</a>
            </div>
        </div>

        <div class="category-panel">
            <?php if ($message): ?>
                <div class="form-message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form class="inline-form" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="create">
                <label>
                    <span>New category</span>
                    <input
                        type="text"
                        name="name"
                        placeholder="Shoes, Denim, Bags..."
                        value="<?php echo htmlspecialchars($_POST['action'] ?? '') === 'create' ? htmlspecialchars($_POST['name'] ?? '') : ''; ?>"
                        required
                    >
                </label>
                <button type="submit">Add Category</button>
            </form>

            <?php if ($categories): ?>
                <div class="category-list">
                    <?php foreach ($categories as $category): ?>
                        <article class="category-row">
                            <form class="inline-form" method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id" value="<?php echo (int)$category['id']; ?>">
                                <label>
                                    <span><?php echo (int)$category['product_count']; ?> product<?php echo (int)$category['product_count'] === 1 ? '' : 's'; ?></span>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                                </label>
                                <button type="submit">Save</button>
                            </form>

                            <form method="POST" onsubmit="return confirm('Delete this category?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$category['id']; ?>">
                                <button class="danger-button" type="submit">Delete</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h2>No categories yet</h2>
                    <p>Add a category before adding new products.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
