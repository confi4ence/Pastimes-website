<?php

// createTable.php - Recreate tblUser from userData.txt

require_once 'DBConn.php';

$messages = [];
$errors   = [];

// --- Drop and recreate tblUser ---
$conn->query("DROP TABLE IF EXISTS tblUser");

$create = "CREATE TABLE tblUser (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    username    VARCHAR(80)   NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,
    role        ENUM('buyer','seller') NOT NULL DEFAULT 'buyer',
    status      ENUM('pending','approved') NOT NULL DEFAULT 'approved',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($create)) {
    $messages[] = "tblUser created successfully.";
} else {
    $errors[] = "Error creating table: " . $conn->error;
}

// --- Read and insert users from userData.txt ---
$file = 'userData.txt';
if (!file_exists($file)) {
    $errors[] = "userData.txt not found.";
} else {
    $lines   = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $count   = 0;

    $stmt = $conn->prepare(
        "INSERT INTO tblUser (full_name, email, username, password, role, status)
         VALUES (?, ?, ?, ?, ?, 'approved')"
    );

    foreach ($lines as $line) {
        $parts = explode(',', $line);
        if (count($parts) < 5) continue;

        [$full_name, $email, $username, $plain_pw, $role] = array_map('trim', $parts);
        $hashed = password_hash($plain_pw, PASSWORD_DEFAULT);

        $stmt->bind_param("sssss", $full_name, $email, $username, $hashed, $role);
        if ($stmt->execute()) {
            $count++;
        } else {
            $errors[] = "Failed to insert $full_name: " . $stmt->error;
        }
    }

    $stmt->close();
    $messages[] = "$count user(s) imported from userData.txt.";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Table - Pasttime</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
            </div>
            <h1>Database Setup</h1>
            <p>Pasttime Clothing Store</p>
        </div>

        <?php foreach ($messages as $msg): ?>
            <div class="msg msg-success"><?php echo htmlspecialchars($msg); ?></div>
        <?php endforeach; ?>

        <?php foreach ($errors as $err): ?>
            <div class="msg msg-error"><?php echo htmlspecialchars($err); ?></div>
        <?php endforeach; ?>

        <?php if (empty($errors)): ?>
            <div class="msg msg-info">All done! tblUser has been recreated and populated.</div>
        <?php endif; ?>

        <div class="mt-4 flex-gap">
            <a href="login.php"       class="btn btn-primary">Go to Login</a>
            <a href="admin_login.php" class="btn btn-dark">Admin Login</a>
        </div>
    </div>
</div>
</body>
</html>