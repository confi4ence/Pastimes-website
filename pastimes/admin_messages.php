<?php
// ===================================================
// admin_messages.php — Admin sends/views messages to/from
// sellers and buyers. Stored in tblMessage.
// ===================================================
session_start();
require_once 'DBConn.php';
require_once 'includes/functions.php';

require_admin();

$msg = '';

// Send a new message to a user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    $userId  = (int)$_POST['user_id'];
    $orderId = !empty($_POST['order_id']) ? (int)$_POST['order_id'] : null;
    $subject = clean_input($_POST['subject'] ?? '');
    $message = clean_input($_POST['message'] ?? '');

    $stmt = $conn->prepare(
        "INSERT INTO tblMessage (sender_type, user_id, order_id, subject, message, is_read) VALUES ('admin', ?, ?, ?, ?, 0)"
    );
    $stmt->bind_param("iiss", $userId, $orderId, $subject, $message);
    $msg = $stmt->execute() ? "Message sent successfully." : "Error: " . $stmt->error;
    $stmt->close();
}

// Fetch all messages with the user's name
$messages = $conn->query(
    "SELECT m.*, u.full_name, u.role
     FROM tblMessage m
     JOIN tblUser u ON m.user_id = u.user_id
     ORDER BY m.created_at DESC"
);

// Fetch users for the dropdown
$users = $conn->query("SELECT user_id, full_name, role FROM tblUser WHERE status='approved' ORDER BY full_name");
$userList = [];
if ($users) {
    while ($row = $users->fetch_assoc()) $userList[] = $row;
}

// Optional prefill via query string, e.g. linked from Orders or Clothes pages
$prefill_user_id  = (int)($_GET['user_id'] ?? 0);
$prefill_order_id = clean_input($_GET['order_id'] ?? '');
$prefill_subject  = clean_input($_GET['subject'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Pasttime Admin</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <div class="nav-inner">
        <span class="nav-brand">Past<span>times</span> <small style="font-size:0.65rem;color:var(--gold);font-family:'DM Sans',sans-serif;letter-spacing:1px;">ADMIN</small></span>
        <nav class="nav-links">
            <a href="/pastimes/admin_dashboard.php">Users</a>
            <a href="/pastimes/admin_clothes.php">Clothes</a>
            <a href="/pastimes/admin_orders.php">Orders</a>
            <a href="/pastimes/admin_messages.php" class="active">Messages</a>
            <a href="/pastimes/clothes.php">View Store</a>
            <a href="/pastimes/logout.php" class="btn-nav-logout">Logout</a>
        </nav>
    </div>
</header>

<div class="page-hero">
    <h1>Messages</h1>
    <p>Communicate with sellers and buyers</p>
</div>

<div class="wrapper">

    <?php if ($msg): ?>
        <div class="msg msg-success" style="margin-top:20px;"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <!-- Send Message Form -->
    <div class="section-head" style="margin-top:24px;">
        <h2>Send a Message</h2>
    </div>

    <div class="card">
        <form method="POST" action="admin_messages.php">
            <input type="hidden" name="action" value="send">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>Send To</label>
                    <select name="user_id" required>
                        <option value="">Select a user...</option>
                        <?php foreach ($userList as $u): ?>
                            <option value="<?php echo $u['user_id']; ?>" <?php echo $prefill_user_id === (int)$u['user_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['full_name']); ?> (<?php echo ucfirst($u['role']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Related Order ID (optional)</label>
                    <input type="number" name="order_id" placeholder="e.g. 3" value="<?php echo htmlspecialchars($prefill_order_id); ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Subject</label>
                <input type="text" name="subject" required placeholder="e.g. Item Condition Check" value="<?php echo htmlspecialchars($prefill_subject); ?>">
            </div>

            <div class="form-group">
                <label>Message</label>
                <textarea name="message" rows="3" required placeholder="Type your message here..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Send Message</button>
        </form>
    </div>

    <!-- All Messages -->
    <div class="section-head" style="margin-top:32px;">
        <h2>Message History</h2>
    </div>

    <div class="card" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>From</th><th>To</th><th>Subject</th><th>Message</th><th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $messages->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <span class="badge <?php echo $row['sender_type'] === 'admin' ? 'badge-seller' : 'badge-buyer'; ?>">
                                <?php echo $row['sender_type'] === 'admin' ? 'Admin' : ucfirst($row['role']); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $row['sender_type'] === 'admin' ? htmlspecialchars($row['full_name']) : 'Admin'; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($row['subject']); ?></strong></td>
                        <td style="max-width:300px;"><?php echo htmlspecialchars($row['message']); ?></td>
                        <td><?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<footer>
    &copy; <?php echo date('Y'); ?> <span>Pasttime</span> Clothing Store — Admin Panel
</footer>

</body>
</html>