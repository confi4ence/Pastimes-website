<?php
// order_history.php - Shows a user's purchase history
// Reads tblAorder joined with tblOrderLine and tblClothes
// Shows a grand total of all purchases at the bottom
session_start();
require_once 'DBConn.php';
require_once 'includes/functions.php';

// Admins manage everyone's orders on a dedicated page, not a personal history
if (isset($_SESSION['admin_id'])) {
    redirect('/pastimes/admin_orders.php');
}

require_login();

$userId = $_SESSION['user_id'];

// Fetch all orders for this user
$ordersResult = $conn->prepare(
    "SELECT order_id, order_ref, session_id, total_price, order_date, status
     FROM tblAorder WHERE user_id = ? ORDER BY order_date DESC"
);
$ordersResult->bind_param("i", $userId);
$ordersResult->execute();
$orders = $ordersResult->get_result();

$ordersArr = [];
$grandTotal = 0;

while ($order = $orders->fetch_assoc()) {
    // Fetch line items for this order
    $linesStmt = $conn->prepare(
        "SELECT ol.quantity, ol.unit_price, ol.line_total, c.item_name, c.brand, c.image_path
         FROM tblOrderLine ol
         JOIN tblClothes c ON ol.clothes_id = c.clothes_id
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
    $grandTotal += $order['total_price'];
}
$ordersResult->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Pasttime</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <div class="nav-inner">
        <span class="nav-brand">Past<span>times</span></span>
        <nav class="nav-links">
            <?php if (isset($_SESSION['admin_id'])): ?>
                <a href="/pastimes/admin_dashboard.php">Admin Dashboard</a>
            <?php else: ?>
                <a href="/pastimes/user_dashboard.php">Dashboard</a>
            <?php endif; ?>
            <a href="/pastimes/clothes.php">Browse</a>
            <?php if (($_SESSION['role'] ?? '') === 'seller'): ?>
                <a href="/pastimes/sell.php">Sell Item</a>
            <?php endif; ?>
            <a href="/pastimes/cart.php">Cart</a>
            <a href="/pastimes/order_history.php" class="active">Orders</a>
            <a href="/pastimes/messages.php">Messages</a>
            <a href="/pastimes/logout.php" class="btn-nav-logout">Logout</a>
        </nav>
    </div>
</header>

<div class="page-hero">
    <h1>Order History</h1>
    <p><?php echo count($ordersArr); ?> order<?php echo count($ordersArr) !== 1 ? 's' : ''; ?> placed</p>
</div>

<div class="wrapper">

    <?php if (empty($ordersArr)): ?>
        <div class="msg msg-info" style="margin-top:28px;">
            You haven't placed any orders yet. <a href="/pastimes/clothes.php">Browse clothes</a> to get started.
        </div>
    <?php else: ?>

        <?php foreach ($ordersArr as $order): ?>
        <div class="card" style="margin-top:24px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:10px; margin-bottom:16px;">
                <div>
                    <h3 style="margin-bottom:4px;"><?php echo htmlspecialchars($order['order_ref']); ?></h3>
                    <p style="color:#888; font-size:0.85rem;">
                        Placed on <?php echo date('d F Y, H:i', strtotime($order['order_date'])); ?>
                    </p>
                </div>
                <span class="badge badge-<?php echo $order['status'] === 'delivered' ? 'approved' : 'pending'; ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Brand</th>
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
                            <td><?php echo $line['quantity']; ?></td>
                            <td>R<?php echo number_format($line['unit_price'], 2); ?></td>
                            <td style="color:var(--gold); font-weight:700;">R<?php echo number_format($line['line_total'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="text-align:right; margin-top:12px; padding-top:12px; border-top:1px solid #eee;">
                <span style="color:#888; margin-right:10px;">Order Total:</span>
                <span style="font-family:'Playfair Display',serif; font-size:1.2rem; color:var(--gold); font-weight:700;">
                    R<?php echo number_format($order['total_price'], 2); ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Grand total of all purchases -->
        <div class="card card-gold" style="margin-top:28px; text-align:right;">
            <span style="color:#888; font-size:1rem; margin-right:14px;">Total of All Purchases:</span>
            <span style="font-family:'Playfair Display',serif; font-size:1.6rem; color:var(--gold); font-weight:700;">
                R<?php echo number_format($grandTotal, 2); ?>
            </span>
        </div>

    <?php endif; ?>

</div>

<footer>
    &copy; <?php echo date('Y'); ?> <span>Pasttime</span> Clothing Store
</footer>

</body>
</html>