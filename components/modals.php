<?php
// Ensure session and CSRF token are available
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$user_phone = $user_phone ?? '';
?>

<!-- ==================== BUY NOW / CHECKOUT MODAL ==================== -->
<div id="buy-modal" class="auth-modal">
    <div class="auth-modal-content modal-sm">
        <button class="auth-modal-close" onclick="closeModal('buy-modal')">&times;</button>
        <h2 class="modal-heading">Complete Your Order</h2>

        <div id="buy-modal-alert" class="modal-alert"></div>

        <div id="buy-modal-product-summary" class="product-summary-box">
            <div class="buy-product-name" id="buy-product-name">Product Name</div>
            <div class="buy-product-price" id="buy-product-price">₱0.00</div>
        </div>

        <form id="ajax-buy-form" class="auth-form" action="process_checkout.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="product_id" id="buy-product-id">

            <div class="form-row-2col">
                <input type="text" name="full_name" placeholder="Full Name" required class="auth-input auth-input-half" value="<?= htmlspecialchars($_SESSION['first_name'] ?? '') ?>">
                <!-- Phone autofilled from database registration record -->
                <input type="tel" name="phone" placeholder="Phone Number" required class="auth-input auth-input-half" value="<?= htmlspecialchars($user_phone) ?>">
            </div>

            <textarea name="address" placeholder="Shipping Address" required class="auth-input auth-textarea"></textarea>

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

<!-- ==================== SERVICES BOOKING MODAL ==================== -->
<div id="service-booking-modal" class="auth-modal">
    <div class="auth-modal-content" style="max-width: 550px;">
        <span class="auth-close-btn" onclick="closeModal('service-booking-modal')">&times;</span>

        <div class="auth-modal-header">
            <h2>Book Service Appointment</h2>
            <p>Schedule your vehicle maintenance with Bencalo MotoWorks</p>
        </div>

        <div id="service-booking-modal-alert" class="modal-alert"></div>

        <form id="ajax-service-booking-form" action="process_booking.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" id="booking-service-id" name="service_id">

            <!-- Selected Service Display -->
            <div style="background: rgba(255,255,255,0.05); padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.1);">
                <span style="font-size: 12px; color: var(--text-muted); display: block; text-transform: uppercase; letter-spacing: 0.5px;">Selected Service</span>
                <span id="booking-service-name" style="font-size: 16px; font-weight: 600; color: var(--primary-accent);">Select a service</span>
            </div>

            <!-- Personal Information Section -->
            <div style="font-size: 14px; font-weight: 600; color: var(--text-main); margin-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 6px;">
                1. Personal Information
            </div>

            <div class="auth-form-group">
                <label for="booking-fullname">Full Name</label>
                <div class="auth-input-wrapper">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="booking-fullname" name="fullname" placeholder="Enter your full name" value="<?= htmlspecialchars($_SESSION['first_name'] ?? '') ?>" required>
                </div>
            </div>

            <div class="auth-form-group">
                <label for="booking-contact">Contact Number</label>
                <div class="auth-input-wrapper">
                    <i class="fa-solid fa-phone"></i>
                    <input type="tel" id="booking-contact" name="contact" placeholder="e.g., 09123456789" value="<?= htmlspecialchars($user_phone ?? '') ?>" required>
                </div>
            </div>

            <!-- Vehicle Information Section -->
            <div style="font-size: 14px; font-weight: 600; color: var(--text-main); margin: 20px 0 12px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 6px;">
                2. Vehicle Details
            </div>

            <div class="auth-form-group">
                <label for="booking-vehicle">Vehicle Model / Type</label>
                <div class="auth-input-wrapper">
                    <i class="fa-solid fa-motorcycle"></i>
                    <input type="text" id="booking-vehicle" name="vehicle" placeholder="e.g., Yamaha NMAX / Honda Click" required>
                </div>
            </div>

            <!-- Schedule Preferences Section -->
            <div style="font-size: 14px; font-weight: 600; color: var(--text-main); margin: 20px 0 12px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 6px;">
                3. Schedule Preference
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
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
                        <select id="booking-time" name="booking_time" required style="width: 100%; background: transparent; border: none; color: inherit; outline: none; padding-left: 35px; font-family: inherit; font-size: 14px;">
                            <option value="" disabled selected style="background: #1e1e1e; color: #fff;">Select time slot</option>
                            <option value="09:00 AM - 10:30 AM" style="background: #1e1e1e; color: #fff;">09:00 AM - 10:30 AM</option>
                            <option value="10:30 AM - 12:00 PM" style="background: #1e1e1e; color: #fff;">10:30 AM - 12:00 PM</option>
                            <option value="01:00 PM - 02:30 PM" style="background: #1e1e1e; color: #fff;">01:00 PM - 02:30 PM</option>
                            <option value="02:30 PM - 04:00 PM" style="background: #1e1e1e; color: #fff;">02:30 PM - 04:00 PM</option>
                            <option value="04:00 PM - 05:30 PM" style="background: #1e1e1e; color: #fff;">04:00 PM - 05:30 PM</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="auth-form-group" style="margin-top: 15px;">
                <label for="booking-notes">Additional Notes / Special Requests (Optional)</label>
                <textarea id="booking-notes" name="notes" placeholder="Describe any specific issues or custom requests..." style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; padding: 10px; color: inherit; font-family: inherit; font-size: 14px; resize: vertical; min-height: 80px;"></textarea>
            </div>

            <button type="submit" class="auth-submit-btn" style="width: 100%; margin-top: 10px;">Confirm Service Appointment</button>
        </form>
    </div>
</div>

<script>
    /* Open Service Booking Modal Flow */
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

    document.addEventListener('DOMContentLoaded', () => {
        // Register form handler for service booking modal AJAX submission
        handleFormSubmit('ajax-service-booking-form', 'process_booking.php', 'service-booking-modal');
    });
</script>

<!-- ==================== LOGIN MODAL ==================== -->
<div id="login-modal" class="auth-modal">
    <div class="auth-modal-content">
        <button class="auth-modal-close" onclick="closeModal('login-modal')">&times;</button>
        <h2 class="modal-heading-lg">Log In</h2>

        <div id="login-modal-alert" class="modal-alert"></div>

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

        <div id="register-modal-alert" class="modal-alert"></div>

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

        <div id="forgot-modal-alert" class="modal-alert"></div>

        <form id="ajax-forgot-form" class="auth-form" action="forgot-password.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="email" name="email" placeholder="Enter your email address" required class="auth-input auth-input-padded">
            <button type="submit" class="btn btn-primary btn-block auth-btn btn-full-width">Send Reset Link</button>
        </form>
        <p class="auth-modal-footer"><a href="#" onclick="switchModal('forgot-modal', 'login-modal')" class="auth-link">Back to Log In</a></p>
    </div>
</div>