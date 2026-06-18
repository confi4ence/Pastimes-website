<?php
// cart.php - View and edit cart contents (session-based)
// Cart is an associative array keyed by clothes_id:
//   $_SESSION['cart'][clothes_id] = [...item fields..., 'cart_qty' => N]
session_start();
require_once 'DBConn.php';

// Allow both logged-in users AND admins
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: /pastimes/login.php");
    exit();
}

$cart = $_SESSION['cart'] ?? [];

// ---- Update quantity for an item ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
    $id  = (int)$_POST['clothes_id'];
    $qty = (int)$_POST['quantity'];

    if (isset($cart[$id])) {
        if ($qty <= 0) {
            unset($_SESSION['cart'][$id]);
        } else {
            // Don't allow more than what's in stock
            $maxStock = $cart[$id]['quantity'];
            $_SESSION['cart'][$id]['cart_qty'] = min($qty, $maxStock);
        }
    }
    header("Location: /pastimes/cart.php");
    exit();
}

// ---- Remove item ----
if (isset($_GET['remove'])) {
    $id = (int)$_GET['remove'];
    if (isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
    }
    header("Location: /pastimes/cart.php");
    exit();
}

// ---- Clear cart ----
if (isset($_GET['clear'])) {
    unset($_SESSION['cart']);
    header("Location: /pastimes/cart.php");
    exit();
}

// Refresh cart after any change above
$cart  = $_SESSION['cart'] ?? [];
$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['cart_qty'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - Pasttime</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .qty-input {
            width: 60px;
            padding: 6px 8px;
            border: 1.5px solid #ddd;
            border-radius: 6px;
            text-align: center;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
        }
        .qty-form { display: flex; align-items: center; gap: 8px; }
    </style>
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
            <a href="/pastimes/cart.php" class="active">Cart <?php if (count($cart) > 0): ?>(<?php echo count($cart); ?>)<?php endif; ?></a>
            <?php if (isset($_SESSION['admin_id'])): ?>
                <a href="/pastimes/admin_orders.php">Orders</a>
            <?php else: ?>
                <a href="/pastimes/order_history.php">Orders</a>
            <?php endif; ?>
            <a href="/pastimes/logout.php" class="btn-nav-logout">Logout</a>
        </nav>
    </div>
</header>

<div class="page-hero">
    <h1>My Cart</h1>
    <p><?php echo count($cart); ?> item<?php echo count($cart) !== 1 ? 's' : ''; ?> in your cart</p>
</div>

<div class="wrapper">

    <?php if (empty($cart)): ?>
        <div class="msg msg-info" style="margin-top:28px;">
            Your cart is empty. <a href="/pastimes/clothes.php">Browse clothes</a> to add items.
        </div>
    <?php else: ?>

        <div class="section-head" style="margin-top:28px;">
            <h2>Cart Items</h2>
            <a href="/pastimes/cart.php?clear=1"
               onclick="return confirm('Clear your entire cart?');"
               class="btn btn-danger btn-sm">Clear Cart</a>
        </div>

        <div class="card" style="padding:0;">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Brand</th>
                            <th>Size</th>
                            <th>Condition</th>
                            <th>Unit Price</th>
                            <th>Quantity</th>
                            <th>Line Total</th>
                            <th>Remove</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1; foreach ($cart as $clothesId => $item): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($item['brand']); ?></td>
                            <td><?php echo htmlspecialchars($item['size']); ?></td>
                            <td><?php echo htmlspecialchars($item['item_condition']); ?></td>
                            <td style="color:var(--gold);font-family:'Playfair Display',serif;font-weight:700;">
                                R<?php echo number_format($item['price'], 2); ?>
                            </td>
                            <td>
                                <form method="POST" class="qty-form">
                                    <input type="hidden" name="update_qty" value="1">
                                    <input type="hidden" name="clothes_id" value="<?php echo $clothesId; ?>">
                                    <input type="number" name="quantity" class="qty-input"
                                           value="<?php echo $item['cart_qty']; ?>"
                                           min="1" max="<?php echo $item['quantity']; ?>">
                                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                </form>
                                <small style="color:#999;">(<?php echo $item['quantity']; ?> in stock)</small>
                            </td>
                            <td style="color:var(--gold);font-family:'Playfair Display',serif;font-weight:700;">
                                R<?php echo number_format($item['price'] * $item['cart_qty'], 2); ?>
                            </td>
                            <td>
                                <a href="/pastimes/cart.php?remove=<?php echo $clothesId; ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Remove this item?');">✕</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:rgba(198,159,213,0.08);">
                            <td colspan="7" style="padding:14px 16px;font-weight:600;font-family:'Playfair Display',serif;">
                                Total
                            </td>
                            <td colspan="2" style="padding:14px 16px;color:var(--gold);font-family:'Playfair Display',serif;font-size:1.2rem;font-weight:700;">
                                R<?php echo number_format($total, 2); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="flex-gap mt-4">
            <a href="/pastimes/clothes.php" class="btn btn-primary">Continue Shopping</a>
            <a href="/pastimes/checkout.php" class="btn btn-gold">Proceed to Checkout</a>
        </div>

    <?php endif; ?>

</div>

<footer>
    &copy; <?php echo date('Y'); ?> <span>Pasttime</span> Clothing Store
</footer>

</body>
</html>