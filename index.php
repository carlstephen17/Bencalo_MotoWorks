<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF Token for security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$configFile = __DIR__ . '/includes/config.php';
if (file_exists($configFile)) {
    require_once $configFile;
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

// Base Default Testimonials
$default_testimonials = [
    [
        "name" => "Robert Smith",
        "location" => "Washington, D.C.",
        "message" => "Great service every time! Friendly and the mechanics always explain what they are doing. Trustworthy and reliable.",
        "image" => ""
    ],
    [
        "name" => "Emily Johnson",
        "location" => "San Francisco, California",
        "message" => "Saved me a lot of trouble! They diagnosed an engine issue that another shop couldn't fix. Excellent technical knowledge.",
        "image" => ""
    ],
    [
        "name" => "Alice Johnson",
        "location" => "Los Angeles, California",
        "message" => "Bencalo Motoworks is my go-to place for maintenance. They always use quality parts, and my car runs like a dream. Highly recommended!",
        "image" => ""
    ],
    [
        "name" => "John Doe",
        "location" => "Washington, D.C.",
        "message" => "Ordering parts through their website was seamless, and the delivery was quick. The parts are exact and exactly what I needed.",
        "image" => ""
    ]
];

// Fetch Latest Approved Testimonials from Database
$db_testimonials = [];
if (isset($pdo)) {
    try {
        // Check if the image column exists by attempting to select it
        $stmt = $pdo->prepare("SELECT name, subject, message, created_at, image FROM testimonials WHERE status = 'approved' ORDER BY created_at DESC LIMIT 4");
        $stmt->execute();
        $db_testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Fallback if the image column hasn't been added to your database table yet
        try {
            $stmt = $pdo->prepare("SELECT name, subject, message, created_at FROM testimonials WHERE status = 'approved' ORDER BY created_at DESC LIMIT 4");
            $stmt->execute();
            $db_testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $ex) {
            // Fallback silently
        }
    }
}

// Overlay new messages sequentially onto the static slots (1st message -> slot 0, 2nd -> slot 1, etc.)
$testimonials_to_display = $default_testimonials;
foreach ($db_testimonials as $index => $db_t) {
    if (isset($testimonials_to_display[$index])) {
        $subject = trim($db_t['subject'] ?? '');
        $message = $db_t['message'] ?? '';
        if ($subject === '' && preg_match('/^Subject:\s*(.*?)\R\R(.*)$/s', $message, $matches)) {
            $subject = trim($matches[1]);
            $message = $matches[2];
        }
        $testimonials_to_display[$index] = [
            "name" => $db_t['name'],
            "location" => "Contact Page Review",
            "subject" => $subject,
            "message" => $message,
            "image" => !empty($db_t['image']) ? $db_t['image'] : ''
        ];
    }
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
    <link rel="stylesheet" href="styles/services.css">

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

    <?php require_once 'components/header.php'; ?>

    <main id="main-content">
        <?php require_once 'components/hero.php'; ?>
        <?php require_once 'components/featured_products.php'; ?>
        <?php require_once 'components/featured_services.php'; ?>
        <?php require_once 'components/featured_promo.php'; ?>
        <?php require_once 'components/get_started.php'; ?>
    </main>

    <!-- ==================== TESTIMONIALS SECTION ==================== -->
    <section class="testimonials-section" style="padding: 60px 20px; background: var(--color-bg, #121212);">
        <div class="container">
            <div class="section-header text-center" style="margin-bottom: 40px;">
                <h2 style="font-size: 2.2rem; font-weight: 800; color: #fff; text-transform: uppercase; letter-spacing: 1px;">HERE’S THE REASON WHY YOU SHOULD CHOOSE US</h2>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 25px;">
                <?php foreach ($testimonials_to_display as $t): ?>
                    <div style="background: var(--color-card-bg, #1e1e1e); border: 1px solid var(--color-border, #2a2a2a); border-radius: 12px; padding: 30px; box-shadow: 0 8px 25px rgba(0,0,0,0.3); display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                                <div style="width: 50px; height: 50px; border-radius: 50%; background: #333; display: flex; align-items: center; justify-content: center; color: var(--color-primary, #00bcd4); font-size: 1.2rem; overflow: hidden; flex-shrink: 0;">
                                    <?php if (!empty($t['image'])): ?>
                                        <img src="<?= htmlspecialchars($t['image']) ?>" alt="<?= htmlspecialchars($t['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="fa-solid fa-user"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4 style="font-size: 1rem; font-weight: 700; color: #fff; margin: 0 0 2px 0;"><?= htmlspecialchars($t['name']) ?></h4>
                                    <span style="font-size: 0.8rem; color: var(--color-text-muted, #a0a0a0);"><?= htmlspecialchars($t['location']) ?></span>
                                </div>
                            </div>
                            <div style="color: #00bcd4; margin-bottom: 15px; font-size: 0.9rem;">
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                                <i class="fa-solid fa-star"></i>
                            </div>
                            <?php if (!empty($t['subject'])): ?>
                                <div style="font-size: 0.9rem; line-height: 1.5; color: #fff; font-weight: 600; margin-bottom: 8px;">Subject: <?= htmlspecialchars($t['subject']) ?></div>
                            <?php endif; ?>
                            <p style="font-size: 0.95rem; line-height: 1.6; color: var(--color-text-muted, #d0d0d0); font-style: italic; margin: 0;">"<?= htmlspecialchars($t['message']) ?>"</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php require_once 'components/footer.php'; ?>
    <?php require_once 'components/modals.php'; ?>

    <script>
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
            const params = new URLSearchParams(window.location.search);
            if (params.get('admin_login') === '1') {
                openModal('login-modal');
                window.history.replaceState({}, document.title, window.location.pathname);
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
        });
    </script>
</body>

</html>