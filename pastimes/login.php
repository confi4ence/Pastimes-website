<?php

// login.php - User login
session_start();
require_once 'DBConn.php';
require_once 'includes/functions.php';

if (is_logged_in()) redirect('/pastimes/user_dashboard.php');

$error    = '';
$warning  = '';
$username = '';
$email    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_input($_POST['username'] ?? '');
    $email    = clean_input($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$email || !$password) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare(
            "SELECT user_id, full_name, password, status, role
             FROM tblUser WHERE username=? AND email=?"
        );
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "No account found with those credentials.";
        } else {
            $user = $result->fetch_assoc();

            if ($user['status'] !== 'approved') {
                $warning = "Your account is still pending admin approval. Please check back later.";
            } elseif (!password_verify($password, $user['password'])) {
                $error = "Incorrect password. Please try again.";
            } else {
                // Successful login
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];

                // If the user was sent here from checkout, send them back to finish
                if (!empty($_SESSION['cart'])) {
                    unset($_SESSION['checkout_redirect_msg']);
                    redirect('/pastimes/checkout.php');
                }
                redirect('/pastimes/user_dashboard.php');
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pasttime</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
            </div>
            <h1>Welcome Back</h1>
            <p>Sign in to Pasttime</p>
        </div>

        <?php if ($error):   ?><div class="msg msg-error"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($warning): ?><div class="msg msg-warning"><?php echo $warning; ?></div><?php endif; ?>
        <?php if (!empty($_SESSION['checkout_redirect_msg'])): ?>
            <div class="msg msg-info"><?php echo htmlspecialchars($_SESSION['checkout_redirect_msg']); unset($_SESSION['checkout_redirect_msg']); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       value="<?php echo htmlspecialchars($username); ?>"
                       placeholder="Your username" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       value="<?php echo htmlspecialchars($email); ?>"
                       placeholder="Your email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Your password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="register.php">Register here</a>
            <br>
            <a href="admin_login.php" style="color:#888;">Admin login →</a>
        </div>
    </div>
</div>
</body>
</html>