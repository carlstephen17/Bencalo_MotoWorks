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