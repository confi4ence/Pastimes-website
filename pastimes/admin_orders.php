<?php
// admin_orders.php — Admin: view all orders placed by buyers and update
// their status (pending → confirmed → shipped → delivered / cancelled).
// This is what "Orders" in the admin nav links to (admins don't have
// their own personal order history — they manage everyone else's).
session_start();
require_once 'DBConn.php';
require_once 'includes/functions.php';

require_admin();

$msg = '';

// ---- Handle status update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $orderId = (int)$_POST['order_id'];
    $status  = clean_input($_POST['status'] ?? 'pending');
    $allowed = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
    if (in_array($status, $allowed, true)) {
        $stmt = $conn->prepare("UPDATE tblAorder SET status=? WHERE order_id=?");
        $stmt->bind_param("si", $status, $orderId);
        $msg = $stmt->execute() ? "Order status updated to \"" . ucfirst($status) . "\"." : "Error: " . $stmt->error;
        $stmt->close();
    }
}

// ---- Fetch all orders with buyer info ----
$ordersResult = $conn->query(
    "SELECT o.*, u.full_name AS buyer_name, u.email AS buyer_email, u.user_id AS buyer_id
     FROM tblAorder o
     JOIN tblUser u ON o.user_id = u.user_id
     ORDER BY o.order_date DESC"
);

$ordersArr = [];
$statusCounts = ['pending' => 0, 'confirmed' => 0, 'shipped' => 0, 'delivered' => 0, 'cancelled' => 0];

if ($ordersResult) {
    while ($order = $ordersResult->fetch_assoc()) {
        $linesStmt = $conn->prepare(
            "SELECT ol.quantity, ol.unit_price, ol.line_total, c.item_name, c.brand, c.seller_id, s.full_name AS seller_name
             FROM tblOrderLine ol
             JOIN tblClothes c ON ol.clothes_id = c.clothes_id
             LEFT JOIN tblUser s ON c.seller_id = s.user_id
             WHERE ol.order_id = ?"
        );
        $linesStmt->bind_param("i", $order['order_id']);
        $linesStmt->execute();
        $linesResult = $linesStmt->get_result();

        $lines = [];
        while ($line = $linesResult->fetch_assoc()) {
            $lines[] = $line;
        }
        $linesStmt->close();

        $order['lines'] = $lines;
        $ordersArr[] = $order;
        if (isset($statusCounts[$order['status']])) $statusCounts[$order['status']]++;
    }
}

$totalOrders = count($ordersArr);
$totalRevenue = array_sum(array_column($ordersArr, 'total_price'));
$admin_name = htmlspecialchars($_SESSION['admin_full_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Pasttime Admin</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <div class="nav-inner">
        <span class="nav-brand">Past<span>times</span> <small style="font-size:0.65rem;color:var(--gold);font-family:'DM Sans',sans-serif;letter-spacing:1px;">ADMIN</small></span>
        <nav class="nav-links">
            <a href="/pastimes/admin_dashboard.php">Users</a>
            <a href="/pastimes/admin_clothes.php">Clothes</a>
            <a href="/pastimes/admin_orders.php" class="active">Orders</a>
            <a href="/pastimes/admin_messages.php">Messages</a>
            <a href="/pastimes/clothes.php">View Store</a>
            <a href="/pastimes/logout.php" class="btn-nav-logout">Logout</a>
        </nav>
    </div>
</header>

<div class="page-hero">
    <h1>Manage Orders</h1>
    <p>Welcome, <?php echo $admin_name; ?> — track deliveries for every buyer</p>
</div>

<div class="wrapper">

    <?php if ($msg): ?>
        <div class="msg msg-success" style="margin-top:20px;"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row" style="margin-top: 24px;">
        <div class="stat-card">
            <div class="stat-num"><?php echo $totalOrders; ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card gold">
            <div class="stat-num">R<?php echo number_format($totalRevenue, 2); ?></div>
            <div class="stat-label">Total Purchases (Revenue)</div>
        </div>
        <div class="stat-card dark">
            <div class="stat-num"><?php echo $statusCounts['shipped']; ?></div>
            <div class="stat-label">In Transit</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?php echo $statusCounts['delivered']; ?></div>
            <div class="stat-label">Delivered</div>
        </div>
    </div>

    <div class="section-head">
        <h2>All Orders</h2>
    </div>

    <?php if (empty($ordersArr)): ?>
        <div class="msg msg-info">No orders have been placed yet.</div>
    <?php else: ?>

        <?php foreach ($ordersArr as $order): ?>
        <div class="card" style="margin-top:18px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:14px; margin-bottom:16px;">
                <div>
                    <h3 style="margin-bottom:4px;"><?php echo htmlspecialchars($order['order_ref']); ?></h3>
                    <p style="color:#888; font-size:0.85rem;">
                        Placed on <?php echo date('d F Y, H:i', strtotime($order['order_date'])); ?>
                        by <strong><?php echo htmlspecialchars($order['buyer_name']); ?></strong>
                        (<?php echo htmlspecialchars($order['buyer_email']); ?>)
                    </p>
                </div>
                <span class="badge badge-<?php echo $order['status'] === 'delivered' ? 'approved' : ($order['status'] === 'cancelled' ? 'pending' : 'seller'); ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Brand</th>
                            <th>Seller</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($order['lines'] as $line): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($line['item_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($line['brand']); ?></td>
                            <td><?php echo htmlspecialchars($line['seller_name'] ?? '—'); ?></td>
                            <td><?php echo $line['quantity']; ?></td>
                            <td>R<?php echo number_format($line['unit_price'], 2); ?></td>
                            <td style="color:var(--gold); font-weight:700;">R<?php echo number_format($line['line_total'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; margin-top:14px; padding-top:14px; border-top:1px solid #eee;">
                <div>
                    <span style="color:#888; margin-right:10px;">Order Total:</span>
                    <span style="font-family:'Playfair Display',serif; font-size:1.2rem; color:var(--gold); font-weight:700;">
                        R<?php echo number_format($order['total_price'], 2); ?>
                    </span>
                </div>

                <div class="flex-gap">
                    <form method="POST" class="flex-gap" style="align-items:center;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                        <select name="status" style="padding:8px 12px;border:1.5px solid #ddd;border-radius:7px;font-family:'DM Sans',sans-serif;">
                            <?php foreach (['pending','confirmed','shipped','delivered','cancelled'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $order['status'] === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-gold btn-sm">Update Status</button>
                    </form>
                    <a href="/pastimes/admin_messages.php?user_id=<?php echo $order['buyer_id']; ?>&order_id=<?php echo $order['order_id']; ?>&subject=<?php echo urlencode('Regarding order ' . $order['order_ref']); ?>"
                       class="btn btn-dark btn-sm">Message Buyer</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    <?php endif; ?>

</div>

<footer>
    &copy; <?php echo date('Y'); ?> <span>Pasttime</span> Clothing Store — Admin Panel
</footer>

</body>
</html>
