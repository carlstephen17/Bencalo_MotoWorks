<?php
session_start();

// Auto-login via Remember Me Cookie if session is not set
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    require_once 'config.php';
    $token = $_COOKIE['remember_token'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['role'] = $user['role'] ?? 'user';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bencalo MotoWorks</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/style.css">
</head>

<body>

    <!-- ==================== SKIP LINK ==================== -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- ==================== SITE HEADER SECTION ==================== -->
    <header class="site-header">
        <div class="container header-inner">
            <a href="#" class="logo">
                <img src="images/header/Bencalo MotoWorks Logo.svg" alt="Bencalo MotoWorks Logo" class="logo-img">
            </a>

            <nav class="main-nav" id="main-nav">
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#products">Shop</a></li>
                    <li><a href="#services">Guides</a></li>
                    <li><a href="#about">About Us</a></li>
                </ul>
            </nav>

            <div class="header-cta-group">
                <a href="#contact" class="btn btn-primary">Book Appointment</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span style="font-size: 0.9rem; color: var(--color-border); font-weight: 600; margin-left: 10px;">
                        Hello, <?= htmlspecialchars($_SESSION['first_name'] ?? 'User') ?>!
                    </span>
                    <a href="logout.php" class="btn-signup-header">Log Out</a>
                <?php else: ?>
                    <a href="#" onclick="openModal('login-modal')" class="btn-login-header">Log In</a>
                    <a href="#" onclick="openModal('register-modal')" class="btn-signup-header">Sign Up</a>
                <?php endif; ?>
            </div>

            <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="main-nav">
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
                    <a href="#products" class="btn btn-primary">Shop Now</a>
                    <a href="#services" class="btn btn-outline">Browse Services</a>
                </div>
            </div>
        </section>

        <!-- ==================== FEATURED PRODUCTS SECTION ==================== -->
        <section id="products" class="container">
            <div class="section-title">
                <h2>Featured Products</h2>
            </div>
            <div class="card-grid product-grid">
                <?php
                $products = [
                    ["name" => "GIVI HPS 50.6 Stuttgart Solid Black Medium (H506FSBK)", "price" => "₱250.00", "image" => "images/featured_products/helmet.png", "featured" => false],
                    ["name" => "MOTUL 300V 4T Factory Line 10W40 1L", "price" => "₱320.00", "image" => "images/featured_products/synthetic_oil.png", "featured" => false],
                    ["name" => "KOSO Side Mirror", "price" => "₱320.00", "image" => "images/featured_products/h3420_side_mirror.png", "featured" => false],
                    ["name" => "Michelin Pilot Sport 4 225/40 ZR18 92Y XL", "price" => "₱325.00", "image" => "images/featured_products/tire.webp", "featured" => false]
                ];

                foreach ($products as $p) {
                    $cardClass = $p['featured'] ? 'product-card product-card-featured' : 'product-card';
                    $btnClass = $p['featured'] ? 'btn btn-cyan btn-block requires-auth' : 'btn btn-primary btn-block requires-auth';
                    echo '
            <div class="' . $cardClass . '">
                <div class="product-image"> 
                    <img src="' . $p['image'] . '" alt="' . $p['name'] . '">
                </div>
                <h3 class="product-name">' . $p['name'] . '</h3>
                <div class="product-price">' . $p['price'] . '</div>
                <a href="#" class="' . $btnClass . '">Buy Now</a>
            </div>';
                }
                ?>
            </div>
        </section>

        <!-- ==================== FEATURED SERVICES SECTION ==================== -->
        <section id="services" class="container">
            <div class="section-title">
                <h2>Featured Services</h2>
            </div>
            <div class="card-grid service-grid">
                <?php
                $services = [
                    ["name" => "Premium Oil & Change Service", "desc" => "Full synthetic oil, new filter, comprehensive check-up.", "icon" => "images/featured_services/premium_oil_and_change_service.png"],
                    ["name" => "Wheel & Tire Service", "desc" => "Mounting, precision balancing, and pressure check.", "icon" => "images/featured_services/wheel_and_tire_service.png"],
                    ["name" => "Vehicle Washing Service", "desc" => "Complete exterior wash, foam bath, wheel cleaning, and wax finishing", "icon" => "images/featured_services/vehicle_washing_service.png"],
                    ["name" => "Battery Service", "desc" => "Battery inspection, voltage testing, terminal cleaning, and battery replacement", "icon" => "images/featured_services/battery_service.png"]
                ];

                foreach ($services as $s) {
                    echo '
                    <div class="service-card">
                        <div class="service-icon">
                            <img src="' . $s['icon'] . '" alt="' . $s['name'] . '">
                        </div>
                        <h3 class="service-name">' . $s['name'] . '</h3>
                        <p class="service-desc">' . $s['desc'] . '</p>
                        <a href="#" class="btn btn-primary btn-block requires-auth">Book Service</a>
                    </div>';
                }
                ?>
            </div>
        </section>

        <!-- ==================== FEATURED PROMO SECTION ==================== -->
        <section class="container">
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
                        <a href="#contact" class="btn btn-primary">VIEW ALL PROMOS</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== CONTACT / GET STARTED SECTION ==================== -->
        <section id="contact" class="container">
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
                        <a href="#" class="btn-cyan requires-auth">Book Appointment</a>
                        <a href="#products" class="btn-outline">Explore Parts</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ==================== TESTIMONIALS SECTION ==================== -->
    <section class="container">
        <div class="section-title center">
            <h2>HERE’S THE REASON WHY YOU SHOULD CHOOSE US</h2>
        </div>
        <div class="card-grid testimonial-grid">
            <?php
            $testimonials = [
                [
                    "name" => "Robert Smith",
                    "location" => "Washington, D.C.",
                    "text" => "Great service every time! Friendly and the mechanics always explain what they are doing. Trustworthy and reliable.",
                    "image" => "images/testimonials/testimonial_1.png"
                ],
                [
                    "name" => "Emily Johnson",
                    "location" => "San Francisco, California",
                    "text" => "Saved me a lot of trouble! They diagnosed an engine issue that another shop couldn't fix. Excellent technical knowledge.",
                    "image" => "images/testimonials/testimonial_2.png"
                ],
                [
                    "name" => "Alice Johnson",
                    "location" => "Los Angeles, California",
                    "text" => "Bencalo Motoworks is my go-to place for maintenance. They always use quality parts, and my car runs like a dream. Highly recommended!",
                    "image" => "images/testimonials/testimonial_3.png"
                ],
                [
                    "name" => "John Doe",
                    "location" => "Washington, D.C.",
                    "text" => "Ordering parts through their website was seamless, and the delivery was quick. The parts are exact and exactly what I needed.",
                    "image" => "images/testimonials/testimonial_4.png"
                ]
            ];

            foreach ($testimonials as $t) {
                echo '
<div class="testimonial-card">
    <div class="testimonial-header">
        <img src="' . $t['image'] . '" alt="' . $t['name'] . '" class="testimonial-avatar">
        <div class="testimonial-meta">
            <h3 class="testimonial-name">' . $t['name'] . '</h3>
            <div class="testimonial-location">' . $t['location'] . '</div>
        </div>
    </div>
    <div class="stars">★★★★★</div>
    <p class="testimonial-text">"' . $t['text'] . '"</p>
</div>';
            }
            ?>
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
                        <li><a href="#">Our Team</a></li>
                        <li><a href="#">About Us</a></li>
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
                        <li><a href="#">My Account</a></li>
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
                <p>&copy; 2026 BENCALO MOTOWORKS. All Rights Reserved.</p>
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

    <!-- ==================== UPDATED LOGIN MODAL SNIPPET ==================== -->
    <div id="login-modal" class="auth-modal">
        <div class="auth-modal-content">
            <button class="auth-modal-close" onclick="closeModal('login-modal')">&times;</button>
            <h2 style="margin-bottom: 24px; text-align: center;">Log In</h2>

            <div id="login-message" style="font-size: 0.85rem; margin-bottom: 16px; text-align: center; font-weight: 600; display: none;"></div>

            <form id="ajax-login-form" class="auth-form" action="login.php" method="POST">
                <input type="text" name="username" placeholder="Username or Email" required autocomplete="username" class="auth-input" style="width:100%; padding:12px; margin-bottom:16px; background:var(--color-bg-alt); border:1px solid var(--color-primary); color:var(--color-text); border-radius:var(--radius-sm);">

                <div class="password-container" style="position: relative; width: 100%; margin-bottom: 12px;">
                    <input type="password" id="login-password" name="password" placeholder="Password" required autocomplete="current-password" class="auth-input" style="width:100%; padding:12px 40px 12px 12px; background:var(--color-bg-alt); border:1px solid var(--color-primary); color:var(--color-text); border-radius:var(--radius-sm);">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('login-password', this)" style="position: absolute; top: 50%; right: 12px; transform: translateY(-50%); background: transparent; border: none; color: var(--color-text-muted, #aaa); cursor: pointer; font-size: 0.95rem;">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                <div class="auth-options" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; font-size: 0.85rem;">
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; color: var(--color-text);">
                        <input type="checkbox" name="remember" style="cursor: pointer;"> Remember Me
                    </label>
                    <a href="#" onclick="switchModal('login-modal', 'forgot-modal')" class="auth-link" style="color: var(--color-primary); text-decoration: none;">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-block auth-btn" style="width:100%;">Log In</button>
            </form>
            <p class="auth-modal-footer" style="text-align: center; margin-top: 15px;">Don't have an account? <a href="#" onclick="switchModal('login-modal', 'register-modal')" class="auth-link">Register</a></p>
        </div>
    </div>

    <!-- ==================== UPDATED REGISTER MODAL SNIPPET ==================== -->
    <div id="register-modal" class="auth-modal">
        <div class="auth-modal-content">
            <button class="auth-modal-close" onclick="closeModal('register-modal')">&times;</button>
            <h2 style="margin-bottom: 24px; text-align: center;">Create Account</h2>

            <div id="register-message" style="font-size: 0.85rem; margin-bottom: 16px; text-align: center; font-weight: 600; display: none;"></div>

            <form id="ajax-register-form" class="auth-form" action="register.php" method="POST">
                <input type="text" name="username" placeholder="Username" required autocomplete="username" class="auth-input" style="width:100%; padding:10px; margin-bottom:12px; background:var(--color-bg-alt); border:1px solid var(--color-primary); color:var(--color-text); border-radius:var(--radius-sm);">
                <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                    <input type="text" name="first_name" placeholder="First Name" required class="auth-input" style="width:50%; padding:10px; background:var(--color-bg-alt); border:1px solid var(--color-primary); color:var(--color-text); border-radius:var(--radius-sm);">
                    <input type="text" name="last_name" placeholder="Last Name" required class="auth-input" style="width:50%; padding:10px; background:var(--color-bg-alt); border:1px solid var(--color-primary); color:var(--color-text); border-radius:var(--radius-sm);">
                </div>
                <input type="email" name="email" placeholder="Email Address" required autocomplete="email" class="auth-input" style="width:100%; padding:10px; margin-bottom:12px; background:var(--color-bg-alt); border:1px solid var(--color-primary); color:var(--color-text); border-radius:var(--radius-sm);">
                <input type="text" name="phone" placeholder="Phone Number" required autocomplete="tel" class="auth-input" style="width:100%; padding:10px; margin-bottom:12px; background:var(--color-bg-alt); border:1px solid var(--color-primary); color:var(--color-text); border-radius:var(--radius-sm);">

                <div class="password-container" style="position: relative; width: 100%; margin-bottom: 16px;">
                    <input type="password" id="register-password" name="password" placeholder="Password" required autocomplete="new-password" class="auth-input" style="width:100%; padding:10px 40px 10px 10px; background:var(--color-bg-alt); border:1px solid var(--color-primary); color:var(--color-text); border-radius:var(--radius-sm);">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('register-password', this)" style="position: absolute; top: 50%; right: 12px; transform: translateY(-50%); background: transparent; border: none; color: var(--color-text-muted, #aaa); cursor: pointer; font-size: 0.95rem;">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                <button type="submit" class="btn btn-primary btn-block auth-btn" style="width:100%;">Sign Up</button>
            </form>
            <p class="auth-modal-footer" style="text-align: center; margin-top: 15px;">Already have an account? <a href="#" onclick="switchModal('register-modal', 'login-modal')" class="auth-link">Log In</a></p>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgot-modal" class="auth-modal">
        <div class="auth-modal-content">
            <button class="auth-modal-close" onclick="closeModal('forgot-modal')">&times;</button>
            <h2 style="margin-bottom: 16px; text-align: center;">Reset Password</h2>
            <p style="font-size: 0.85rem; text-align: center; margin-bottom: 20px; color: var(--color-text-muted, #aaa);">Enter your account email and we'll send you recovery instructions.</p>

            <div id="forgot-message" style="font-size: 0.85rem; margin-bottom: 16px; text-align: center; font-weight: 600; display: none;"></div>

            <form id="ajax-forgot-form" class="auth-form" action="forgot-password.php" method="POST">
                <input type="email" name="email" placeholder="Enter your email address" required class="auth-input" style="width:100%; padding:12px; margin-bottom:16px; background:var(--color-bg-alt); border:1px solid var(--color-primary); color:var(--color-text); border-radius:var(--radius-sm);">
                <button type="submit" class="btn btn-primary btn-block auth-btn" style="width:100%;">Send Reset Link</button>
            </form>
            <p class="auth-modal-footer" style="text-align: center; margin-top: 15px;"><a href="#" onclick="switchModal('forgot-modal', 'login-modal')" class="auth-link">Back to Log In</a></p>
        </div>
    </div>

    <!-- ==================== JAVASCRIPT LOGIC SECTION ==================== -->
    <script>
        const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;

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

        /* ==================== PASSWORD TOGGLE FUNCTION (Add to your JS) ==================== */
        function togglePasswordVisibility(fieldId, btn) {
            const passwordField = document.getElementById(fieldId);
            const icon = btn.querySelector('i');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Require login handling for restricted buttons
            document.querySelectorAll('.requires-auth').forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!isLoggedIn) {
                        e.preventDefault();
                        openModal('login-modal');
                    }
                });
            });

            // Close modal when clicking on the blurred backdrop
            window.addEventListener('click', function(e) {
                if (e.target.classList.contains('auth-modal')) {
                    e.target.classList.remove('active');
                }
            });

            // AJAX Login Submission
            const loginForm = document.getElementById('ajax-login-form');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(loginForm);
                    const msgDiv = document.getElementById('login-message');

                    fetch('login.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            msgDiv.style.display = 'block';
                            msgDiv.style.color = data.success ? '#4cd137' : '#ff6b6b';
                            msgDiv.textContent = data.message;
                            if (data.success) {
                                setTimeout(() => window.location.reload(), 1000);
                            }
                        })
                        .catch(() => {
                            msgDiv.style.display = 'block';
                            msgDiv.style.color = '#ff6b6b';
                            msgDiv.textContent = "An error occurred. Please try again.";
                        });
                });
            }

            // AJAX Register Submission
            const registerForm = document.getElementById('ajax-register-form');
            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(registerForm);
                    const msgDiv = document.getElementById('register-message');

                    fetch('register.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            msgDiv.style.display = 'block';
                            msgDiv.style.color = data.success ? '#4cd137' : '#ff6b6b';
                            msgDiv.textContent = data.message;
                            if (data.success) {
                                setTimeout(() => switchModal('register-modal', 'login-modal'), 1500);
                            }
                        })
                        .catch(() => {
                            msgDiv.style.display = 'block';
                            msgDiv.style.color = '#ff6b6b';
                            msgDiv.textContent = "An error occurred. Please try again.";
                        });
                });
            }

            // AJAX Forgot Password Submission
            const forgotForm = document.getElementById('ajax-forgot-form');
            if (forgotForm) {
                forgotForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(forgotForm);
                    const msgDiv = document.getElementById('forgot-message');

                    fetch('forgot_password.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            msgDiv.style.display = 'block';
                            msgDiv.style.color = data.success ? '#4cd137' : '#ff6b6b';
                            msgDiv.textContent = data.message;
                            if (data.success) {
                                forgotForm.reset();
                            }
                        })
                        .catch(() => {
                            msgDiv.style.display = 'block';
                            msgDiv.style.color = '#ff6b6b';
                            msgDiv.textContent = "An error occurred. Please try again.";
                        });
                });
            }
        });
    </script>
</body>

</html>