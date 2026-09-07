<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF Token for security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Load Database Config if available
if (file_exists('config.php')) {
    require_once 'config.php';
}

// Auto-login via Remember Me Cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token']) && isset($pdo)) {
    $token = $_COOKIE['remember_token'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['role'] = $user['role'] ?? 'user';
    }
}

// Fetch Logged-in User's Phone Number for Checkout Autofill
$user_phone = '';
if (isset($_SESSION['user_id']) && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($userData && !empty($userData['phone'])) {
            $user_phone = $userData['phone'];
        }
    } catch (Exception $e) {
        // Fallback silently if query fails
    }
}

// Calculate shopping cart total items count
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += is_array($item) ? ($item['quantity'] ?? 1) : 1;
    }
}

// Fetch Products from Database or Fallback
$products = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM products WHERE active = 1 LIMIT 8");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Database query failed, fallback used below
    }
}

if (empty($products)) {
    $products = [
        ["id" => 1, "name" => "GIVI HPS 50.6 Stuttgart Solid Black Medium (H506FSBK)", "price" => 250.00, "image" => "images/featured_products/helmet.png", "featured" => false],
        ["id" => 2, "name" => "MOTUL 300V 4T Factory Line 10W40 1L", "price" => 320.00, "image" => "images/featured_products/synthetic_oil.png", "featured" => false],
        ["id" => 3, "name" => "KOSO Side Mirror", "price" => 320.00, "image" => "images/featured_products/h3420_side_mirror.png", "featured" => false],
        ["id" => 4, "name" => "Michelin Pilot Sport 4 225/40 ZR18 92Y XL", "price" => 325.00, "image" => "images/featured_products/tire.webp", "featured" => false]
    ];
}

// Fetch Services from Database or Fallback
$services = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM services WHERE active = 1 LIMIT 4");
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Fallback used below
    }
}

if (empty($services)) {
    $services = [
        ["id" => 1, "name" => "Premium Oil & Change Service", "desc" => "Full synthetic oil, new filter, comprehensive check-up.", "icon" => "images/featured_services/premium_oil_and_change_service.png"],
        ["id" => 2, "name" => "Wheel & Tire Service", "desc" => "Mounting, precision balancing, and pressure check.", "icon" => "images/featured_services/wheel_and_tire_service.png"],
        ["id" => 3, "name" => "Vehicle Washing Service", "desc" => "Complete exterior wash, foam bath, wheel cleaning, and wax finishing.", "icon" => "images/featured_services/vehicle_washing_service.png"],
        ["id" => 4, "name" => "Battery Service", "desc" => "Battery inspection, voltage testing, terminal cleaning, and battery replacement.", "icon" => "images/featured_services/battery_service.png"]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bencalo MotoWorks</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/index/style.css">
    <link rel="stylesheet" href="styles/index/layout.css">
    <link rel="stylesheet" href="styles/index/components.css">
    <link rel="stylesheet" href="styles/index/modals.css">
    <style>
        /* Inline style for modal alert boxes */
        .modal-alert {
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 14px;
            display: none;
        }

        .modal-alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }

        .modal-alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }
    </style>
</head>

