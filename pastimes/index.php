<?php

// index.php - Pasttime landing page

session_start();
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pasttime - Second-Hand Clothing Store</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .hero-main {
            min-height: 92vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px 24px;
            position: relative;
            z-index: 1;
        }

        .hero-eyebrow {
            font-size: 0.78rem;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 16px;
            font-weight: 600;
        }

        .hero-main h1 {
            font-size: clamp(2.8rem, 8vw, 5.5rem);
            line-height: 1.05;
            color: var(--secondary);
            margin-bottom: 20px;
        }

        .hero-main h1 em {
            font-style: italic;
            color: var(--primary-dark);
        }

        .hero-tagline {
            font-size: 1.1rem;
            color: #666;
            max-width: 480px;
            margin: 0 auto 36px;
            line-height: 1.7;
        }

        .hero-cta {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 60px;
        }

        .hero-cta .btn {
            padding: 14px 32px;
            font-size: 0.95rem;
        }

        /* Flow cards */
        .flow-section {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px 60px;
            position: relative;
            z-index: 1;
        }

        .flow-section h2 {
            text-align: center;
            margin-bottom: 8px;
            font-size: 1.5rem;
        }

        .flow-subtitle {
            text-align: center;
            color: #888;
            font-size: 0.88rem;
            margin-bottom: 32px;
        }

        .flow-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .flow-card {
            background: #fff;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 3px 14px rgba(45,45,45,0.09);
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-decoration: none;
            color: inherit;
            display: block;
            border-bottom: 3px solid transparent;
        }

        .flow-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(45,45,45,0.15);
            color: inherit;
        }

        .flow-card.purple { border-bottom-color: var(--primary); }
        .flow-card.gold   { border-bottom-color: var(--gold); }
        .flow-card.dark   { border-bottom-color: var(--secondary); }

        .flow-num {
            width: 38px; height: 38px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Playfair Display', serif;
            font-size: 1rem;
            font-weight: 700;
            margin: 0 auto 12px;
        }

        .flow-card.purple .flow-num { background: rgba(198,159,213,0.2); color: var(--primary-dark); }
        .flow-card.gold   .flow-num { background: rgba(201,168,76,0.15);  color: var(--gold); }
        .flow-card.dark   .flow-num { background: rgba(45,45,45,0.08);    color: var(--secondary); }

        .flow-card h3 { font-size: 0.95rem; margin-bottom: 6px; }
        .flow-card p  { font-size: 0.8rem;  color: #888; line-height: 1.5; }

        /* Decoration strip */
        .colour-strip {
            display: flex;
            height: 5px;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 200;
        }
        .colour-strip div { flex: 1; }

        /* Setup banner */
        .setup-banner {
            background: linear-gradient(135deg, var(--secondary), #4a3a5c);
            color: #fff;
            text-align: center;
            padding: 14px 24px;
            font-size: 0.88rem;
            position: relative;
            z-index: 1;
        }
        .setup-banner a { color: var(--gold); font-weight: 600; }
    </style>
</head>
<body>

<!-- Colour strip -->
<div class="colour-strip">
    <div style="background:var(--primary);"></div>
    <div style="background:var(--gold);"></div>
    <div style="background:var(--secondary);"></div>
</div>

<!-- Setup nudge if not yet configured -->
<div class="setup-banner">
    First time here? <a href="setup.php">Run setup.php</a> to create the database and insert sample data.
</div>

<header style="top:5px;">
    <div class="nav-inner">
        <span class="nav-brand">Past<span>times</span></span>
        <nav class="nav-links">
            <?php if (is_logged_in()): ?>
                <a href="user_dashboard.php">Dashboard</a>
                <a href="clothes.php">Browse</a>
                <a href="logout.php" class="btn-nav-logout">Logout</a>
            <?php else: ?>
                <a href="register.php">Register</a>
                <a href="login.php">Login</a>
                <a href="admin_login.php">Admin</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<!-- Hero -->
<div class="hero-main">
    <p class="hero-eyebrow">Second-hand clothing marketplace</p>
    <h1>Give clothes a<br><em>second life</em></h1>
    <p class="hero-tagline">
        Pasttime connects buyers and sellers of pre-loved clothing.
        Shop sustainably, sell easily.
    </p>
    <div class="hero-cta">
        <a href="register.php" class="btn btn-primary">Create Account</a>
        <a href="login.php"    class="btn btn-dark">Sign In</a>
    </div>
</div>

<!-- Demo flow -->
<div class="flow-section">
    <h2>Demo Flow</h2>
    <p class="flow-subtitle">Follow these steps to demonstrate the full application</p>

    <div class="flow-grid">
        <a href="setup.php" class="flow-card dark">
            <div class="flow-num">0</div>
            <h3>Run Setup</h3>
            <p>Creates all 4 tables, admin account, sample users and clothes</p>
        </a>
        <a href="register.php" class="flow-card purple">
            <div class="flow-num">1</div>
            <h3>Register</h3>
            <p>New user registers - account set to <em>pending</em></p>
        </a>
        <a href="login.php" class="flow-card purple">
            <div class="flow-num">2</div>
            <h3>Try Login</h3>
            <p>Pending user blocked with a clear message</p>
        </a>
        <a href="admin_login.php" class="flow-card gold">
            <div class="flow-num">3</div>
            <h3>Admin Login</h3>
            <p>Login as admin - username: <code>admin</code> / pw: <code>admin123</code></p>
        </a>
        <a href="admin_dashboard.php" class="flow-card gold">
            <div class="flow-num">4</div>
            <h3>Approve User</h3>
            <p>Approve pending user, or add / edit / delete users</p>
        </a>
        <a href="login.php" class="flow-card purple">
            <div class="flow-num">5</div>
            <h3>User Login</h3>
            <p>Approved user logs in successfully via <code>password_verify()</code></p>
        </a>
        <a href="clothes.php" class="flow-card dark">
            <div class="flow-num">6</div>
            <h3>Browse Clothes</h3>
            <p>View grid + table of items. Click "Add to Cart" for price popup</p>
        </a>
    </div>
</div>

<footer>
    &copy; <?php echo date('Y'); ?> <span>Pasttime</span> Clothing Store &mdash;
   
</footer>

</body>
</html>