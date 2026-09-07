<?php
// Ensure session and variables like $cart_count are available if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$cart_count = $cart_count ?? 0;
?>
<!-- ==================== SITE HEADER SECTION ==================== -->
<header class="site-header">
    <div class="container header-inner">
        <a href="index.php" class="logo">
            <img src="images/header/Bencalo MotoWorks Logo.svg" alt="Bencalo MotoWorks Logo" class="logo-img">
        </a>

        <nav class="main-nav" id="main-nav">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="shop.php">Shop</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="#about">About Us</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>

        <div class="header-cta-group">
            <a href="cart.php" class="cart-link-icon" aria-label="Shopping Cart">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="cart-badge" id="cart-count-badge"><?= $cart_count ?></span>
            </a>

            <a href="services.php" class="btn btn-primary">Book Appointment</a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-dropdown">
                    <button class="btn-signup-header">
                        Hello, <?= htmlspecialchars($_SESSION['first_name'] ?? 'User') ?> <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div class="user-menu">
                        <a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a>
                        <a href="appointment_history.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a>
                        <a href="order_history.php"><i class="fa-solid fa-box"></i> Order History</a>
                        <a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="#" onclick="openModal('login-modal')" class="btn-login-header">Log In</a>
                <a href="#" onclick="openModal('register-modal')" class="btn-signup-header">Sign Up</a>
            <?php endif; ?>
        </div>

        <button class="nav-toggle" id="nav-toggle-btn" aria-label="Toggle navigation" aria-expanded="false" aria-controls="main-nav">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</header>