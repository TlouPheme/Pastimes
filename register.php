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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!verify_csrf()) {
        $message = 'Security check failed. Please try again.';
        $messageType = 'error';
    } elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        $message = 'Please enter your name, a valid email, and a password with at least 6 characters.';
        $messageType = 'error';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match.';
        $messageType = 'error';
    } else {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $existingUser = $stmt->get_result()->fetch_assoc();

        if ($existingUser) {
            $message = 'An account with that email already exists.';
            $messageType = 'error';
        } else {
            $result = $conn->query('SELECT COUNT(*) AS total FROM users');
            $userCount = (int)(($result ? $result->fetch_assoc() : ['total' => 0])['total'] ?? 0);
            $role = $userCount === 0 ? 'admin' : 'customer';
            $verificationStatus = $role === 'admin' ? 'verified' : 'pending';
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (name, email, password, role, verification_status) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('sssss', $name, $email, $passwordHash, $role, $verificationStatus);

            if ($stmt->execute()) {
                if ($role === 'admin') {
                    $_SESSION['user'] = [
                        'id' => $stmt->insert_id,
                        'name' => $name,
                        'email' => $email,
                        'role' => $role,
                        'verification_status' => $verificationStatus,
                    ];

                    header('Location: index.php');
                } else {
                    header('Location: login.php?pending=1');
                }

                exit;
            }

            $message = 'Your account could not be created. Please try again.';
            $messageType = 'error';
        }
    }
}

include 'includes/header.php';
?>

<main>
    <section class="auth-page">
        <div class="auth-intro">
            <p class="eyebrow">Join Pastimes</p>
            <h1>Create your account</h1>
            <p>Register to save your store activity and browse thrift finds with your own account.</p>
        </div>

        <form class="auth-form" method="POST">
            <?php echo csrf_field(); ?>
            <?php if ($message): ?>
                <div class="form-message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <label>
                <span>Name</span>
                <input
                    type="text"
                    name="name"
                    placeholder="Your name"
                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                    required
                >
            </label>

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
                <input type="password" name="password" minlength="6" required>
            </label>

            <label>
                <span>Confirm password</span>
                <input type="password" name="confirm_password" minlength="6" required>
            </label>

            <button type="submit">Create Account</button>
            <p class="auth-switch">Already registered? <a href="login.php">Login</a></p>
        </form>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
