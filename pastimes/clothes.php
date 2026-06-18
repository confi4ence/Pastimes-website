<?php
// clothes.php - Browse clothing items
session_start();
require_once 'DBConn.php';
require_once 'includes/functions.php';

// Allow both logged-in users AND admins to view clothes
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: /pastimes/login.php");
    exit();
}

// Fetch all available clothes into array
$result = $conn->query(
    "SELECT c.*, u.full_name AS seller_name
     FROM tblClothes c
     LEFT JOIN tblUser u ON c.seller_id = u.user_id
     WHERE c.status = 'available'
     ORDER BY c.clothes_id DESC"
);

$clothes = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $clothes[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Clothes - Pasttime</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Cart popup overlay */
        .popup-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(45,45,45,0.55);
            z-index: 200;
            align-items: center;
            justify-content: center;
        }
        .popup-overlay.open { display: flex; }

        .popup-box {
            background: #fff;
            border-radius: 14px;
            padding: 36px;
            max-width: 360px;
            width: 90%;
            text-align: center;
            box-shadow: 0 12px 40px rgba(45,45,45,0.25);
            animation: popIn 0.2s ease;
        }

        @keyframes popIn {
            from { transform: scale(0.88); opacity: 0; }
            to   { transform: scale(1);    opacity: 1; }
        }

        .popup-icon {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, var(--primary), #a97dbf);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
        }

        .popup-icon svg { width: 26px; height: 26px; fill: #fff; }

        .popup-box h3 { margin-bottom: 6px; }
        .popup-item  { color: #666; font-size: 0.9rem; margin-bottom: 16px; }
        .popup-price {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: var(--gold);
            font-weight: 700;
            margin-bottom: 20px;
        }

        .popup-price small { font-size: 1rem; color: #aaa; font-family: 'DM Sans', sans-serif; }

        .popup-actions { display: flex; gap: 10px; justify-content: center; }
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
            <a href="/pastimes/clothes.php" class="active">Browse Clothes</a>
            <?php if (($_SESSION['role'] ?? '') === 'seller'): ?>
                <a href="/pastimes/sell.php">Sell Item</a>
            <?php endif; ?>
            <a href="/pastimes/cart.php">Cart <?php $cc = count($_SESSION['cart'] ?? []); if ($cc > 0) echo "($cc)"; ?></a>
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
    <h1>Browse Clothing</h1>
    <p><?php echo count($clothes); ?> item<?php echo count($clothes) !== 1 ? 's' : ''; ?> available</p>
</div>

<div class="wrapper">

    <?php if (isset($_SESSION['cart_msg'])): ?>
        <div class="msg msg-success" style="margin-top:20px;">
            <?php echo htmlspecialchars($_SESSION['cart_msg']); unset($_SESSION['cart_msg']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($clothes)): ?>
        <div class="msg msg-info" style="margin-top:24px;">No clothing items available right now. Check back soon!</div>
    <?php else: ?>

        <!-- Also show as table -->
        <div class="section-head" style="margin-top:28px;">
            <h2>All Items</h2>
        </div>

        <!-- Clothes Grid -->
        <div class="clothes-grid">
            <?php foreach ($clothes as $item): ?>
            <div class="clothes-card">
                <?php
                $img = htmlspecialchars($item['image_path'] ?? 'images/item1.jpg');
                ?>
                <img src="<?php echo $img; ?>"
                     alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                     onerror="this.src='https://placehold.co/300x200/c69fd5/2d2d2d?text=<?php echo urlencode($item['item_name']); ?>'">
                <div class="clothes-card-body">
                    <h3><?php echo htmlspecialchars($item['item_name']); ?></h3>
                    <p class="brand"><?php echo htmlspecialchars($item['brand']); ?></p>
                    <div class="meta">
                        <span class="tag"><?php echo htmlspecialchars($item['category']); ?></span>
                        <span class="tag">Size <?php echo htmlspecialchars($item['size']); ?></span>
                        <span class="tag"><?php echo htmlspecialchars($item['item_condition']); ?></span>
                    </div>
                    <div class="price">R<?php echo number_format($item['price'], 2); ?></div>
                    <p style="font-size:0.78rem; color:<?php echo $item['quantity'] > 0 ? '#888' : '#c0392b'; ?>; margin-bottom:8px;">
                        <?php echo $item['quantity'] > 0 ? $item['quantity'] . ' in stock' : 'Out of stock'; ?>
                    </p>
                    <button class="btn btn-primary btn-block"
                            <?php echo $item['quantity'] <= 0 ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : ''; ?>
                            onclick="addToCart(
                                <?php echo $item['clothes_id']; ?>,
                                '<?php echo addslashes(htmlspecialchars($item['item_name'])); ?>',
                                <?php echo $item['price']; ?>
                            )">
                        <?php echo $item['quantity'] > 0 ? 'Add to Cart' : 'Out of Stock'; ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Table view -->
        <div class="section-head" style="margin-top: 48px;">
            <h2>Item List</h2>
        </div>

        <div class="card" style="padding:0;">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th><th>Item</th><th>Brand</th>
                            <th>Stock</th>
                            <th>Category</th><th>Size</th><th>Condition</th>
                            <th>Price</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($clothes as $item): ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($item['image_path'] ?? ''); ?>"
                                     alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                                     style="width:52px;height:52px;object-fit:cover;border-radius:6px;background:#f0e8f5;"
                                     onerror="this.src='https://placehold.co/52x52/c69fd5/2d2d2d?text=IMG'">
                            </td>
                            <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($item['brand']); ?></td>
                            <td>
                                <span class="badge <?php echo $item['quantity'] > 0 ? 'badge-approved' : 'badge-pending'; ?>">
                                    <?php echo $item['quantity'] > 0 ? $item['quantity'] . ' left' : 'Out of stock'; ?>
                                </span>
                            </td>
                            <td><span class="tag"><?php echo htmlspecialchars($item['category']); ?></span></td>
                            <td><?php echo htmlspecialchars($item['size']); ?></td>
                            <td><?php echo htmlspecialchars($item['item_condition']); ?></td>
                            <td style="color:var(--gold);font-family:'Playfair Display',serif;font-weight:700;">
                                R<?php echo number_format($item['price'], 2); ?>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm"
                                        <?php echo $item['quantity'] <= 0 ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : ''; ?>
                                        onclick="addToCart(
                                            <?php echo $item['clothes_id']; ?>,
                                            '<?php echo addslashes(htmlspecialchars($item['item_name'])); ?>',
                                            <?php echo $item['price']; ?>
                                        )">
                                    <?php echo $item['quantity'] > 0 ? 'Add to Cart' : 'Out of Stock'; ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>

</div>

<!-- Cart Price Popup -->
<div class="popup-overlay" id="cartPopup">
    <div class="popup-box">
        <div class="popup-icon">
            <svg viewBox="0 0 24 24"><path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.2 14H19l2-8H5.5L4.3 2H1v2h2l3.6 7.6L5.2 14c-.6 1.1.2 2 1 2H19v-2H7.2z"/></svg>
        </div>
        <h3>Added to Cart!</h3>
        <p class="popup-item" id="popupItemName"></p>
        <div class="popup-price">
            R<span id="popupPrice"></span>
            <br><small>selling price</small>
        </div>
        <div class="popup-actions">
            <button class="btn btn-primary" onclick="closePopup()">Continue Shopping</button>
        </div>
    </div>
</div>

<footer>
    &copy; <?php echo date('Y'); ?> <span>Pasttime</span> Clothing Store
</footer>

<script>
function addToCart(id, name, price) {
    document.getElementById('popupItemName').textContent = name;
    document.getElementById('popupPrice').textContent = parseFloat(price).toFixed(2);
    document.getElementById('cartPopup').classList.add('open');

    // Also store in session via fetch (optional enhancement)
    fetch('cart_add.php?id=' + id, { method: 'GET' }).catch(() => {});
}

function closePopup() {
    document.getElementById('cartPopup').classList.remove('open');
}

// Close on overlay click
document.getElementById('cartPopup').addEventListener('click', function(e) {
    if (e.target === this) closePopup();
});
</script>

</body>
</html>