<?php
require_once 'includes/config.php';
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
    }
}

$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += is_array($item) ? ($item['quantity'] ?? 1) : 1;
    }
}

$default_products = [
    ["id" => 1, "name" => "GIVI HPS 50.6 Stuttgart Solid Black Medium (H506FSBK)", "price" => 250.00, "image" => "images/featured_products/helmet.png", "featured" => 0],
    ["id" => 2, "name" => "MOTUL 300V 4T Factory Line 10W40 1L", "price" => 320.00, "image" => "images/featured_products/synthetic_oil.png", "featured" => 0],
    ["id" => 3, "name" => "KOSO Side Mirror", "price" => 320.00, "image" => "images/featured_products/h3420_side_mirror.png", "featured" => 0],
    ["id" => 4, "name" => "Michelin Pilot Sport 4 225/40 ZR18 92Y XL", "price" => 325.00, "image" => "images/featured_products/tire.webp", "featured" => 0],
    ["id" => 5, "name" => "NGK Iridium IX Spark Plug", "price" => 45.00, "image" => "images/products/iridium.png", "featured" => 0],
    ["id" => 6, "name" => "Brembo High Performance Brake Pads", "price" => 180.00, "image" => "images/products/brembo.png", "featured" => 0],
    ["id" => 7, "name" => "DID Heavy Duty Racing Chain & Sprocket Set", "price" => 1250.00, "image" => "images/products/chain.png", "featured" => 0],
    ["id" => 8, "name" => "Racing Boy (RCB) S1 Series Brake Master Cylinder", "price" => 950.00, "image" => "images/products/brake.png", "featured" => 0]
];

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

        /* Flying Add-to-Cart Slow-Mo Animation Styles */
        .flying-cart-clone {
            position: fixed;
            z-index: 99999;
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ffc107;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            pointer-events: none;
            /* Smooth slow-mo transition */
            transition: all 1.8s cubic-bezier(0.25, 1, 0.5, 1);
        }

        @keyframes cartBadgeBounce {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.4);
                background-color: #ffc107;
                color: #000;
            }
            100% {
                transform: scale(1);
            }
        }

        .cart-badge-bounce {
            animation: cartBadgeBounce 0.5s ease-in-out;
        }
    </style>
</head>

