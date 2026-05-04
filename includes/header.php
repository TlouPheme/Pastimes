<?php
include_once __DIR__ . '/auth.php';

$basePath = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? '../' : '';
$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pastimes Thrift Store</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/styles.css">
</head>
<body>

<header class="site-header">
    <a class="brand" href="<?php echo $basePath; ?>index.php" aria-label="Pastimes home">
        <span class="brand-mark">P</span>
        <span>Pastimes</span>
    </a>
    <nav class="site-nav" aria-label="Primary navigation">
        <a href="<?php echo $basePath; ?>index.php">Shop</a>
        <?php if (is_admin()): ?>
            <a href="<?php echo $basePath; ?>admin/dashboard.php">Dashboard</a>
            <a href="<?php echo $basePath; ?>admin/add_product.php">Add Product</a>
            <a href="<?php echo $basePath; ?>admin/products.php">Manage Products</a>
            <a href="<?php echo $basePath; ?>admin/categories.php">Categories</a>
            <a href="<?php echo $basePath; ?>admin/inquiries.php">Inquiries</a>
            <a href="<?php echo $basePath; ?>admin/orders.php">Orders</a>
            <a href="<?php echo $basePath; ?>admin/users.php">Users</a>
        <?php endif; ?>
        <?php if ($user): ?>
            <?php if (!is_admin()): ?>
                <a href="<?php echo $basePath; ?>account.php">Account</a>
                <a href="<?php echo $basePath; ?>favorites.php">Favorites</a>
                <a href="<?php echo $basePath; ?>cart.php">Cart</a>
            <?php endif; ?>
            <span class="nav-user">Hi, <?php echo htmlspecialchars($user['name']); ?></span>
            <a href="<?php echo $basePath; ?>logout.php">Logout</a>
        <?php else: ?>
            <a href="<?php echo $basePath; ?>login.php">Login</a>
            <a href="<?php echo $basePath; ?>register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>
