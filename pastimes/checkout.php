<?php

// checkout.php - Processes the cart into a real order
// - Redirects to login if not logged in
// - Generates an order reference and uses the PHP session ID
// - Writes one row to tblAorder and one row per item to tblOrderLine
// - Decrements stock quantity in tblClothes
// - Empties the cart afterwards
session_start();
require_once 'DBConn.php';
require_once 'includes/functions.php';

// ---- Must be logged in as a user to checkout ----
if (!isset($_SESSION['user_id'])) {
    $_SESSION['checkout_redirect_msg'] = "Please log in or register to complete your order.";
    header("Location: /pastimes/login.php");
    exit();
}

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header("Location: /pastimes/cart.php");
    exit();
}

$userId    = $_SESSION['user_id'];
$sessionId = session_id();
$orderRef  = "ORD-" . date('Ymd') . "-" . strtoupper(substr(uniqid(), -6));

// Calculate total
$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['cart_qty'];
}

$conn->begin_transaction();
$success = true;
$errorMsg = '';

try {
    // 1. Insert into tblAorder
    $ostmt = $conn->prepare(
        "INSERT INTO tblAorder (user_id, order_ref, session_id, total_price, status) VALUES (?,?,?,?,'pending')"
    );
    $ostmt->bind_param("issd", $userId, $orderRef, $sessionId, $total);
    if (!$ostmt->execute()) {
        throw new Exception("Could not create order: " . $ostmt->error);
    }
    $orderId = $conn->insert_id;
    $ostmt->close();

    // 2. Insert each cart item into tblOrderLine and decrement stock
    $lstmt = $conn->prepare(
        "INSERT INTO tblOrderLine (order_id, clothes_id, quantity, unit_price, line_total) VALUES (?,?,?,?,?)"
    );
    $ustmt = $conn->prepare(
        "UPDATE tblClothes SET quantity = quantity - ? WHERE clothes_id = ? AND quantity >= ?"
    );

    foreach ($cart as $clothesId => $item) {
        $qty       = (int)$item['cart_qty'];
        $unitPrice = $item['price'];
        $lineTotal = $unitPrice * $qty;

        $lstmt->bind_param("iiidd", $orderId, $clothesId, $qty, $unitPrice, $lineTotal);
        if (!$lstmt->execute()) {
            throw new Exception("Could not save order line for " . $item['item_name']);
        }

        // Decrement stock — only if enough stock is still available
        $ustmt->bind_param("iii", $qty, $clothesId, $qty);
        $ustmt->execute();
        if ($ustmt->affected_rows === 0) {
            throw new Exception("Not enough stock for " . $item['item_name']);
        }
    }
    $lstmt->close();
    $ustmt->close();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $success = false;
    $errorMsg = $e->getMessage();
}

// Empty the cart after a successful checkout
if ($success) {
    unset($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Pasttime</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <div class="nav-inner">
        <span class="nav-brand">Past<span>times</span></span>
        <nav class="nav-links">
            <a href="/pastimes/user_dashboard.php">Dashboard</a>
            <a href="/pastimes/clothes.php">Browse</a>
            <a href="/pastimes/order_history.php">Orders</a>
            <a href="/pastimes/logout.php" class="btn-nav-logout">Logout</a>
        </nav>
    </div>
</header>

<div class="page-hero">
    <h1><?php echo $success ? 'Order Confirmed' : 'Checkout Failed'; ?></h1>
    <p><?php echo $success ? 'Thank you for shopping at Pasttime' : 'Something went wrong'; ?></p>
</div>

<div class="wrapper">
    <?php if ($success): ?>
        <div class="card card-gold" style="margin-top:28px; text-align:center;">
            <div style="font-size:3rem; margin-bottom:8px;">✅</div>
            <h2>Your order has been placed!</h2>
            <p style="color:#888; margin:8px 0 20px;">A confirmation has been recorded against your account.</p>

            <div style="background:var(--bg); border-radius:10px; padding:20px; text-align:left; max-width:420px; margin:0 auto 20px;">
                <div style="display:grid; grid-template-columns:auto 1fr; gap:8px 16px; font-size:0.95rem;">
                    <span style="color:#888;">Order Reference:</span>
                    <strong><?php echo htmlspecialchars($orderRef); ?></strong>

                    <span style="color:#888;">Session ID:</span>
                    <code style="font-size:0.78rem; word-break:break-all;"><?php echo htmlspecialchars($sessionId); ?></code>

                    <span style="color:#888;">Total Paid:</span>
                    <strong style="color:var(--gold); font-family:'Playfair Display',serif; font-size:1.2rem;">
                        R<?php echo number_format($total, 2); ?>
                    </strong>

                    <span style="color:#888;">Status:</span>
                    <span class="badge badge-pending">Pending</span>
                </div>
            </div>

            <div class="flex-gap" style="justify-content:center;">
                <a href="/pastimes/order_history.php" class="btn btn-primary">View My Orders</a>
                <a href="/pastimes/clothes.php" class="btn btn-dark">Continue Shopping</a>
            </div>
        </div>
    <?php else: ?>
        <div class="msg msg-error" style="margin-top:28px;">
            <?php echo htmlspecialchars($errorMsg); ?>
        </div>
        <a href="/pastimes/cart.php" class="btn btn-primary mt-4">Back to Cart</a>
    <?php endif; ?>
</div>

<footer>
    &copy; <?php echo date('Y'); ?> <span>Pasttime</span> Clothing Store
</footer>

</body>
</html>