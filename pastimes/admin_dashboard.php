<?php
// admin_dashboard.php - Admin: approve users + CRUD
session_start();
require_once 'DBConn.php';
require_once 'includes/functions.php';

require_admin();

$msg = '';
$edit_user = null;

// ---- Handle POST actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Approve user
    if ($action === 'approve') {
        $uid  = (int)$_POST['user_id'];
        $stmt = $conn->prepare("UPDATE tblUser SET status='approved' WHERE user_id=?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $msg = "User approved successfully.";
        $stmt->close();

    // Add new user
    } elseif ($action === 'add') {
        $fn   = clean_input($_POST['full_name'] ?? '');
        $em   = clean_input($_POST['email']     ?? '');
        $un   = clean_input($_POST['username']  ?? '');
        $pw   = $_POST['password']              ?? '';
        $role = clean_input($_POST['role']      ?? 'buyer');
        $stat = clean_input($_POST['status']    ?? 'approved');
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "INSERT INTO tblUser (full_name, email, username, password, role, status) VALUES (?,?,?,?,?,?)"
        );
        $stmt->bind_param("ssssss", $fn, $em, $un, $hash, $role, $stat);
        $stmt->execute() ? $msg = "User added." : $msg = "Error: " . $stmt->error;
        $stmt->close();

    // Update user
    } elseif ($action === 'update') {
        $uid  = (int)$_POST['user_id'];
        $fn   = clean_input($_POST['full_name'] ?? '');
        $em   = clean_input($_POST['email']     ?? '');
        $un   = clean_input($_POST['username']  ?? '');
        $role = clean_input($_POST['role']      ?? 'buyer');
        $stat = clean_input($_POST['status']    ?? 'approved');
        $stmt = $conn->prepare(
            "UPDATE tblUser SET full_name=?, email=?, username=?, role=?, status=? WHERE user_id=?"
        );
        $stmt->bind_param("sssssi", $fn, $em, $un, $role, $stat, $uid);
        $stmt->execute() ? $msg = "User updated." : $msg = "Error: " . $stmt->error;
        $stmt->close();

    // Delete user
    } elseif ($action === 'delete') {
        $uid  = (int)$_POST['user_id'];
        $stmt = $conn->prepare("DELETE FROM tblUser WHERE user_id=?");
        $stmt->bind_param("i", $uid);
        $stmt->execute() ? $msg = "User deleted." : $msg = "Error: " . $stmt->error;
        $stmt->close();
    }
}

