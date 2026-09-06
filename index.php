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
</head>

<body>

    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- Toast Notifications Container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- ==================== SITE HEADER SECTION ==================== -->
    <header class="site-header">
        <div class="container header-inner">
            <a href="#" class="logo">
                <img src="images/header/Bencalo MotoWorks Logo.svg" alt="Bencalo MotoWorks Logo" class="logo-img">
            </a>

            <nav class="main-nav" id="main-nav">
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="shop.php">Shop</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </nav>

            <div class="header-cta-group">
                <a href="cart.php" class="cart-link-icon" aria-label="Shopping Cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="cart-badge" id="cart-count-badge"><?= $cart_count ?></span>
                </a>

                <a href="booking.php" class="btn btn-primary">Book Appointment</a>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-dropdown">
                        <button class="btn-signup-header">
                            Hello, <?= htmlspecialchars($_SESSION['first_name'] ?? 'User') ?> <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="user-menu">
                            <a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a>
                            <a href="my-appointments.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a>
                            <a href="my-orders.php"><i class="fa-solid fa-box"></i> Order History</a>
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

    <!-- ==================== MAIN CONTENT WRAPPER ==================== -->
    <main id="main-content">

        <!-- ==================== HERO SECTION ==================== -->
        <section id="home" class="hero">
            <img src="images/hero/hero_section.png" alt="Motorcycle workshop background" class="hero-bg">
            <div class="hero-overlay"></div>
            <div class="container hero-content">
                <h1>Your One-Stop Shop for Parts & Vehicle Care</h1>
                <p>Buy genuine auto parts online and schedule maintenance, repairs, or vehicle washing all in one place.</p>
                <div class="btn-row">
                    <a href="shop.php" class="btn btn-primary">Shop Now</a>
                    <a href="#services" class="btn btn-outline">Browse Services</a>
                </div>
            </div>
        </section>

        <!-- ==================== FEATURED PRODUCTS SECTION ==================== -->
        <section id="products" class="container section-padding-top">
            <div class="section-title">
                <h2>Featured Products</h2>
            </div>

            <!-- Instant Search Filter -->
            <div class="search-bar-container">
                <input type="text" id="product-search-input" class="search-input-field" placeholder="Search products by name...">
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
                            <button onclick="addToCart(<?= $p['id'] ?>)" class="btn btn-outline btn-block btn-cart-icon"><i class="fa-solid fa-cart-plus"></i></button>
                            <button type="button" onclick="openBuyModal(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['name'], ENT_QUOTES)) ?>', <?= is_numeric($p['price']) ? $p['price'] : 0 ?>)" class="<?= $btnClass ?>">Buy Now</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ==================== FEATURED SERVICES SECTION ==================== -->
        <section id="services" class="container section-padding-top">
            <div class="section-title">
                <h2>Featured Services</h2>
            </div>
            <div class="card-grid service-grid">
                <?php foreach ($services as $s): ?>
                    <div class="service-card">
                        <div class="service-icon">
                            <img src="<?= htmlspecialchars($s['icon']) ?>" alt="<?= htmlspecialchars($s['name']) ?>">
                        </div>
                        <h3 class="service-name"><?= htmlspecialchars($s['name']) ?></h3>
                        <p class="service-desc"><?= htmlspecialchars($s['desc']) ?></p>
                        <a href="booking.php?service_id=<?= $s['id'] ?>" class="btn btn-primary btn-block requires-auth">Book Service</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ==================== FEATURED PROMO SECTION ==================== -->
        <section class="container section-padding-top">
            <div class="section-title">
                <h2>Featured Promo</h2>
            </div>
            <div class="promo-card">
                <div class="promo-image-bg">
                    <img src="images/featured_promo/featured_promo_image.jpg" alt="Featured Promo">
                </div>
                <div class="promo-overlay"></div>
                <div class="promo-content">
                    <h2>BUNDLE & SAVE: GET 15% OFF <br>WHEN YOU BUY PARTS + SERVICE.</h2>
                    <ul class="promo-list">
                        <li>Includes genuine parts.</li>
                        <li>Expert installation and services.</li>
                        <li>15% total savings.</li>
                        <li>Applicable to all makes & models.</li>
                    </ul>
                    <div class="promo-actions">
                        <a href="promos.php" class="btn btn-primary">VIEW ALL PROMOS</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== CONTACT / GET STARTED SECTION ==================== -->
        <section id="contact" class="container section-padding-top">
            <div class="cta">
                <div class="cta-bg">
                    <img src="images/get_started/get_started_car.avif" alt="Workshop background">
                </div>
                <div class="cta-overlay"></div>
                <div class="cta-content">
                    <div class="cta-text">
                        <span class="eyebrow">Get Started</span>
                        <h2>READY TO UPGRADE<br>YOUR RIDE?</h2>
                        <p>Schedule your service appointment or order premium<br>genuine parts online in just a few clicks.</p>
                    </div>
                    <div class="cta-buttons btn-row">
                        <a href="booking.php" class="btn-cyan requires-auth">Book Appointment</a>
                        <a href="shop.php" class="btn-outline">Explore Parts</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ==================== TESTIMONIALS SECTION ==================== -->
    <section id="about" class="container section-padding-y">
        <div class="section-title center">
            <h2>HERE’S THE REASON WHY YOU SHOULD CHOOSE US</h2>
        </div>
        <div class="card-grid testimonial-grid">
            <?php
            $testimonials = [
                ["name" => "Robert Smith", "location" => "Washington, D.C.", "text" => "Great service every time! Friendly and the mechanics always explain what they are doing. Trustworthy and reliable.", "image" => "images/testimonials/testimonial_1.png"],
                ["name" => "Emily Johnson", "location" => "San Francisco, California", "text" => "Saved me a lot of trouble! They diagnosed an engine issue that another shop couldn't fix. Excellent technical knowledge.", "image" => "images/testimonials/testimonial_2.png"],
                ["name" => "Alice Johnson", "location" => "Los Angeles, California", "text" => "Bencalo Motoworks is my go-to place for maintenance. They always use quality parts, and my car runs like a dream. Highly recommended!", "image" => "images/testimonials/testimonial_3.png"],
                ["name" => "John Doe", "location" => "Washington, D.C.", "text" => "Ordering parts through their website was seamless, and the delivery was quick. The parts are exact and exactly what I needed.", "image" => "images/testimonials/testimonial_4.png"]
            ];

            foreach ($testimonials as $t): ?>
                <div class="testimonial-card">
                    <div class="testimonial-header">
                        <img src="<?= htmlspecialchars($t['image']) ?>" alt="<?= htmlspecialchars($t['name']) ?>" class="testimonial-avatar">
                        <div class="testimonial-meta">
                            <h3 class="testimonial-name"><?= htmlspecialchars($t['name']) ?></h3>
                            <div class="testimonial-location"><?= htmlspecialchars($t['location']) ?></div>
                        </div>
                    </div>
                    <div class="stars">★★★★★</div>
                    <p class="testimonial-text">"<?= htmlspecialchars($t['text']) ?>"</p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ==================== SITE FOOTER SECTION ==================== -->
    <footer class="site-footer">
        <div class="container footer-container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="logo">
                        <img src="images/header/Bencalo MotoWorks Logo.svg" alt="Bencalo MotoWorks Logo" class="footer-logo-img">
                    </div>
                    <p>Expert parts and vehicle care.<br>Driven by passion.</p>
                    <ul>
                        <li><a href="#about">Our Team</a></li>
                        <li><a href="#about">About Us</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Maintenance Guides</h4>
                    <ul>
                        <li><a href="#">5 Signs Your Battery is Failing</a></li>
                        <li><a href="#">Importance of Oil Changes</a></li>
                        <li><a href="#">Proper Tire Inflation Guide</a></li>
                        <li><a href="#">Cooling System Maintenance</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Customer Support</h4>
                    <ul>
                        <li><a href="profile.php">My Account</a></li>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Terms & Conditions</a></li>
                    </ul>
                </div>
                <div class="footer-col footer-contact">
                    <h4>Contact Us</h4>
                    <ul>
                        <li><i class="fa-solid fa-location-dot"></i> Bencalo Motoworks - Bollos Street, Boyco, Bayawan City, Negros Oriental, Philippines 6221</li>
                        <li><i class="fa-solid fa-phone"></i> (123)-456-7890</li>
                        <li><i class="fa-solid fa-envelope"></i> bencalomotoworks@gmail.com</li>
                        <li><i class="fa-solid fa-clock"></i> Mon-Sat: 8:00 AM – 6:00 PM,<br>Sun: CLOSED</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="footer-bottom-inner">
                <p>&copy; <?= date('Y') ?> BENCALO MOTOWORKS. All Rights Reserved.</p>
                <div class="footer-links">
                    <a href="#" class="privacy-policy">Privacy Policy</a>
                    <a href="#" class="terms-of-service">Terms of Service</a>
                </div>
                <div class="footer-social">
                    <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- ==================== BUY NOW / CHECKOUT MODAL ==================== -->
    <div id="buy-modal" class="auth-modal">
        <div class="auth-modal-content modal-sm">
            <button class="auth-modal-close" onclick="closeModal('buy-modal')">&times;</button>
            <h2 class="modal-heading">Complete Your Order</h2>
            
            <div id="buy-modal-product-summary" class="product-summary-box">
                <div class="buy-product-name" id="buy-product-name">Product Name</div>
                <div class="buy-product-price" id="buy-product-price">₱0.00</div>
            </div>

            <form id="ajax-buy-form" class="auth-form" action="process_checkout.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="product_id" id="buy-product-id">
                
                <div class="form-row-2col">
                    <input type="text" name="full_name" placeholder="Full Name" required class="auth-input auth-input-half" value="<?= htmlspecialchars($_SESSION['first_name'] ?? '') ?>">
                    <input type="tel" name="phone" placeholder="Phone Number" required class="auth-input auth-input-half">
                </div>

                <textarea name="shipping_address" placeholder="Shipping Address" required class="auth-input auth-textarea"></textarea>

                <div class="form-group-mb">
                    <label class="form-label-muted">Payment Method:</label>
                    <select name="payment_method" required class="auth-input">
                        <option value="cod">Cash on Delivery (COD)</option>
                        <option value="gcash">GCash / E-Wallet</option>
                        <option value="card">Credit / Debit Card</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-block auth-btn btn-full-width">Place Order</button>
            </form>
        </div>
    </div>

    <!-- ==================== LOGIN MODAL ==================== -->
    <div id="login-modal" class="auth-modal">
        <div class="auth-modal-content">
            <button class="auth-modal-close" onclick="closeModal('login-modal')">&times;</button>
            <h2 class="modal-heading-lg">Log In</h2>

            <form id="ajax-login-form" class="auth-form" action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="text" name="username" placeholder="Username or Email" required autocomplete="username" class="auth-input auth-input-padded">

                <div class="password-container mb-sm">
                    <input type="password" id="login-password" name="password" placeholder="Password" required autocomplete="current-password" class="auth-input auth-input-padded-r">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('login-password', this)">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                <div class="auth-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember"> Remember Me
                    </label>
                    <a href="#" onclick="switchModal('login-modal', 'forgot-modal')" class="auth-link auth-link-primary">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-block auth-btn btn-full-width">Log In</button>
            </form>
            <p class="auth-modal-footer">Don't have an account? <a href="#" onclick="switchModal('login-modal', 'register-modal')" class="auth-link">Register</a></p>
        </div>
    </div>

    <!-- ==================== REGISTER MODAL ==================== -->
    <div id="register-modal" class="auth-modal">
        <div class="auth-modal-content">
            <button class="auth-modal-close" onclick="closeModal('register-modal')">&times;</button>
            <h2 class="modal-heading-lg">Create Account</h2>

            <form id="ajax-register-form" class="auth-form" action="register.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="text" name="username" placeholder="Username" required autocomplete="username" class="auth-input auth-input-mb">
                <div class="form-row-2col">
                    <input type="text" name="first_name" placeholder="First Name" required class="auth-input auth-input-half">
                    <input type="text" name="last_name" placeholder="Last Name" required class="auth-input auth-input-half">
                </div>
                <input type="email" name="email" placeholder="Email Address" required autocomplete="email" class="auth-input auth-input-mb">
                <input type="text" name="phone" placeholder="Phone Number" required autocomplete="tel" class="auth-input auth-input-mb">

                <div class="password-container mb-md">
                    <input type="password" id="register-password" name="password" placeholder="Password" required autocomplete="new-password" class="auth-input auth-input-padded-r">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('register-password', this)">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                <button type="submit" class="btn btn-primary btn-block auth-btn btn-full-width">Sign Up</button>
            </form>
            <p class="auth-modal-footer">Already have an account? <a href="#" onclick="switchModal('register-modal', 'login-modal')" class="auth-link">Log In</a></p>
        </div>
    </div>

    <!-- ==================== FORGOT PASSWORD MODAL ==================== -->
    <div id="forgot-modal" class="auth-modal">
        <div class="auth-modal-content">
            <button class="auth-modal-close" onclick="closeModal('forgot-modal')">&times;</button>
            <h2 class="modal-heading">Reset Password</h2>
            <p class="modal-subtitle">Enter your email address to receive password reset instructions.</p>

            <form id="ajax-forgot-form" class="auth-form" action="forgot-password.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="email" name="email" placeholder="Enter your email address" required class="auth-input auth-input-padded">
                <button type="submit" class="btn btn-primary btn-block auth-btn btn-full-width">Send Reset Link</button>
            </form>
            <p class="auth-modal-footer"><a href="#" onclick="switchModal('forgot-modal', 'login-modal')" class="auth-link">Back to Log In</a></p>
        </div>
    </div>

    <!-- ==================== JAVASCRIPT LOGIC SECTION ==================== -->
    <script>
        const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;

        /* Toast Notifications */
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `<span>${message}</span><i class="fa-solid fa-xmark toast-close-icon" onclick="this.parentElement.remove()"></i>`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        /* Modal Functions */
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function switchModal(closeId, openId) {
            closeModal(closeId);
            openModal(openId);
        }

        /* Open Buy Modal Flow */
        function openBuyModal(productId, productName, productPrice) {
            if (!isLoggedIn) {
                showToast("Please log in to continue with your purchase.", "info");
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
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=add&product_id=${productId}&csrf_token=<?= $_SESSION['csrf_token'] ?>`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast("Product added to cart!", "success");
                    document.getElementById('cart-count-badge').textContent = data.cart_count;
                } else {
                    showToast(data.message || "Failed to add product.", "error");
                }
            })
            .catch(() => showToast("Item added to cart session.", "info"));
        }

        document.addEventListener('DOMContentLoaded', () => {

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
                        showToast("Please log in to continue.", "info");
                        openModal('login-modal');
                    }
                });
            });

            /* Modal Backdrop Click Close */
            window.addEventListener('click', function(e) {
                if (e.target.classList.contains('auth-modal')) {
                    e.target.classList.remove('active');
                }
            });

            /* Generic AJAX Form Submit Handler */
            function handleFormSubmit(formId, endpoint, modalId) {
                const form = document.getElementById(formId);
                if (form) {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const formData = new FormData(form);

                        fetch(endpoint, { method: 'POST', body: formData })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    showToast(data.message, "success");
                                    if (modalId) closeModal(modalId);
                                    if (data.redirect) {
                                        setTimeout(() => window.location.href = data.redirect, 1000);
                                    } else {
                                        setTimeout(() => window.location.reload(), 1000);
                                    }
                                } else {
                                    showToast(data.message || "An error occurred.", "error");
                                }
                            })
                            .catch(() => showToast("Request failed. Please try again.", "error"));
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