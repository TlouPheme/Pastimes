<?php
global $conn;
include '../includes/db.php';
include '../includes/auth.php';

require_admin('../index.php');

$stats = [
    'products' => 0,
    'available' => 0,
    'sold' => 0,
    'inquiries' => 0,
    'new_inquiries' => 0,
    'favorites' => 0,
    'users' => 0,
    'orders' => 0,
];

$result = $conn->query("SELECT
    COUNT(*) AS products,
    SUM(status = 'available') AS available,
    SUM(status = 'sold') AS sold
    FROM products");

if ($result) {
    $row = $result->fetch_assoc();
    $stats['products'] = (int)($row['products'] ?? 0);
    $stats['available'] = (int)($row['available'] ?? 0);
    $stats['sold'] = (int)($row['sold'] ?? 0);
}

$result = $conn->query("SELECT COUNT(*) AS inquiries, SUM(status = 'new') AS new_inquiries FROM inquiries");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['inquiries'] = (int)($row['inquiries'] ?? 0);
    $stats['new_inquiries'] = (int)($row['new_inquiries'] ?? 0);
}

$result = $conn->query('SELECT COUNT(*) AS favorites FROM favorites');
if ($result) {
    $stats['favorites'] = (int)($result->fetch_assoc()['favorites'] ?? 0);
}

$result = $conn->query('SELECT COUNT(*) AS users FROM users');
if ($result) {
    $stats['users'] = (int)($result->fetch_assoc()['users'] ?? 0);
}

$result = $conn->query('SELECT COUNT(*) AS orders FROM orders');
if ($result) {
    $stats['orders'] = (int)($result->fetch_assoc()['orders'] ?? 0);
}

$latestProducts = $conn->query('SELECT products.*, categories.name AS category_name
    FROM products
    LEFT JOIN categories ON categories.id = products.category_id
    ORDER BY products.created_at DESC
    LIMIT 5');
$latestProducts = $latestProducts ? $latestProducts->fetch_all(MYSQLI_ASSOC) : [];

$latestInquiries = $conn->query('SELECT inquiries.*, products.name AS product_name
    FROM inquiries
    LEFT JOIN products ON products.id = inquiries.product_id
    ORDER BY inquiries.created_at DESC
    LIMIT 5');
$latestInquiries = $latestInquiries ? $latestInquiries->fetch_all(MYSQLI_ASSOC) : [];

include '../includes/header.php';
?>

<main>
    <section class="admin-list-page">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Admin</p>
                <h1>Dashboard</h1>
            </div>
            <div class="admin-actions">
                <a class="button primary" href="add_product.php">Add Product</a>
                <a class="button secondary" href="inquiries.php">Inquiries</a>
            </div>
        </div>

        <div class="stats-grid">
            <article class="stat-card">
                <span><?php echo $stats['products']; ?></span>
                <p>Total products</p>
            </article>
            <article class="stat-card">
                <span><?php echo $stats['available']; ?></span>
                <p>Available</p>
            </article>
            <article class="stat-card">
                <span><?php echo $stats['sold']; ?></span>
                <p>Sold</p>
            </article>
            <article class="stat-card">
                <span><?php echo $stats['new_inquiries']; ?></span>
                <p>New inquiries</p>
            </article>
            <article class="stat-card">
                <span><?php echo $stats['favorites']; ?></span>
                <p>Saved favorites</p>
            </article>
            <article class="stat-card">
                <span><?php echo $stats['users']; ?></span>
                <p>Users</p>
            </article>
            <article class="stat-card">
                <span><?php echo $stats['orders']; ?></span>
                <p>Orders</p>
            </article>
        </div>

        <div class="dashboard-grid">
            <section class="dashboard-panel">
                <div class="section-heading compact-heading">
                    <h2>Latest products</h2>
                    <a class="back-link" href="products.php">View all</a>
                </div>
                <?php foreach ($latestProducts as $product): ?>
                    <article class="mini-row">
                        <div>
                            <span class="status-pill <?php echo htmlspecialchars($product['status']); ?>"><?php echo ucfirst(htmlspecialchars($product['status'])); ?></span>
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?> &middot; R<?php echo number_format((float)$product['price'], 2); ?></p>
                        </div>
                        <a class="button secondary" href="edit_product.php?id=<?php echo (int)$product['id']; ?>">Edit</a>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="dashboard-panel">
                <div class="section-heading compact-heading">
                    <h2>Latest inquiries</h2>
                    <a class="back-link" href="inquiries.php">View all</a>
                </div>
                <?php foreach ($latestInquiries as $inquiry): ?>
                    <article class="mini-row">
                        <div>
                            <span class="status-pill <?php echo htmlspecialchars($inquiry['status']); ?>"><?php echo ucfirst(htmlspecialchars($inquiry['status'])); ?></span>
                            <h3><?php echo htmlspecialchars($inquiry['product_name'] ?? 'Deleted product'); ?></h3>
                            <p><?php echo htmlspecialchars($inquiry['name']); ?> &middot; <?php echo htmlspecialchars(date('M j, Y H:i', strtotime($inquiry['created_at']))); ?></p>
                        </div>
                        <a class="button secondary" href="inquiries.php">Open</a>
                    </article>
                <?php endforeach; ?>
            </section>
        </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
