<?php

// admin_login.php — Admin login

session_start();
require_once 'DBConn.php';
require_once 'includes/functions.php';

if (is_admin_logged_in()) redirect('/pastimes/admin_dashboard.php');

$error    = '';
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
            "SELECT admin_id, full_name, password FROM tblAdmin WHERE username=? AND email=?"
        );
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "No admin account found with those credentials.";
        } else {
            $admin = $result->fetch_assoc();
            if (!password_verify($password, $admin['password'])) {
                $error = "Incorrect password.";
            } else {
                $_SESSION['admin_id']        = $admin['admin_id'];
                $_SESSION['admin_full_name'] = $admin['full_name'];
                $_SESSION['is_admin']        = true;
                redirect('/pastimes/admin_dashboard.php');
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
    <title>Admin Login - Pasttime</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card admin">
        <div class="auth-logo">
            <div class="logo-icon" style="background: #c9a84c;">
                <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            </div>
            <h1>Admin Portal</h1>
            <p>Pasttime Clothing Store</p>
        </div>

        <?php if ($error): ?>
            <div class="msg msg-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="admin_login.php">
            <div class="form-group">
                <label for="username">Admin Username</label>
                <input type="text" id="username" name="username"
                       value="<?php echo htmlspecialchars($username); ?>"
                       placeholder="Admin username" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       value="<?php echo htmlspecialchars($email); ?>"
                       placeholder="Admin email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Admin password" required>
            </div>

            <button type="submit" class="btn btn-gold btn-block">Sign In as Admin</button>
        </form>

        <div class="auth-footer">
            <a href="login.php">← Back to user login</a>
        </div>
    </div>
</div>
</body>
</html>