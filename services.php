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

// Fetch Logged-in User's Full Name and Phone Number for Booking Autofill
$user_fullname = '';
$user_phone = '';
if (isset($_SESSION['user_id']) && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT first_name, last_name, phone FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($userData) {
            $firstName = $userData['first_name'] ?? '';
            $lastName = $userData['last_name'] ?? '';
            $user_fullname = trim($firstName . ' ' . $lastName);
            if (empty($user_fullname)) {
                $user_fullname = $_SESSION['first_name'] ?? '';
            }
            if (!empty($userData['phone'])) {
                $user_phone = $userData['phone'];
            }
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

// Define default_services globally so it's always in scope
$default_services = [
    ["id" => 1, "name" => "Complete Periodic Maintenance Service (PMS)", "price" => 450.00, "image" => "images/featured_products/synthetic_oil.png", "featured" => 1],
    ["id" => 2, "name" => "Engine Tuning & Diagnostic Scan", "price" => 350.00, "image" => "images/featured_products/spark_plug.png", "featured" => 0],
    ["id" => 3, "name" => "Suspension Overhaul & Tuning", "price" => 600.00, "image" => "images/featured_products/h3420_side_mirror.png", "featured" => 0],
    ["id" => 4, "name" => "Brake System Flush & Pad Replacement", "price" => 250.00, "image" => "images/featured_products/brake_pads.png", "featured" => 0],
    ["id" => 5, "name" => "Tire Mount, Balance & Alignment", "price" => 300.00, "image" => "images/featured_products/tire.webp", "featured" => 0],
    ["id" => 6, "name" => "Chain & Sprocket Replacement Service", "price" => 200.00, "image" => "images/featured_products/brake_pads.png", "featured" => 0],
    ["id" => 7, "name" => "Electrical System & Wiring Repair", "price" => 280.00, "image" => "images/featured_products/spark_plug.png", "featured" => 0],
    ["id" => 8, "name" => "Coolant Flush & Radiator Servicing", "price" => 320.00, "image" => "images/featured_products/coolant.png", "featured" => 0]
];

// Fetch ALL Services from Database with Auto-Seeding & Sync
$services = [];
if (isset($pdo)) {
    try {
        $stmtUpsert = $pdo->prepare("
            INSERT INTO services (id, name, price, image, active, featured) 
            VALUES (?, ?, ?, ?, 1, ?)
            ON DUPLICATE KEY UPDATE 
                name = VALUES(name), 
                price = VALUES(price), 
                image = VALUES(image),
                featured = VALUES(featured)
        ");

        foreach ($default_services as $ds) {
            $stmtUpsert->execute([$ds['id'], $ds['name'], $ds['price'], $ds['image'], $ds['featured']]);
        }

        $stmt = $pdo->query("SELECT * FROM services WHERE active = 1 ORDER BY id DESC");
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $services = $default_services;
    }
} else {
    $services = $default_services;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - Bencalo MotoWorks</title>
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

        /* Match existing modal styling variables/classes natively */
        #service-booking-modal .auth-modal-content {
            max-height: 90vh;
            overflow-y: auto;
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
            <h2>Professional Services & Tune-Ups</h2>
        </div>

        <div class="search-bar-container">
            <input type="text" id="product-search-input" class="search-input-field" placeholder="Search services...">
        </div>

        <div class="card-grid product-grid" id="product-grid-container">
            <?php foreach ($services as $s):
                $priceFormatted = is_numeric($s['price']) ? '₱' . number_format($s['price'], 2) : $s['price'];
                $isFeatured = !empty($s['featured']);
                $cardClass = $isFeatured ? 'product-card product-card-featured' : 'product-card';
                $btnClass = $isFeatured ? 'btn btn-cyan' : 'btn btn-primary';
            ?>
                <div class="<?= $cardClass ?>" data-name="<?= strtolower(htmlspecialchars($s['name'])) ?>">
                    <div class="product-image">
                        <img src="<?= htmlspecialchars($s['image']) ?>" alt="<?= htmlspecialchars($s['name']) ?>">
                    </div>
                    <h3 class="product-name"><?= htmlspecialchars($s['name']) ?></h3>
                    <div class="product-price"><?= $priceFormatted ?></div>
                    <div class="product-card-actions">
                        <button onclick="addToCart(<?= $s['id'] ?>)" class="btn btn-outline btn-cart-add" aria-label="Add to cart"><i class="fa-solid fa-cart-plus"></i></button>
                        <button type="button" onclick="openServiceBookingModal(<?= $s['id'] ?>, '<?= addslashes(htmlspecialchars($s['name'], ENT_QUOTES)) ?>')" class="<?= $btnClass ?>">Book Appointment</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- ==================== SITE FOOTER SECTION ==================== -->
    <?php require_once 'components/footer.php'; ?>

    <!-- ==================== SITE MODALS SECTION ==================== -->
    <?php require_once 'components/modals.php'; ?>

    <!-- ==================== SERVICES BOOKING MODAL ==================== -->
    <div id="service-booking-modal" class="auth-modal">
        <div class="auth-modal-content">
            <span class="auth-close-btn" onclick="closeModal('service-booking-modal')">&times;</span>

            <div class="auth-modal-header">
                <h2>Book Service Appointment</h2>
                <p>Schedule your vehicle maintenance with Bencalo MotoWorks</p>
            </div>

            <div id="service-booking-modal-alert" class="modal-alert"></div>

            <form id="ajax-service-booking-form" action="process_booking.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" id="booking-service-id" name="service_id">

                <div class="auth-form-group">
                    <label>Selected Service</label>
                    <div class="auth-input-wrapper" style="background: rgba(255,255,255,0.03); cursor: default;">
                        <i class="fa-solid fa-wrench"></i>
                        <span id="booking-service-name" style="padding: 12px 14px 12px 40px; font-size: 14px; font-weight: 600; color: inherit; width: 100%; display: inline-block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Select a service</span>
                    </div>
                </div>

                <div style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin: 15px 0 10px; opacity: 0.8;">
                    1. Personal Information
                </div>

                <div class="auth-form-group">
                    <label for="booking-fullname">Full Name</label>
                    <div class="auth-input-wrapper">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" id="booking-fullname" name="fullname" placeholder="Enter your full name" value="<?= htmlspecialchars($user_fullname) ?>" required>
                    </div>
                </div>

                <div class="auth-form-group">
                    <label for="booking-contact">Contact Number</label>
                    <div class="auth-input-wrapper">
                        <i class="fa-solid fa-phone"></i>
                        <input type="tel" id="booking-contact" name="contact" placeholder="e.g., 09123456789" value="<?= htmlspecialchars($user_phone) ?>" required>
                    </div>
                </div>

                <div style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin: 20px 0 10px; opacity: 0.8;">
                    2. Vehicle Details
                </div>

                <div class="auth-form-group">
                    <label for="booking-vehicle">Vehicle Model / Type</label>
                    <div class="auth-input-wrapper">
                        <i class="fa-solid fa-motorcycle"></i>
                        <input type="text" id="booking-vehicle" name="vehicle" placeholder="e.g., Yamaha NMAX / Honda Click" required>
                    </div>
                </div>

                <div style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin: 20px 0 10px; opacity: 0.8;">
                    3. Schedule Preferences
                </div>

                <div class="auth-form-group">
                    <label for="booking-date">Preferred Date</label>
                    <div class="auth-input-wrapper">
                        <i class="fa-solid fa-calendar-days"></i>
                        <input type="date" id="booking-date" name="booking_date" required min="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="auth-form-group">
                    <label for="booking-time">Preferred Time Slot</label>
                    <div class="auth-input-wrapper">
                        <i class="fa-solid fa-clock"></i>
                        <select id="booking-time" name="booking_time" required style="width: 100%; background: transparent; border: none; color: inherit; outline: none; padding: 12px 14px 12px 40px; font-family: inherit; font-size: 14px; cursor: pointer;">
                            <option value="" disabled selected style="background: #1a1a1a; color: #fff;">Select time slot</option>
                            <option value="09:00 AM - 10:30 AM" style="background: #1a1a1a; color: #fff;">09:00 AM - 10:30 AM</option>
                            <option value="10:30 AM - 12:00 PM" style="background: #1a1a1a; color: #fff;">10:30 AM - 12:00 PM</option>
                            <option value="01:00 PM - 02:30 PM" style="background: #1a1a1a; color: #fff;">01:00 PM - 02:30 PM</option>
                            <option value="02:30 PM - 04:00 PM" style="background: #1a1a1a; color: #fff;">02:30 PM - 04:00 PM</option>
                            <option value="04:00 PM - 05:30 PM" style="background: #1a1a1a; color: #fff;">04:00 PM - 05:30 PM</option>
                        </select>
                    </div>
                </div>

                <div class="auth-form-group">
                    <label for="booking-notes">Additional Notes / Special Requests (Optional)</label>
                    <div class="auth-input-wrapper" style="height: auto; align-items: flex-start;">
                        <i class="fa-solid fa-note-sticky" style="top: 14px;"></i>
                        <textarea id="booking-notes" name="notes" placeholder="Describe any specific issues or custom requests..." style="width: 100%; background: transparent; border: none; outline: none; color: inherit; padding: 12px 14px 12px 40px; font-family: inherit; font-size: 14px; resize: vertical; min-height: 80px;"></textarea>
                    </div>
                </div>

                <button type="submit" class="auth-submit-btn" style="width: 100%; margin-top: 10px;">Confirm Service Appointment</button>
            </form>
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

        function openServiceBookingModal(serviceId, serviceName) {
            if (!isLoggedIn) {
                openModal('login-modal');
                showModalAlert('login-modal', 'Please log in to book a service appointment.', 'error');
                return;
            }
            document.getElementById('booking-service-id').value = serviceId;
            document.getElementById('booking-service-name').textContent = serviceName;
            openModal('service-booking-modal');
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
                        const badge = document.getElementById('cart-count-badge');
                        if (badge) badge.textContent = data.cart_count;
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
                            .then(async res => {
                                const text = await res.text();
                                try {
                                    return JSON.parse(text);
                                } catch (err) {
                                    throw new Error(text || 'Invalid server response');
                                }
                            })
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
                            .catch((err) => {
                                showModalAlert(modalId, err.message || 'Request failed. Please try again.', 'error');
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
            handleFormSubmit('ajax-service-booking-form', 'process_booking.php', 'service-booking-modal');
        });
    </script>
</body>

</html>