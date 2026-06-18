<?php
// admin_clothes.php - Admin: Add, Edit, Delete clothing items
session_start();
require_once 'DBConn.php';
require_once 'includes/functions.php';

require_admin();

$msg = '';
$edit_item = null;

// ---- Handle POST actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name  = clean_input($_POST['item_name'] ?? '');
        $brand = clean_input($_POST['brand'] ?? '');
        $cat   = clean_input($_POST['category'] ?? '');
        $size  = clean_input($_POST['size'] ?? '');
        $cond  = clean_input($_POST['item_condition'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $qty   = (int)($_POST['quantity'] ?? 0);
        $desc  = clean_input($_POST['description'] ?? '');
        $img   = clean_input($_POST['image_path'] ?? '');
        $seller = (int)($_POST['seller_id'] ?? 0);
        $sellerParam = $seller > 0 ? $seller : null;

        $stmt = $conn->prepare(
            "INSERT INTO tblClothes (item_name, brand, category, size, item_condition, price, quantity, description, image_path, seller_id, status)
             VALUES (?,?,?,?,?,?,?,?,?,?,'available')"
        );
        $stmt->bind_param("sssssdissi", $name, $brand, $cat, $size, $cond, $price, $qty, $desc, $img, $sellerParam);
        $msg = $stmt->execute() ? "Item added successfully." : "Error: " . $stmt->error;
        $stmt->close();

    } elseif ($action === 'approve_request') {
        // Approve a pending seller request — makes it visible to buyers
        $id   = (int)$_POST['clothes_id'];
        $qty  = (int)($_POST['quantity'] ?? 1);
        if ($qty < 1) $qty = 1;
        $stmt = $conn->prepare("UPDATE tblClothes SET status='available', quantity=? WHERE clothes_id=?");
        $stmt->bind_param("ii", $qty, $id);
        $msg = $stmt->execute() ? "Seller request approved — item is now live in the store." : "Error: " . $stmt->error;
        $stmt->close();

    } elseif ($action === 'reject_request') {
        // Reject a pending seller request — removes it
        $id   = (int)$_POST['clothes_id'];
        $stmt = $conn->prepare("DELETE FROM tblClothes WHERE clothes_id=? AND status='pending'");
        $stmt->bind_param("i", $id);
        $msg = $stmt->execute() ? "Seller request rejected." : "Error: " . $stmt->error;
        $stmt->close();

    } elseif ($action === 'update') {
        $id    = (int)$_POST['clothes_id'];
        $name  = clean_input($_POST['item_name'] ?? '');
        $brand = clean_input($_POST['brand'] ?? '');
        $cat   = clean_input($_POST['category'] ?? '');
        $size  = clean_input($_POST['size'] ?? '');
        $cond  = clean_input($_POST['item_condition'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $qty   = (int)($_POST['quantity'] ?? 0);
        $desc  = clean_input($_POST['description'] ?? '');
        $img   = clean_input($_POST['image_path'] ?? '');
        $status = clean_input($_POST['status'] ?? 'available');

        $stmt = $conn->prepare(
            "UPDATE tblClothes SET item_name=?, brand=?, category=?, size=?, item_condition=?, price=?, quantity=?, description=?, image_path=?, status=?
             WHERE clothes_id=?"
        );
        $stmt->bind_param("sssssdisssi", $name, $brand, $cat, $size, $cond, $price, $qty, $desc, $img, $status, $id);
        $msg = $stmt->execute() ? "Item updated successfully." : "Error: " . $stmt->error;
        $stmt->close();

    } elseif ($action === 'delete') {
        $id   = (int)$_POST['clothes_id'];
        $stmt = $conn->prepare("DELETE FROM tblClothes WHERE clothes_id=?");
        $stmt->bind_param("i", $id);
        $msg = $stmt->execute() ? "Item deleted successfully." : "Error: " . $stmt->error;
        $stmt->close();
    }
}

// Load edit form
if (isset($_GET['edit'])) {
    $id   = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM tblClothes WHERE clothes_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_item = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch all clothes (excluding pending requests, which get their own section)
$all_result = $conn->query("SELECT * FROM tblClothes WHERE status != 'pending' ORDER BY created_at DESC");

// Fetch pending seller requests awaiting approval
$pending_result = $conn->query(
    "SELECT c.*, u.full_name AS seller_name, u.email AS seller_email
     FROM tblClothes c
     LEFT JOIN tblUser u ON c.seller_id = u.user_id
     WHERE c.status = 'pending'
     ORDER BY c.created_at DESC"
);
$pending_count = $pending_result ? $pending_result->num_rows : 0;

// Fetch sellers for the dropdown
$sellersResult = $conn->query("SELECT user_id, full_name FROM tblUser WHERE role='seller' AND status='approved'");
$sellers = [];
if ($sellersResult) {
    while ($row = $sellersResult->fetch_assoc()) $sellers[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clothes - Pasttime Admin</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <div class="nav-inner">
        <span class="nav-brand">Past<span>times</span> <small style="font-size:0.65rem;color:var(--gold);font-family:'DM Sans',sans-serif;letter-spacing:1px;">ADMIN</small></span>
        <nav class="nav-links">
            <a href="/pastimes/admin_dashboard.php">Users</a>
            <a href="/pastimes/admin_clothes.php" class="active">Clothes</a>
            <a href="/pastimes/admin_orders.php">Orders</a>
            <a href="/pastimes/admin_messages.php">Messages</a>
            <a href="/pastimes/clothes.php">View Store</a>
            <a href="/pastimes/logout.php" class="btn-nav-logout">Logout</a>
        </nav>
    </div>
</header>

<div class="page-hero">
    <h1>Manage Clothing</h1>
    <p>Add, edit and delete clothing items</p>
</div>

<div class="wrapper">

    <?php if ($msg): ?>
        <div class="msg msg-success" style="margin-top:20px;"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <!-- ===== Pending Seller Requests ===== -->
    <div class="section-head" style="margin-top:24px;">
        <h2>Pending Seller Requests</h2>
        <?php if ($pending_count > 0): ?>
            <span class="badge badge-pending"><?php echo $pending_count; ?> waiting</span>
        <?php endif; ?>
    </div>

    <?php if ($pending_count === 0): ?>
        <div class="msg msg-info">No pending seller requests at this time.</div>
    <?php else: ?>
        <?php while ($req = $pending_result->fetch_assoc()): ?>
            <div class="card card-gold">
                <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:20px;">
                    <div style="display:flex; gap:16px;">
                        <img src="<?php echo htmlspecialchars($req['image_path'] ?: ''); ?>"
                             alt="<?php echo htmlspecialchars($req['item_name']); ?>"
                             style="width:90px;height:90px;object-fit:cover;border-radius:8px;background:#f0e8f5;flex-shrink:0;"
                             onerror="this.src='https://placehold.co/90x90/c69fd5/2d2d2d?text=<?php echo urlencode($req['item_name']); ?>'">
                        <div>
                            <h3 style="margin-bottom:4px;"><?php echo htmlspecialchars($req['item_name']); ?></h3>
                            <p style="color:#888; font-size:0.85rem; margin-bottom:6px;">
                                Submitted by <strong><?php echo htmlspecialchars($req['seller_name'] ?? 'Unknown seller'); ?></strong>
                                <?php if ($req['seller_email']): ?> (<?php echo htmlspecialchars($req['seller_email']); ?>)<?php endif; ?>
                            </p>
                            <div class="meta" style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:8px;">
                                <span class="tag"><?php echo htmlspecialchars($req['brand']); ?></span>
                                <span class="tag"><?php echo htmlspecialchars($req['category']); ?></span>
                                <span class="tag">Size <?php echo htmlspecialchars($req['size']); ?></span>
                                <span class="tag"><?php echo htmlspecialchars($req['item_condition']); ?></span>
                            </div>
                            <p style="color:#555; font-size:0.88rem; max-width:480px;"><?php echo htmlspecialchars($req['description']); ?></p>
                            <p style="color:var(--gold); font-family:'Playfair Display',serif; font-weight:700; margin-top:6px;">
                                R<?php echo number_format($req['price'], 2); ?>
                            </p>
                        </div>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:10px; min-width:200px;">
                        <form method="POST" class="flex-gap" style="align-items:flex-end;">
                            <input type="hidden" name="action" value="approve_request">
                            <input type="hidden" name="clothes_id" value="<?php echo $req['clothes_id']; ?>">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Stock Qty</label>
                                <input type="number" name="quantity" value="1" min="1" style="width:80px;">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Approve &amp; Publish</button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Reject and remove this request?');">
                            <input type="hidden" name="action" value="reject_request">
                            <input type="hidden" name="clothes_id" value="<?php echo $req['clothes_id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                        </form>
                        <a href="/pastimes/admin_messages.php?user_id=<?php echo $req['seller_id']; ?>&subject=<?php echo urlencode('About your listing: ' . $req['item_name']); ?>"
                           class="btn btn-dark btn-sm">Message Seller</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

    <!-- Add / Edit Form -->
    <div class="section-head" style="margin-top:32px;">
        <h2><?php echo $edit_item ? 'Edit Item' : 'Add New Item'; ?></h2>
        <?php if ($edit_item): ?>
            <a href="/pastimes/admin_clothes.php" class="btn btn-sm btn-dark">+ Add New Instead</a>
        <?php endif; ?>
    </div>

    <div class="card <?php echo $edit_item ? 'card-gold' : ''; ?>">
        <form method="POST" action="admin_clothes.php">
            <input type="hidden" name="action" value="<?php echo $edit_item ? 'update' : 'add'; ?>">
            <?php if ($edit_item): ?>
                <input type="hidden" name="clothes_id" value="<?php echo $edit_item['clothes_id']; ?>">
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" name="item_name" value="<?php echo htmlspecialchars($edit_item['item_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Brand</label>
                    <input type="text" name="brand" value="<?php echo htmlspecialchars($edit_item['brand'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" value="<?php echo htmlspecialchars($edit_item['category'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Size</label>
                    <input type="text" name="size" value="<?php echo htmlspecialchars($edit_item['size'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Condition</label>
                    <input type="text" name="item_condition" value="<?php echo htmlspecialchars($edit_item['item_condition'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Price (R)</label>
                    <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($edit_item['price'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Stock Quantity</label>
                    <input type="number" name="quantity" value="<?php echo htmlspecialchars($edit_item['quantity'] ?? '10'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Image Path</label>
                    <input type="text" name="image_path" value="<?php echo htmlspecialchars($edit_item['image_path'] ?? ''); ?>" placeholder="images/filename.jpg (leave blank for auto placeholder)">
                </div>
                <?php if (!$edit_item): ?>
                <div class="form-group">
                    <label>Seller</label>
                    <select name="seller_id">
                        <option value="0">No specific seller</option>
                        <?php foreach ($sellers as $s): ?>
                            <option value="<?php echo $s['user_id']; ?>"><?php echo htmlspecialchars($s['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="available" <?php echo ($edit_item['status'] ?? '') === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="sold" <?php echo ($edit_item['status'] ?? '') === 'sold' ? 'selected' : ''; ?>>Sold</option>
                        <option value="pending" <?php echo ($edit_item['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <div class="form-group" style="margin-top:16px;">
                <label>Description</label>
                <textarea name="description" rows="3"><?php echo htmlspecialchars($edit_item['description'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="btn <?php echo $edit_item ? 'btn-gold' : 'btn-primary'; ?>">
                <?php echo $edit_item ? 'Update Item' : 'Add Item'; ?>
            </button>
        </form>
    </div>

    <!-- All Clothes Table -->
    <div class="section-head" style="margin-top:32px;">
        <h2>All Clothing Items</h2>
    </div>

    <div class="card" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Item</th><th>Brand</th><th>Price</th>
                        <th>Stock</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $all_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['clothes_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['brand']); ?></td>
                        <td style="color:var(--gold);font-weight:700;">R<?php echo number_format($row['price'], 2); ?></td>
                        <td>
                            <span class="badge <?php echo $row['quantity'] > 0 ? 'badge-approved' : 'badge-pending'; ?>">
                                <?php echo $row['quantity']; ?>
                            </span>
                        </td>
                        <td><span class="badge <?php echo $row['status'] === 'available' ? 'badge-approved' : 'badge-seller'; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                        <td>
                            <div class="flex-gap">
                                <a href="/pastimes/admin_clothes.php?edit=<?php echo $row['clothes_id']; ?>" class="btn btn-gold btn-sm">Edit</a>
                                <form method="POST" onsubmit="return confirm('Delete this item?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="clothes_id" value="<?php echo $row['clothes_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
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