<body>

    <a href="#main-content" class="skip-link">Skip to main content</a>

    <?php require_once 'components/header.php'; ?>

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

                $stockLevel = 0;
                foreach (['stock_level', 'stock', 'quantity', 'qty'] as $col) {
                    if (isset($p[$col])) {
                        $stockLevel = (int)$p[$col];
                        break;
                    }
                }
            ?>
                <div class="<?= $cardClass ?>" data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>">
                    <div class="product-image">
                        <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                    </div>
                    <h3 class="product-name"><?= htmlspecialchars($p['name']) ?></h3>
                    <div class="product-price"><?= $priceFormatted ?></div>

                    <div class="product-stock" style="font-size: 0.85rem; margin: 5px 0 10px 0; color: <?= ($stockLevel > 0) ? '#2e7d32' : '#c62828' ?>;">
                        Stock: <strong><?= $stockLevel ?></strong> available
                    </div>

                    <div class="product-card-actions">
                        <button type="button" onclick="openCartModal(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['name'], ENT_QUOTES)) ?>', <?= is_numeric($p['price']) ? $p['price'] : 0 ?>, <?= $stockLevel ?>)" class="btn btn-outline btn-block btn-cart-add" <?= ($stockLevel <= 0) ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : '' ?>><i class="fa-solid fa-cart-plus"></i></button>
                        <?php if ($stockLevel > 0): ?>
                            <button type="button" onclick="openBuyModal(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['name'], ENT_QUOTES)) ?>', <?= is_numeric($p['price']) ? $p['price'] : 0 ?>, <?= $stockLevel ?>)" class="<?= $btnClass ?>">Buy Now</button>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary btn-block" disabled style="background: #ccc; cursor: not-allowed; border-color: #ccc;">Out of Stock</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <?php require_once 'components/footer.php'; ?>
    <?php require_once 'components/modals.php'; ?>

    <script>
        // Global variables and declarations defined strictly once
        if (typeof window.isLoggedIn === 'undefined') {
            window.isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
        }
        if (typeof window.maxStock === 'undefined') {
            window.maxStock = 1;
        }

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add('active');
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.remove('active');
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

        function openBuyModal(productId, productName, productPrice, stockLevel = 1) {
            if (!window.isLoggedIn) {
                openModal('login-modal');
                showModalAlert('login-modal', 'Please log in to continue with your purchase.', 'error');
                return;
            }
            document.getElementById('buy-product-id').value = productId;
            document.getElementById('buy-product-name').textContent = productName;
            document.getElementById('buy-product-price').textContent = typeof productPrice === 'number' ? '₱' + productPrice.toFixed(2) : productPrice;

            window.maxStock = stockLevel;
            const qtyInput = document.getElementById('buy-quantity');
            if (qtyInput) {
                qtyInput.max = stockLevel;
                qtyInput.min = 1;
                qtyInput.value = 1;
                qtyInput.dataset.productId = productId;
            }
            const stockWarning = document.getElementById('buy-stock-warning');
            if (stockWarning) {
                stockWarning.style.display = 'none';
            }

            openModal('buy-modal');
        }

        function openCartModal(productId, productName, productPrice, stockLevel = 1) {
            if (!window.isLoggedIn) {
                openModal('login-modal');
                showModalAlert('login-modal', 'Please log in to add items to your cart.', 'error');
                return;
            }

            const modalId = 'cart-modal';
            const idField = document.getElementById('cart-product-id');
            const nameEl = document.getElementById('cart-product-name');
            const priceEl = document.getElementById('cart-product-price');
            const qtyInput = document.getElementById('cart-quantity');
            const warning = document.getElementById('cart-stock-warning');

            if (idField) idField.value = productId;
            if (nameEl) nameEl.textContent = productName;
            if (priceEl) priceEl.textContent = typeof productPrice === 'number' ? '₱' + productPrice.toFixed(2) : productPrice;

            if (qtyInput) {
                qtyInput.max = stockLevel;
                qtyInput.min = 1;
                qtyInput.value = 1;
                qtyInput.dataset.maxStock = stockLevel;
            }
            if (warning) {
                warning.style.display = 'none';
            }

            openModal(modalId);
        }

        function validateStockLimit() {
            const qtyInput = document.getElementById('buy-quantity');
            const warning = document.getElementById('buy-stock-warning');
            if (qtyInput && warning) {
                let currentVal = parseInt(qtyInput.value) || 1;
                if (currentVal > window.maxStock) {
                    warning.innerText = 'Maximum available stock is ' + window.maxStock;
                    warning.style.display = 'block';
                    qtyInput.value = window.maxStock;
                } else if (currentVal < 1) {
                    qtyInput.value = 1;
                    warning.style.display = 'none';
                } else {
                    warning.style.display = 'none';
                }
            }
        }

        function validateCartStockLimit() {
            const qtyInput = document.getElementById('cart-quantity');
            const warning = document.getElementById('cart-stock-warning');
            if (qtyInput && warning) {
                let maxStockVal = parseInt(qtyInput.dataset.maxStock) || 1;
                let currentVal = parseInt(qtyInput.value) || 1;
                if (currentVal > maxStockVal) {
                    warning.innerText = 'Maximum available stock is ' + maxStockVal;
                    warning.style.display = 'block';
                    qtyInput.value = maxStockVal;
                } else if (currentVal < 1) {
                    qtyInput.value = 1;
                    warning.style.display = 'none';
                } else {
                    warning.style.display = 'none';
                }
            }
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

            const qtyInput = document.getElementById('buy-quantity');
            if (qtyInput) {
                qtyInput.addEventListener('input', validateStockLimit);
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
                    if (!window.isLoggedIn) {
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

            function handleFormSubmit(formId, endpoint, modalId) {
                const form = document.getElementById(formId);
                if (form) {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const formData = new FormData(form);

                        // Capture product context and original button position if submitting from cart modal
                        let sourceImg = null;
                        let startRect = null;
                        if (formId === 'ajax-cart-form') {
                            const prodId = formData.get('product_id');
                            const card = document.querySelector(`.product-card [onclick*="openCartModal(${prodId},"]`)?.closest('.product-card');
                            if (card) {
                                sourceImg = card.querySelector('.product-image img');
                                if (sourceImg) {
                                    startRect = sourceImg.getBoundingClientRect();
                                }
                            }
                        }

                        fetch(endpoint, {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                // 1. Display success message inside the modal first
                                showModalAlert(modalId, data.message || 'Successfully added to cart!', 'success');

                                if (formId === 'ajax-buy-form') {
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 1000);
                                } else if (formId === 'ajax-cart-form') {
                                    // 2. Keep modal open briefly to let user read the success alert, then close it and trigger the slow-mo animation
                                    setTimeout(() => {
                                        closeModal(modalId);

                                        if (sourceImg && startRect) {
                                            const cartIcon = document.querySelector('.header-cart-link, .fa-cart-shopping');
                                            if (cartIcon) {
                                                const cartRect = cartIcon.getBoundingClientRect();

                                                const clone = document.createElement('img');
                                                clone.src = sourceImg.src;
                                                clone.className = 'flying-cart-clone';
                                                clone.style.left = startRect.left + 'px';
                                                clone.style.top = startRect.top + 'px';
                                                // Start at normal size relative to card image
                                                clone.style.transform = 'scale(1)';
                                                document.body.appendChild(clone);

                                                // Force reflow
                                                clone.getBoundingClientRect();

                                                // Stage 1 (Midway): Move towards cart and get bigger (magnify effect)
                                                setTimeout(() => {
                                                    const midX = (startRect.left + cartRect.left) / 2;
                                                    const midY = Math.min(startRect.top, cartRect.top) - 80;
                                                    clone.style.left = midX + 'px';
                                                    clone.style.top = midY + 'px';
                                                    clone.style.transform = 'scale(1.7)';
                                                }, 40);

                                                // Stage 2 (Arrival): Reach top right cart icon and normalize/shrink back down as it enters
                                                setTimeout(() => {
                                                    clone.style.left = (cartRect.left + cartRect.width / 2 - 25) + 'px';
                                                    clone.style.top = (cartRect.top + cartRect.height / 2 - 25) + 'px';
                                                    clone.style.opacity = '0.2';
                                                    clone.style.transform = 'scale(0.3)';
                                                }, 950);

                                                // Cleanup clone and bounce header badge when finished
                                                setTimeout(() => {
                                                    clone.remove();
                                                    if (typeof data.cart_count !== 'undefined') {
                                                        const badge = document.getElementById('cart-count-badge');
                                                        if (badge) {
                                                            badge.textContent = data.cart_count;
                                                            badge.classList.add('cart-badge-bounce');
                                                            setTimeout(() => badge.classList.remove('cart-badge-bounce'), 500);
                                                        }
                                                    }
                                                }, 1820);
                                            }
                                        }
                                    }, 800); // Wait 800ms so user clearly sees the success alert inside the modal before it closes
                                } else {
                                    if (data.redirect) {
                                        setTimeout(() => window.location.href = data.redirect, 1000);
                                    } else {
                                        setTimeout(() => window.location.reload(), 1000);
                                    }
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
            handleFormSubmit('ajax-cart-form', 'cart_action.php', 'cart-modal');
        });
    </script>
</body>

</html>