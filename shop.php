<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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

$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += is_array($item) ? ($item['quantity'] ?? 1) : 1;
    }
}

// Define default_products globally so it's always in scope
$default_products = [
    ["id" => 1, "name" => "GIVI HPS 50.6 Stuttgart Solid Black Medium (H506FSBK)", "price" => 250.00, "image" => "images/featured_products/helmet.png", "featured" => 0],
    ["id" => 2, "name" => "MOTUL 300V 4T Factory Line 10W40 1L", "price" => 320.00, "image" => "images/featured_products/synthetic_oil.png", "featured" => 0],
    ["id" => 3, "name" => "KOSO Side Mirror", "price" => 320.00, "image" => "images/featured_products/h3420_side_mirror.png", "featured" => 0],
    ["id" => 4, "name" => "Michelin Pilot Sport 4 225/40 ZR18 92Y XL", "price" => 325.00, "image" => "images/featured_products/tire.webp", "featured" => 0],
    ["id" => 5, "name" => "NGK Iridium IX Spark Plug", "price" => 45.00, "image" => "images/featured_products/spark_plug.png", "featured" => 0],
    ["id" => 6, "name" => "Brembo High Performance Brake Pads", "price" => 180.00, "image" => "images/featured_products/brake_pads.png", "featured" => 0],
    ["id" => 7, "name" => "DID Heavy Duty Racing Chain & Sprocket Set", "price" => 1250.00, "image" => "images/featured_products/brake_pads.png", "featured" => 0],
    ["id" => 8, "name" => "Racing Boy (RCB) S1 Series Brake Master Cylinder", "price" => 950.00, "image" => "images/featured_products/brake_pads.png", "featured" => 0]
];

// Fetch ALL Products from Database with Auto-Seeding & Sync
$products = [];
if (isset($pdo)) {
    try {
        $stmtUpsert = $pdo->prepare("
            INSERT INTO products (id, name, price, image, active, featured) 
            VALUES (?, ?, ?, ?, 1, ?)
            ON DUPLICATE KEY UPDATE 
                name = VALUES(name), 
                price = VALUES(price), 
                image = VALUES(image),
                featured = VALUES(featured)
        ");

        foreach ($default_products as $dp) {
            $stmtUpsert->execute([$dp['id'], $dp['name'], $dp['price'], $dp['image'], $dp['featured']]);
        }

        $stmt = $pdo->query("SELECT * FROM products WHERE active = 1 ORDER BY id DESC");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $products = $default_products;
    }
} else {
    $products = $default_products;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop All Products - Bencalo MotoWorks</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/index/style.css">
    <link rel="stylesheet" href="styles/index/layout.css">
    <link rel="stylesheet" href="styles/index/components.css">
    <link rel="stylesheet" href="styles/index/modals.css">
    <link rel="stylesheet" href="styles/shop.css">
    <style>
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
    <main id="main-content" class="container main-content-shop">
        <div class="section-title">
            <h2>All Products & Genuine Parts</h2>
        </div>

        <div class="search-bar-container">
            <input type="text" id="product-search-input" class="search-input-field" placeholder="Search catalog...">
        </div>

        <div class="card-grid product-grid" id="product-grid-container">
            <?php foreach ($products as $p):
                $priceFormatted = is_numeric($p['price']) ? '₱' . number_format($p['price'], 2) : $p['price'];
                $isFeatured = !empty($p['featured']);
                $cardClass = $isFeatured ? 'product-card product-card-featured' : 'product-card';
                $btnClass = $isFeatured ? 'btn btn-cyan btn-block' : 'btn btn-primary btn-block';
            ?>
                <div class="<?= $cardClass ?>" data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>">
                    <div class="product-image">
                        <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                    </div>
                    <h3 class="product-name"><?= htmlspecialchars($p['name']) ?></h3>
                    <div class="product-price"><?= $priceFormatted ?></div>
                    <div class="product-card-actions">
                        <button onclick="addToCart(<?= $p['id'] ?>)" class="btn btn-outline btn-block btn-cart-add"><i class="fa-solid fa-cart-plus"></i></button>
                        <button type="button" onclick="openBuyModal(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['name'], ENT_QUOTES)) ?>', <?= is_numeric($p['price']) ? $p['price'] : 0 ?>)" class="<?= $btnClass ?>">Buy Now</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- ==================== SITE FOOTER SECTION ==================== -->
    <?php require_once 'components/footer.php'; ?>

    <!-- ==================== SITE MODALS SECTION ==================== -->
    <?php require_once 'components/modals.php'; ?>

    <!-- ==================== JAVASCRIPT LOGIC SECTION ==================== -->
    <script>
        const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;

        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
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

        function showModalAlert(modalId, message, type = 'error') {
            const alertBox = document.getElementById(modalId + '-alert');
            if (alertBox) {
                alertBox.textContent = message;
                alertBox.className = 'modal-alert ' + type;
            }
        }

        function openBuyModal(productId, productName, productPrice) {
            if (!isLoggedIn) {
                openModal('login-modal');
                showModalAlert('login-modal', 'Please log in to continue with your purchase.', 'error');
                return;
            }
            document.getElementById('buy-product-id').value = productId;
            document.getElementById('buy-product-name').textContent = productName;
            document.getElementById('buy-product-price').textContent = typeof productPrice === 'number' ? '₱' + productPrice.toFixed(2) : productPrice;
            openModal('buy-modal');
        }

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
            const navToggleBtn = document.getElementById('nav-toggle-btn');
            const mainNav = document.getElementById('main-nav');
            if (navToggleBtn && mainNav) {
                navToggleBtn.addEventListener('click', () => {
                    const expanded = navToggleBtn.getAttribute('aria-expanded') === 'true' || false;
                    navToggleBtn.setAttribute('aria-expanded', !expanded);
                    mainNav.classList.toggle('active');
                });
            }

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

            document.querySelectorAll('.requires-auth').forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!isLoggedIn) {
                        e.preventDefault();
                        openModal('login-modal');
                        showModalAlert('login-modal', 'Please log in to continue.', 'error');
                    }
                });
            });

            window.addEventListener('click', function(e) {
                if (e.target.classList.contains('auth-modal')) {
                    e.target.classList.remove('active');
                    const alertBox = e.target.querySelector('.modal-alert');
                    if (alertBox) {
                        alertBox.className = 'modal-alert';
                        alertBox.textContent = '';
                    }
                }
            });

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

            handleFormSubmit('ajax-login-form', 'login.php', 'login-modal');
            handleFormSubmit('ajax-register-form', 'register.php', 'register-modal');
            handleFormSubmit('ajax-forgot-form', 'forgot_password.php', 'forgot-modal');
            handleFormSubmit('ajax-buy-form', 'process_checkout.php', 'buy-modal');
        });
    </script>
</body>

</html>