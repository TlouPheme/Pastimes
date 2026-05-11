<?php
global $conn;
include 'includes/db.php';
include 'includes/categories.php';

$categories = get_categories($conn);

$search = trim($_GET['search'] ?? '');
$category = (int)($_GET['category'] ?? 0);
$status = $_GET['status'] ?? 'all';
$minPrice = filter_input(INPUT_GET, 'min_price', FILTER_VALIDATE_FLOAT);
$maxPrice = filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT);
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
$types = '';

// Build the product filters and matching bind types for a prepared statement.
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

// Count the filtered results first so pagination can clamp out-of-range pages.
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

// Reuse the same filters for the paginated product query.
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY ' . $orderBy . ' LIMIT ? OFFSET ?';

$stmt = $conn->prepare($sql);
$queryTypes = $types . 'ii';
$queryParams = array_merge($params, [$perPage, $offset]);

$stmt->bind_param($queryTypes, ...$queryParams);

$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$featured = $products[0] ?? null;

$pageUrl = function (int $targetPage): string {
    $query = $_GET;
    $query['page'] = $targetPage;

    return 'index.php?' . http_build_query($query) . '#products';
};

$featuredResult = $conn->query('SELECT products.*, categories.name AS category_name
    FROM products
    LEFT JOIN categories ON categories.id = products.category_id
    WHERE products.featured = 1
    ORDER BY products.created_at DESC
    LIMIT 4');
$featuredProducts = $featuredResult ? $featuredResult->fetch_all(MYSQLI_ASSOC) : [];

include 'includes/header.php';
?>

<main>
    <section class="hero">
        <div class="hero-copy">
            <p class="eyebrow">Curated second-hand finds</p>
            <h1>Shop thrift pieces with less scrolling and more intent.</h1>
            <p>Filter by category, search for specific items, and sort the newest stock by price or name.</p>
            <div class="hero-actions">
                <a class="button primary" href="#products">Browse Products</a>
                <?php if (is_admin()): ?>
                    <a class="button secondary" href="admin/add_product.php">Add New Stock</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="hero-panel" aria-label="Store summary">
            <span class="metric"><?php echo $productCount; ?></span>
            <span>matching item<?php echo $productCount === 1 ? '' : 's'; ?></span>
            <?php if ($featured): ?>
                <p>Latest find: <?php echo htmlspecialchars($featured['name']); ?></p>
            <?php else: ?>
                <p>No products match the current filters.</p>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($featuredProducts): ?>
        <section class="featured-section" aria-label="Featured products">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Featured finds</p>
                    <h2>Picked for the front rack</h2>
                </div>
            </div>

            <div class="container">
                <?php foreach ($featuredProducts as $row): ?>
                    <?php
                    $image = $row['image'] ?? '';
                    $imagePath = 'assets/images/' . $image;
                    $hasImage = $image !== '' && file_exists($imagePath);
                    ?>
                    <article class="card featured-card">
                        <a class="product-image" href="product.php?id=<?php echo (int)$row['id']; ?>">
                            <?php if ($hasImage): ?>
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                            <?php else: ?>
                                <span>No image</span>
                            <?php endif; ?>
                        </a>
                        <div class="card-body">
                            <div class="pill-row">
                                <span class="category-pill"><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></span>
                                <span class="featured-pill">Featured</span>
                            </div>
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p class="price">R<?php echo number_format((float)$row['price'], 2); ?></p>
                            <a class="button product-link" href="product.php?id=<?php echo (int)$row['id']; ?>">View Item</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="shop-toolbar" aria-label="Product filters">
        <form method="GET" class="filter-form">
            <label>
                <span>Search</span>
                <input
                    type="search"
                    name="search"
                    placeholder="Jackets, denim, boots..."
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
                <input
                    type="number"
                    min="0"
                    step="0.01"
                    name="min_price"
                    placeholder="0"
                    value="<?php echo htmlspecialchars($_GET['min_price'] ?? ''); ?>"
                >
            </label>

            <label>
                <span>Max price</span>
                <input
                    type="number"
                    min="0"
                    step="0.01"
                    name="max_price"
                    placeholder="1000"
                    value="<?php echo htmlspecialchars($_GET['max_price'] ?? ''); ?>"
                >
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

            <button type="submit">Apply Filters</button>
            <a class="clear-link" href="index.php">Clear</a>
        </form>
    </section>

    <section id="products" class="product-section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Current stock</p>
                <h2><?php echo $productCount; ?> item<?php echo $productCount === 1 ? '' : 's'; ?> found</h2>
            </div>
        </div>

        <?php if ($products): ?>
            <div class="container">
                <?php foreach ($products as $row): ?>
                    <?php
                    $image = $row['image'] ?? '';
                    $imagePath = 'assets/images/' . $image;
                    $hasImage = $image !== '' && file_exists($imagePath);
                    $categoryLabel = $row['category_name'] ?? 'Uncategorized';
                    ?>
                    <article class="card">
                        <a class="product-image" href="product.php?id=<?php echo (int)$row['id']; ?>">
                            <?php if ($hasImage): ?>
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                            <?php else: ?>
                                <span>No image</span>
                            <?php endif; ?>
                        </a>
                        <div class="card-body">
                            <div class="pill-row">
                                <span class="category-pill"><?php echo htmlspecialchars($categoryLabel); ?></span>
                                <?php if ((int)($row['featured'] ?? 0) === 1): ?>
                                    <span class="featured-pill">Featured</span>
                                <?php endif; ?>
                                <span class="status-pill <?php echo htmlspecialchars($row['status'] ?? 'available'); ?>">
                                    <?php echo ucfirst(htmlspecialchars($row['status'] ?? 'available')); ?>
                                </span>
                            </div>
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p class="price">R<?php echo number_format((float)$row['price'], 2); ?></p>
                            <?php if (is_admin()): ?>
                                <div class="card-actions">
                                    <a class="button secondary" href="admin/edit_product.php?id=<?php echo (int)$row['id']; ?>">Edit</a>
                                </div>
                            <?php endif; ?>
                            <a class="button product-link" href="product.php?id=<?php echo (int)$row['id']; ?>">View Item</a>
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
                <h2>No products found</h2>
                <p>Try a different search term, remove the category filter, or add new stock.</p>
                <?php if (is_admin()): ?>
                    <a class="button primary" href="admin/add_product.php">Add Product</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