<body>

    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- ==================== SITE HEADER SECTION ==================== -->
    <?php require_once 'components/header.php'; ?>

    <!-- ==================== MAIN CONTENT WRAPPER ==================== -->

    <main id="main-content">

        <!-- ==================== HERO SECTION ==================== -->
        <?php require_once 'components/hero.php'; ?>

        <!-- ==================== SITE FEATURED PRODUCTS SECTION ==================== -->
        <?php require_once 'components/featured_products.php'; ?>

        <!-- ==================== FEATURED SERVICES SECTION ==================== -->
        <?php require_once 'components/featured_services.php'; ?>

        <!-- ==================== FEATURED PROMO SECTION ==================== -->
        <?php require_once 'components/featured_promo.php'; ?>

        <!-- ==================== GET STARTED SECTION ==================== -->
        <?php require_once 'components/get_started.php'; ?>

    </main>

    <!-- ==================== TESTIMONIALS SECTION ==================== -->
    <?php require_once 'components/testimonials.php'; ?>


    <!-- ==================== SITE FOOTER SECTION ==================== -->
    <?php require_once 'components/footer.php'; ?>

    <!-- ==================== BUY NOW / CHECKOUT MODAL ==================== -->
    <?php require_once 'components/modals.php'; ?>




    <!-- ==================== JAVASCRIPT LOGIC SECTION ==================== -->
    <script>
        const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;

        /* Modal Functions */
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            // Clear alerts when closing modal
            const alertBox = document.getElementById(modalId + '-alert');
            if (alertBox) {
                alertBox.className = 'modal-alert';
                alertBox.textContent = '';
            }
        }

        function switchModal(closeId, openId) {
            closeModal(closeId);
            openModal(openId);
        }

        /* Show alert inside a specific modal */
        function showModalAlert(modalId, message, type = 'error') {
            const alertBox = document.getElementById(modalId + '-alert');
            if (alertBox) {
                alertBox.textContent = message;
                alertBox.className = 'modal-alert ' + type;
            }
        }

        /* Open Buy Modal Flow */
        function openBuyModal(productId, productName, productPrice) {
            if (!isLoggedIn) {
                openModal('login-modal');
                return;
            }
            document.getElementById('buy-product-id').value = productId;
            document.getElementById('buy-product-name').textContent = productName;
            document.getElementById('buy-product-price').textContent = typeof productPrice === 'number' ? '₱' + productPrice.toFixed(2) : productPrice;
            openModal('buy-modal');
        }

        /* Toggle Password Visibility */
        function togglePasswordVisibility(fieldId, btn) {
            const passwordField = document.getElementById(fieldId);
            const icon = btn.querySelector('i');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        /* Add To Cart AJAX */
        function addToCart(productId) {
            fetch('cart_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=add&product_id=${productId}&csrf_token=<?= $_SESSION['csrf_token'] ?>`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('cart-count-badge').textContent = data.cart_count;
                    }
                })
                .catch(() => {});
        }

        document.addEventListener('DOMContentLoaded', () => {

            /* Auto-detect active navigation link and apply underline transfer */
            const currentPath = window.location.pathname.split("/").pop();
            const navLinks = document.querySelectorAll(".main-nav a, .header-nav-link");
            navLinks.forEach(link => {
                const linkHref = link.getAttribute("href");
                if (!linkHref) return;
                const linkPage = linkHref.split("/").pop();
                if (linkPage === currentPath || (currentPath === "" && (linkPage === "index.php" || linkPage === ""))) {
                    link.classList.add("active");
                } else {
                    link.classList.remove("active");
                }
            });

            /* Mobile Navigation Menu Toggle */
            const navToggleBtn = document.getElementById('nav-toggle-btn');
            const mainNav = document.getElementById('main-nav');
            if (navToggleBtn && mainNav) {
                navToggleBtn.addEventListener('click', () => {
                    const expanded = navToggleBtn.getAttribute('aria-expanded') === 'true' || false;
                    navToggleBtn.setAttribute('aria-expanded', !expanded);
                    mainNav.classList.toggle('active');
                });
            }

            /* Product Instant Search */
            const searchInput = document.getElementById('product-search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const query = e.target.value.toLowerCase().trim();
                    const cards = document.querySelectorAll('#product-grid-container .product-card');
                    cards.forEach(card => {
                        const name = card.getAttribute('data-name');
                        card.style.display = name.includes(query) ? '' : 'none';
                    });
                });
            }

            /* Auth check guard for restricted elements */
            document.querySelectorAll('.requires-auth').forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!isLoggedIn) {
                        e.preventDefault();
                        openModal('login-modal');
                        showModalAlert('login-modal', 'Please log in to continue.', 'error');
                    }
                });
            });

            /* Modal Backdrop Click Close */
            window.addEventListener('click', function(e) {
                if (e.target.classList.contains('auth-modal')) {
                    e.target.classList.remove('active');
                    // Clear alerts
                    const alertBox = e.target.querySelector('.modal-alert');
                    if (alertBox) {
                        alertBox.className = 'modal-alert';
                        alertBox.textContent = '';
                    }
                }
            });

            /* Generic AJAX Form Submit Handler with Modal Error Messages */
            function handleFormSubmit(formId, endpoint, modalId) {
                const form = document.getElementById(formId);
                if (form) {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const formData = new FormData(form);

                        fetch(endpoint, {
                                method: 'POST',
                                body: formData
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    showModalAlert(modalId, data.message || 'Success!', 'success');
                                    if (data.redirect) {
                                        setTimeout(() => window.location.href = data.redirect, 1000);
                                    } else {
                                        setTimeout(() => window.location.reload(), 1000);
                                    }
                                } else {
                                    showModalAlert(modalId, data.message || 'An error occurred.', 'error');
                                }
                            })
                            .catch(() => {
                                showModalAlert(modalId, 'Request failed. Please try again.', 'error');
                            });
                    });
                }
            }

            handleFormSubmit('ajax-login-form', 'login.php', 'login-modal');
            handleFormSubmit('ajax-register-form', 'register.php', 'register-modal');
            handleFormSubmit('ajax-forgot-form', 'forgot_password.php', 'forgot-modal');
            handleFormSubmit('ajax-buy-form', 'process_checkout.php', 'buy-modal');
        });
    </script>
</body>

</html>