// Load edit form
if (isset($_GET['edit'])) {
    $uid  = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM tblUser WHERE user_id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $edit_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ---- Fetch data ----
$pending_result = $conn->query("SELECT * FROM tblUser WHERE status='pending' ORDER BY created_at DESC");
$all_result     = $conn->query("SELECT * FROM tblUser ORDER BY created_at DESC");

$pending_count = $pending_result ? $pending_result->num_rows : 0;
$total_count   = $all_result ? $all_result->num_rows : 0;

$clothes_result = $conn->query("SELECT COUNT(*) AS cnt FROM tblClothes");
$clothes_count  = $clothes_result ? $clothes_result->fetch_assoc()['cnt'] : 0;

$admin_name = htmlspecialchars($_SESSION['admin_full_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pasttime</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <div class="nav-inner">
        <span class="nav-brand">Past<span>times</span> <small style="font-size:0.65rem;color:var(--gold);font-family:'DM Sans',sans-serif;letter-spacing:1px;">ADMIN</small></span>
        <nav class="nav-links">
            <a href="/pastimes/admin_dashboard.php" class="active">Users</a>
            <a href="/pastimes/admin_clothes.php">Clothes</a>
            <a href="/pastimes/admin_orders.php">Orders</a>
            <a href="/pastimes/admin_messages.php">Messages</a>
            <a href="/pastimes/clothes.php">View Store</a>
            <a href="/pastimes/logout.php" class="btn-nav-logout">Logout</a>
        </nav>
    </div>
</header>

<div class="page-hero">
    <h1>Admin Dashboard</h1>
    <p>Welcome, <?php echo $admin_name; ?></p>
</div>

<div class="wrapper">

    <?php if ($msg): ?>
        <div class="msg msg-success" style="margin-top:20px;"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row" style="margin-top: 24px;">
        <div class="stat-card">
            <div class="stat-num"><?php echo $total_count; ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card gold">
            <div class="stat-num"><?php echo $pending_count; ?></div>
            <div class="stat-label">Pending Approval</div>
        </div>
        <div class="stat-card dark">
            <div class="stat-num"><?php echo $clothes_count; ?></div>
            <div class="stat-label">Clothing Items</div>
        </div>
    </div>

    <!-- ===== Section A: Pending Users ===== -->
    <div class="section-head">
        <h2>Pending Approvals</h2>
        <?php if ($pending_count > 0): ?>
            <span class="badge badge-pending"><?php echo $pending_count; ?> waiting</span>
        <?php endif; ?>
    </div>

    <?php
    // Re-query after any updates
    $pending_result = $conn->query("SELECT * FROM tblUser WHERE status='pending' ORDER BY created_at DESC");
    if (!$pending_result || $pending_result->num_rows === 0):
    ?>
        <div class="msg msg-info">No pending users at this time.</div>
    <?php else: ?>
        <div class="card" style="padding:0;">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th><th>Full Name</th><th>Email</th>
                            <th>Username</th><th>Role</th><th>Registered</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $pending_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><span class="badge badge-<?php echo $row['role']; ?>"><?php echo ucfirst($row['role']); ?></span></td>
                            <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action"  value="approve">
                                    <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                    <button type="submit" class="btn btn-primary btn-sm">Approve</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- ===== Section B: Add / Edit User Form ===== -->
    <div class="section-head">
        <h2><?php echo $edit_user ? 'Edit User' : 'Add New User'; ?></h2>
        <?php if ($edit_user): ?>
            <a href="admin_dashboard.php" class="btn btn-sm btn-dark">+ Add New Instead</a>
        <?php endif; ?>
    </div>

    <div class="card <?php echo $edit_user ? 'card-gold' : ''; ?>">
        <form method="POST" action="admin_dashboard.php">
            <input type="hidden" name="action"  value="<?php echo $edit_user ? 'update' : 'add'; ?>">
            <?php if ($edit_user): ?>
                <input type="hidden" name="user_id" value="<?php echo $edit_user['user_id']; ?>">
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name"
                           value="<?php echo htmlspecialchars($edit_user['full_name'] ?? ''); ?>"
                           placeholder="Full name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email"
                           value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>"
                           placeholder="Email" required>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username"
                           value="<?php echo htmlspecialchars($edit_user['username'] ?? ''); ?>"
                           placeholder="Username" required>
                </div>
                <?php if (!$edit_user): ?>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Set password" required>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="buyer"  <?php echo ($edit_user['role'] ?? '') === 'buyer'  ? 'selected' : ''; ?>>Buyer</option>
                        <option value="seller" <?php echo ($edit_user['role'] ?? '') === 'seller' ? 'selected' : ''; ?>>Seller</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="approved" <?php echo ($edit_user['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="pending"  <?php echo ($edit_user['status'] ?? '') === 'pending'  ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn <?php echo $edit_user ? 'btn-gold' : 'btn-primary'; ?>">
                <?php echo $edit_user ? 'Update User' : 'Add User'; ?>
            </button>
        </form>
    </div>

    <!-- ===== Section C: All Users CRUD Table ===== -->
    <div class="section-head">
        <h2>All Users</h2>
    </div>

    <?php $all_result = $conn->query("SELECT * FROM tblUser ORDER BY created_at DESC"); ?>
    <div class="card" style="padding:0;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Email</th><th>Username</th>
                        <th>Role</th><th>Status</th><th>Registered</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $all_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><span class="badge badge-<?php echo $row['role']; ?>"><?php echo ucfirst($row['role']); ?></span></td>
                        <td><span class="badge badge-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                        <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <div class="flex-gap">
                                <a href="admin_dashboard.php?edit=<?php echo $row['user_id']; ?>"
                                   class="btn btn-gold btn-sm">Edit</a>
                                <form method="POST" onsubmit="return confirm('Delete this user?');" style="display:inline;">
                                    <input type="hidden" name="action"  value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
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
    &copy; <?php echo date('Y'); ?> <span>Pasttime</span> Clothing Store - Admin Panel
</footer>

</body>
</html>