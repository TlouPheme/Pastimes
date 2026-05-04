<?php
global $conn;
include '../includes/db.php';
include '../includes/auth.php';
include '../includes/categories.php';

require_admin('../index.php');

$categories = get_categories($conn);
$search = trim($_GET['search'] ?? '');
$category = (int)($_GET['category'] ?? 0);
$status = $_GET['status'] ?? 'all';
$minPrice = filter_input(INPUT_GET, 'min_price', FILTER_VALIDATE_FLOAT);
$maxPrice = filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT);
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!verify_csrf()) {
        $message = 'Security check failed. Please try again.';
        $messageType = 'error';
    } elseif ($action === 'toggle_status' && $id) {
        $newStatus = $_POST['status'] === 'sold' ? 'sold' : 'available';
        $stmt = $conn->prepare('UPDATE products SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $newStatus, $id);

        if ($stmt->execute()) {
            $message = 'Product status updated.';
            $messageType = 'success';
        } else {
            $message = 'The product status could not be updated.';
            $messageType = 'error';
        }
    } elseif ($action === 'delete' && $id) {
        $stmt = $conn->prepare('SELECT image FROM products WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();

        $stmt = $conn->prepare('SELECT image FROM product_images WHERE product_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $galleryImages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
        $stmt->bind_param('i', $id);

        if ($stmt->execute()) {
            if (!empty($product['image'])) {
                $imagePath = '../assets/images/' . $product['image'];

                if (is_file($imagePath)) {
                    unlink($imagePath);
                }
            }

            foreach ($galleryImages as $galleryImage) {
                if (!empty($galleryImage['image'])) {
                    $imagePath = '../assets/images/' . $galleryImage['image'];

                    if (is_file($imagePath)) {
                        unlink($imagePath);
                    }
                }
            }

            $message = 'Product deleted successfully.';
            $messageType = 'success';
        } else {
            $message = 'The product could not be deleted. Please try again.';
            $messageType = 'error';
        }
    }
}

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = '(products.name LIKE ? OR products.description LIKE ?)';
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $types .= 'ss';
}

if (array_key_exists($category, $categories)) {
    $where[] = 'products.category_id = ?';
    $params[] = $category;
    $types .= 'i';
}

if (in_array($status, ['available', 'sold'], true)) {
    $where[] = 'products.status = ?';
    $params[] = $status;
    $types .= 's';
}

if ($minPrice !== false && $minPrice !== null && $minPrice >= 0) {
    $where[] = 'products.price >= ?';
    $params[] = $minPrice;
    $types .= 'd';
}

if ($maxPrice !== false && $maxPrice !== null && $maxPrice >= 0) {
    $where[] = 'products.price <= ?';
    $params[] = $maxPrice;
    $types .= 'd';
}

$orderBy = match ($sort) {
    'price_low' => 'products.price ASC',
    'price_high' => 'products.price DESC',
    'name' => 'products.name ASC',
    default => 'products.created_at DESC',
};

$countSql = 'SELECT COUNT(*) AS total
    FROM products
    LEFT JOIN categories ON categories.id = products.category_id';

if ($where) {
    $countSql .= ' WHERE ' . implode(' AND ', $where);
}

$countStmt = $conn->prepare($countSql);

if ($params) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$productCount = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$totalPages = max(1, (int)ceil($productCount / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$sql = 'SELECT products.*, categories.name AS category_name
    FROM products
    LEFT JOIN categories ON categories.id = products.category_id';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY ' . $orderBy . ' LIMIT ? OFFSET ?';

$stmt = $conn->prepare($sql);
$queryTypes = $types . 'ii';
$queryParams = array_merge($params, [$perPage, $offset]);

$stmt->bind_param($queryTypes, ...$queryParams);

$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageUrl = function (int $targetPage): string {
    $query = $_GET;
    $query['page'] = $targetPage;

    return 'products.php?' . http_build_query($query);
};

include '../includes/header.php';
?>

<main>
    <section class="admin-list-page">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Inventory</p>
                <h1>Manage products</h1>
            </div>
            <a class="button primary" href="add_product.php">Add Product</a>
        </div>

        <?php if ($message): ?>
            <div class="form-message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <section class="shop-toolbar" aria-label="Product filters">
            <form method="GET" class="filter-form admin-filter-form">
                <label>
                    <span>Search</span>
                    <input
                        type="search"
                        name="search"
                        placeholder="Product name..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </label>

                <label>
                    <span>Category</span>
                    <select name="category">
                        <option value="0">All categories</option>
                        <?php foreach ($categories as $id => $label): ?>
                            <option value="<?php echo $id; ?>" <?php echo $category === $id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span>Status</span>
                    <select name="status">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All stock</option>
                        <option value="available" <?php echo $status === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="sold" <?php echo $status === 'sold' ? 'selected' : ''; ?>>Sold</option>
                    </select>
                </label>

                <label>
                    <span>Min price</span>
                    <input type="number" min="0" step="0.01" name="min_price" value="<?php echo htmlspecialchars($_GET['min_price'] ?? ''); ?>">
                </label>

                <label>
                    <span>Max price</span>
                    <input type="number" min="0" step="0.01" name="max_price" value="<?php echo htmlspecialchars($_GET['max_price'] ?? ''); ?>">
                </label>

                <label>
                    <span>Sort by</span>
                    <select name="sort">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: low to high</option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: high to low</option>
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name</option>
                    </select>
                </label>

                <button type="submit">Apply</button>
                <a class="clear-link" href="products.php">Clear</a>
            </form>
        </section>

        <?php if ($products): ?>
            <div class="product-table">
                <?php foreach ($products as $product): ?>
                    <?php
                    $image = $product['image'] ?? '';
                    $imagePath = '../assets/images/' . $image;
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
                            <span class="category-pill"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                            <?php if ((int)($product['featured'] ?? 0) === 1): ?>
                                <span class="featured-pill">Featured</span>
                            <?php endif; ?>
                            <span class="status-pill <?php echo htmlspecialchars($product['status'] ?? 'available'); ?>">
                                <?php echo ucfirst(htmlspecialchars($product['status'] ?? 'available')); ?>
                            </span>
                            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                            <p>R<?php echo number_format((float)$product['price'], 2); ?></p>
                        </div>

                        <div class="row-actions">
                            <a class="button secondary" href="../product.php?id=<?php echo (int)$product['id']; ?>">View</a>
                            <a class="button primary" href="edit_product.php?id=<?php echo (int)$product['id']; ?>">Edit</a>
                            <form class="quick-status-form" method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?php echo (int)$product['id']; ?>">
                                <input type="hidden" name="status" value="<?php echo ($product['status'] ?? 'available') === 'sold' ? 'available' : 'sold'; ?>">
                                <button class="button secondary" type="submit">
                                    Mark <?php echo ($product['status'] ?? 'available') === 'sold' ? 'Available' : 'Sold'; ?>
                                </button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Delete this product permanently?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$product['id']; ?>">
                                <button class="danger-button" type="submit">Delete</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if ($totalPages > 1): ?>
                <nav class="pagination" aria-label="Product pages">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo htmlspecialchars($pageUrl($page - 1)); ?>">Prev</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="current-page"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($pageUrl($i)); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?php echo htmlspecialchars($pageUrl($page + 1)); ?>">Next</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <h2>No products yet</h2>
                <p>Add your first thrift item to start building the store inventory.</p>
                <a class="button primary" href="add_product.php">Add Product</a>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
