<?php
global $conn;
include 'includes/db.php';
include 'includes/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = '';

if (($_GET['pending'] ?? '') === '1') {
    $message = 'Your account has been created and is waiting for administrator verification.';
    $messageType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!verify_csrf()) {
        $message = 'Security check failed. Please try again.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $message = 'Please enter your email and password.';
        $messageType = 'error';
    } else {
        $stmt = $conn->prepare('SELECT id, name, email, password, role, verification_status FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            if (($user['role'] ?? '') !== 'admin' && ($user['verification_status'] ?? 'pending') !== 'verified') {
                $message = 'Your account is still waiting for administrator verification.';
                $messageType = 'error';
            } else {
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'verification_status' => $user['verification_status'],
            ];

            header('Location: index.php');
            exit;
            }
        }

        if (!$message) {
            $message = 'The email or password is incorrect.';
            $messageType = 'error';
        }
    }
}

include 'includes/header.php';
?>

<main>
    <section class="auth-page">
        <div class="auth-intro">
            <p class="eyebrow">Welcome back</p>
            <h1>Login to Pastimes</h1>
            <p>Sign in to add new stock and continue managing your thrift store listings.</p>
        </div>

        <form class="auth-form" method="POST">
            <?php echo csrf_field(); ?>
            <?php if ($message): ?>
                <div class="form-message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <label>
                <span>Email</span>
                <input
                    type="email"
                    name="email"
                    placeholder="you@example.com"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                >
            </label>

            <label>
                <span>Password</span>
                <input type="password" name="password" required>
            </label>

            <button type="submit">Login</button>
            <p class="auth-switch">Need an account? <a href="register.php">Register</a></p>
        </form>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
