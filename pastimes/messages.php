<?php
// messages.php - User view of messages from/to admin
session_start();
require_once 'DBConn.php';
require_once 'includes/functions.php';

require_login();

$userId = $_SESSION['user_id'];
$msg = '';

// Send a reply to admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply') {
    $subject = clean_input($_POST['subject'] ?? '');
    $message = clean_input($_POST['message'] ?? '');

    $stmt = $conn->prepare(
        "INSERT INTO tblMessage (sender_type, user_id, subject, message, is_read) VALUES ('user', ?, ?, ?, 0)"
    );
    $stmt->bind_param("iss", $userId, $subject, $message);
    $msg = $stmt->execute() ? "Message sent to admin." : "Error: " . $stmt->error;
    $stmt->close();
}

// Mark all unread admin messages as read for this user
$conn->query("UPDATE tblMessage SET is_read = 1 WHERE user_id = $userId AND sender_type = 'admin'");

// Fetch all messages for this user
$stmt = $conn->prepare("SELECT * FROM tblMessage WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$messages = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Pasttime</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <div class="nav-inner">
        <span class="nav-brand">Past<span>times</span></span>
        <nav class="nav-links">
            <a href="/pastimes/user_dashboard.php">Dashboard</a>
            <a href="/pastimes/clothes.php">Browse</a>
            <?php if (($_SESSION['role'] ?? '') === 'seller'): ?>
                <a href="/pastimes/sell.php">Sell Item</a>
            <?php endif; ?>
            <a href="/pastimes/order_history.php">Orders</a>
            <a href="/pastimes/messages.php" class="active">Messages</a>
            <a href="/pastimes/logout.php" class="btn-nav-logout">Logout</a>
        </nav>
    </div>
</header>

<div class="page-hero">
    <h1>Messages</h1>
    <p>Communication with Pasttime admin</p>
</div>

<div class="wrapper">

    <?php if ($msg): ?>
        <div class="msg msg-success" style="margin-top:20px;"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <!-- Send Message Form -->
    <div class="section-head" style="margin-top:24px;">
        <h2>Message Admin</h2>
    </div>

    <div class="card">
        <form method="POST" action="messages.php">
            <input type="hidden" name="action" value="reply">

            <div class="form-group">
                <label>Subject</label>
                <input type="text" name="subject" required placeholder="e.g. Question about my order">
            </div>

            <div class="form-group">
                <label>Message</label>
                <textarea name="message" rows="3" required placeholder="Type your message here..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Send to Admin</button>
        </form>
    </div>

    <!-- Message History -->
    <div class="section-head" style="margin-top:32px;">
        <h2>Message History</h2>
    </div>

    <?php if ($messages->num_rows === 0): ?>
        <div class="msg msg-info">No messages yet.</div>
    <?php else: ?>
        <?php while ($row = $messages->fetch_assoc()): ?>
            <div class="card" style="margin-top:14px; border-top-color: <?php echo $row['sender_type'] === 'admin' ? 'var(--gold)' : 'var(--primary)'; ?>;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <span class="badge <?php echo $row['sender_type'] === 'admin' ? 'badge-seller' : 'badge-buyer'; ?>">
                        <?php echo $row['sender_type'] === 'admin' ? 'From Admin' : 'You sent'; ?>
                    </span>
                    <span style="color:#888; font-size:0.8rem;"><?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?></span>
                </div>
                <h3 style="margin-bottom:6px;"><?php echo htmlspecialchars($row['subject']); ?></h3>
                <p style="color:#555;"><?php echo htmlspecialchars($row['message']); ?></p>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

</div>

<footer>
    &copy; <?php echo date('Y'); ?> <span>Pasttime</span> Clothing Store
</footer>

</body>
</html>