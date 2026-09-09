<?php
// /admin/includes/admin_sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <h2>MotoWorks Admin</h2>
    </div>
    <ul class="sidebar-menu">
        <li class="<?= ($current_page === 'admin_index.php' || $current_page === 'admin_dashboard.php') ? 'active' : '' ?>">
            <a href="admin_index.php"><i class="fas fa-home"></i> Dashboard</a>
        </li>
        <li class="<?= ($current_page === 'admin_products.php') ? 'active' : '' ?>">
            <a href="admin_products.php"><i class="fas fa-box"></i> Products</a>
        </li>
        <li class="<?= ($current_page === 'admin_orders.php') ? 'active' : '' ?>">
            <a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
        </li>
        <li class="<?= ($current_page === 'admin_services.php') ? 'active' : '' ?>">
            <a href="admin_services.php"><i class="fas fa-concierge-bell"></i> Services</a>
        </li>
        <li class="<?= ($current_page === 'admin_appointment.php') ? 'active' : '' ?>">
            <a href="admin_appointment.php"><i class="fas fa-calendar-check"></i> Appointments</a>
        </li>
        <li class="<?= ($current_page === 'admin_promos.php') ? 'active' : '' ?>">
            <a href="admin_promos.php"><i class="fas fa-tags"></i> Promo</a>
        </li>
        <li class="<?= ($current_page === 'admin_promo_history.php') ? 'active' : '' ?>">
            <a href="admin_promo_history.php"><i class="fas fa-tags"></i> Promo History</a>
        </li>
        <li class="<?= ($current_page === 'admin_users.php') ? 'active' : '' ?>">
            <a href="admin_users.php"><i class="fas fa-users"></i> Users</a>
        </li>
        <li>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </li>
    </ul>
</aside>