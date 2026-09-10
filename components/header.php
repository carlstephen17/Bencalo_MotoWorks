<?php
// Ensure session and variables like $cart_count are available if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$cart_count = $cart_count ?? 0;
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && ($_SESSION['role'] ?? '') === 'admin';
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
                <li><a href="promos.php">Promos</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </nav>

        <div class="header-cta-group">
            <a href="cart.php" class="header-cart-link">
                <i class="fa-solid fa-cart-shopping"></i>
                <span id="cart-count-badge" class="cart-badge"><?= $cart_count ?></span>
            </a>

            <a href="services.php" class="btn btn-primary">Book Appointment</a>

            <?php if ($isAdmin): ?>
                <a href="admin/admin_index.php" class="btn btn-primary">Dashboard</a>
            <?php endif; ?>

            <!-- LOGGED-IN USER DROPDOWN -->
            <div id="nav-user-dropdown" class="user-dropdown" style="display: <?= $isLoggedIn ? 'block' : 'none' ?>;">
                <button class="btn-signup-header user-dropdown-toggle" type="button" onclick="toggleUserDropdownMenu()">
                    Hello, <span id="nav-user-name"><?= htmlspecialchars($_SESSION['first_name'] ?? 'User') ?></span> <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="user-menu" id="userMenuDropdown">
                    <a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a>
                    <a href="appointment_history.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a>
                    <a href="order_history.php"><i class="fa-solid fa-box"></i> Order History</a>
                    <a href="promo_history.php"><i class="fa-solid fa-box"></i> Promo History</a>
                    <a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
                </div>
            </div>

            <!-- GUEST BUTTONS -->
            <div id="nav-guest-buttons" style="display: <?= $isLoggedIn ? 'none' : 'contents' ?>;">
                <button type="button" onclick="openModal('login-modal')" class="btn-login-header" style="background:none; border:none; cursor:pointer;">Log In</button>
                <button type="button" onclick="openModal('register-modal')" class="btn-signup-header" style="cursor:pointer;">Sign Up</button>
            </div>
        </div>

        <button class="nav-toggle" id="nav-toggle-btn" aria-label="Toggle navigation" aria-expanded="false" aria-controls="main-nav">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</header>

<script>
function toggleUserDropdownMenu() {
    const menu = document.getElementById('userMenuDropdown');
    if (menu) {
        menu.classList.toggle('show');
    }
}

// Close dropdown when clicking outside
window.addEventListener('click', function(e) {
    if (!e.target.closest('#nav-user-dropdown')) {
        const menu = document.getElementById('userMenuDropdown');
        if (menu && menu.classList.contains('show')) {
            menu.classList.remove('show');
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';

    document.querySelectorAll('.main-nav a').forEach(function(link) {
        const linkPage = link.getAttribute('href').split('/').pop();
        link.classList.toggle(
            'active',
            linkPage === currentPage || (currentPage === '' && linkPage === 'index.php')
        );
    });
});
</script>