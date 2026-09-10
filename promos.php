<?php
// promos.php - Updated user promo availment page with robust modal submission
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';

$userId = $_SESSION['user_id'] ?? null;

$userData = [];
if ($userId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

$userName = trim(
    ($userData['first_name'] ?? '') . ' ' .
    ($userData['last_name'] ?? '')
);

if ($userName === '') {
    $userName = $userData['username'] ?? '';
}

$userPhone = $userData['phone'] ?? $userData['contact_number'] ?? '';

$promos = [
    [
        'id' => 1,
        'slug' => 'tire_service',
        'title' => 'Tire + Mounting Service Bundle',
        'badge' => '15% OFF',
        'badge_class' => 'badge-pending',
        'product_image' => 'images/featured_products/tire.webp',
        'service_image' => 'images/featured_services/wheel_and_tire_service.png',
        'product_icon' => 'fa-solid fa-circle-dot',
        'service_icon' => 'fa-solid fa-screwdriver-wrench',
        'description' => 'Purchase any eligible motorcycle tire and pair it with a professional mounting, balancing, and alignment service at the same time.',
        'item_label' => 'Select Tire Model / Size',
        'item_options' => [
            'Michelin Pilot Street 2 (90/80-14 Tubeless)',
            'Pirelli Diablo Rosso Sport (100/80-14 Tubeless)',
            'IRC NR73 Tubeless Tire (80/90-17)'
        ],
        'perks' => [
            'Eligible on all standard and tubeless tires',
            'Includes labor and wheel balancing adjustment'
        ]
    ],
    [
        'id' => 2,
        'slug' => 'oil_tuneup',
        'title' => 'Synthetic Oil Change + Tune-Up',
        'badge' => '20% OFF',
        'badge_class' => 'badge-pending',
        'product_image' => 'images/featured_products/synthetic_oil.png  ',
        'service_image' => 'images/featured_services/premium_oil_and_change_service.png',
        'product_icon' => 'fa-solid fa-oil-can',
        'service_icon' => 'fa-solid fa-gauge-high',
        'description' => 'Keep your engine running smoothly with high-grade synthetic oil paired with a complete carb/FI diagnostic tune-up service.',
        'item_label' => 'Select Oil Viscosity & Type',
        'item_options' => [
            'Motul 7100 4T 10W-40 Synthetic (1L)',
            'Shell Advance Ultra 10W-40 Fully Synthetic (1L)',
            'Castrol Power1 Ultimate 10W-50 (1L)'
        ],
        'perks' => [
            'Includes 1L high-performance synthetic oil',
            'Full engine diagnostic & idle tuning'
        ]
    ],
    [
        'id' => 3,
        'slug' => 'chain_sprocket',
        'title' => 'Heavy Duty Chain Kit + Install',
        'badge' => '10% OFF',
        'badge_class' => 'badge-completed',
        'product_image' => 'images/products/chain.png',
        'service_image' => 'images/services/chain.png',
        'product_icon' => 'fa-solid fa-link',
        'service_icon' => 'fa-solid fa-gear',
        'description' => 'Upgrade your drive train with a heavy-duty chain and sprocket set bundled together with precision professional installation.',
        'item_label' => 'Select Chain & Sprocket Set',
        'item_options' => [
            'SSS Heavy Duty Chain & Sprocket Set (428H x 14T/38T)',
            'DID O-Ring Heavy Duty Chain Combo (520V)',
            'RK Racing Gold Chain & Steel Sprocket Kit'
        ],
        'perks' => [
            'O-ring or X-ring heavy duty durability',
            'Includes chain cleaning and tension adjustment'
        ]
    ],
    [
        'id' => 4,
        'slug' => 'brake_overhaul',
        'title' => 'Brake Pad Set + Fluid Bleeding',
        'badge' => '15% OFF',
        'badge_class' => 'badge-pending',
        'product_image' => 'images/products/brake.png',
        'service_image' => 'images/services/brake.png',
        'product_icon' => 'fa-solid fa-compact-disc',
        'service_icon' => 'fa-solid fa-droplet',
        'description' => 'Ensure maximum stopping power by bundling front/rear ceramic brake pads with a complete hydraulic fluid flush and bleed service.',
        'item_label' => 'Select Brake Pad Compound',
        'item_options' => [
            'Brembo Ceramic Front/Rear Brake Pads',
            'EBC FA Series Sintered Brake Pads',
            'Bendix Heavy Duty Street Moto Pads'
        ],
        'perks' => [
            'High-friction ceramic compound pads',
            'Complete DOT-4 brake fluid replacement'
        ]
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Active Promos & Bundles - Bencalo MotoWorks</title>
    <link rel="stylesheet" href="styles/index/style.css">
    <link rel="stylesheet" href="styles/index/components.css">
    <link rel="stylesheet" href="styles/index/layout.css">
    <link rel="stylesheet" href="styles/promos.css">
    <link rel="stylesheet" href="styles/index/modals.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .alert-toast {
            position: fixed; top: 20px; right: 20px; z-index: 9999;
            padding: 15px 20px; border-radius: 6px; color: #fff; font-weight: 500;
            display: none; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .alert-success { background-color: #2ed573; }
        .alert-error { background-color: #ff4757; }
    </style>
</head>
<body>
    <?php require_once 'components/header.php'; ?>

    <div id="alertToast" class="alert-toast"></div>

    <div class="promos-wrapper">
        <div class="promos-container">
            <h2><i class="fa-solid fa-tags"></i> Active Moto Promos & Bundles</h2>
            <p class="promos-subtitle">Combine product purchases with expert shop services to automatically unlock exclusive package discounts.</p>

            <div class="promo-grid">
                <?php foreach ($promos as $promo): ?>
                    <div class="promo-card">
                        <div class="promo-image-showcase">
                            <div class="showcase-img-box">
                                <img src="<?= htmlspecialchars($promo['product_image']) ?>" alt="Product" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <i class="<?= htmlspecialchars($promo['product_icon']) ?>" style="display:none; position:absolute; font-size: 1.8rem; color: #00d2d3;"></i>
                                <span class="img-label">Product</span>
                            </div>
                            <div class="showcase-plus"><i class="fa-solid fa-plus"></i></div>
                            <div class="showcase-img-box">
                                <img src="<?= htmlspecialchars($promo['service_image']) ?>" alt="Service" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <i class="<?= htmlspecialchars($promo['service_icon']) ?>" style="display:none; position:absolute; font-size: 1.8rem; color: #00d2d3;"></i>
                                <span class="img-label">Service</span>
                            </div>
                        </div>

                        <div class="promo-card-header" style="margin-top: 15px;">
                            <span class="promo-title"><i class="fa-solid fa-circle-dot" style="color: #00d2d3;"></i> <?= htmlspecialchars($promo['title']) ?></span>
                            <span class="badge <?= $promo['badge_class'] ?>"><?= $promo['badge'] ?></span>
                        </div>
                        
                        <div class="promo-card-body">
                            <p><?= htmlspecialchars($promo['description']) ?></p>
                            <div class="promo-details-list">
                                <?php foreach ($promo['perks'] as $perk): ?>
                                    <div><i class="fa-solid fa-check" style="color: #2ed573;"></i> <?= htmlspecialchars($perk) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="promo-card-footer">
                            <button type="button" class="btn-avail-bundle" onclick="openClaimModal('<?= htmlspecialchars($promo['slug'], ENT_QUOTES) ?>', '<?= htmlspecialchars($promo['title'], ENT_QUOTES) ?>', '<?= htmlspecialchars($promo['item_label'], ENT_QUOTES) ?>', <?= htmlspecialchars(json_encode($promo['item_options'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)">
                                <i class="fa-solid fa-cart-shopping"></i> Avail This Bundle
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 30px;">
                <a href="promo_history.php" class="back-link"><i class="fa-solid fa-clock-rotate-left"></i> View My Promo Redemptions History</a>
            </div>
        </div>
    </div>

    <!-- Claim Modal Structure -->
    <div id="claimModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); justify-content:center; align-items:center; z-index:1000;">
        <div class="modal-content" style="background:#1e2124; padding:30px; border-radius:8px; width:450px; color:#fff; position:relative;">
            <h3 id="modalBundleTitle" style="margin-bottom: 15px; color:#00FFFF;">Avail Promo Bundle</h3>
            <form id="claimForm" onsubmit="submitClaim(event)">
                <input type="hidden" id="modalBundleSlug" name="bundle_slug">
                
                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px; font-size:0.9rem;">Full Name</label>
                    <input type="text" name="fullname" value="<?= htmlspecialchars($userName) ?>" required style="width:100%; padding:10px; background:#111; border:1px solid #333; color:#fff; border-radius:4px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px; font-size:0.9rem;">Contact Phone</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($userPhone, ENT_QUOTES, 'UTF-8') ?>" required style="width:100%; padding:10px; background:#111; border:1px solid #333; color:#fff; border-radius:4px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label id="modalItemLabel" style="display:block; margin-bottom:5px; font-size:0.9rem;">Select Option</label>
                    <select id="modalItemOptions" name="selected_option" required style="width:100%; padding:10px; background:#111; border:1px solid #333; color:#fff; border-radius:4px;"></select>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px; font-size:0.9rem;">Appointment Date</label>
                    <input type="date" name="appointment_date" required min="<?= date('Y-m-d') ?>" style="width:100%; padding:10px; background:#111; border:1px solid #333; color:#fff; color-scheme:dark; border-radius:4px;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom:5px; font-size:0.9rem;">Preferred Time Slot</label>
                    <select name="appointment_time" required style="width:100%; padding:10px; background:#111; border:1px solid #333; color:#fff; border-radius:4px;">
                        <option value="09:00 AM - 10:30 AM">09:00 AM - 10:30 AM</option>
                        <option value="10:30 AM - 12:00 PM">10:30 AM - 12:00 PM</option>
                        <option value="01:00 PM - 02:30 PM">01:00 PM - 02:30 PM</option>
                        <option value="04:00 PM - 05:30 PM">04:00 PM - 05:30 PM</option>
                    </select>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" onclick="closeClaimModal()" style="padding:10px 20px; background:#333; border:none; color:#fff; border-radius:4px; cursor:pointer;">Cancel</button>
                    <button type="submit" id="submitClaimBtn" style="padding:10px 20px; background:#00FFFF; border:none; color:#000; font-weight:bold; border-radius:4px; cursor:pointer;">Confirm & Avail</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openClaimModal(slug, title, itemLabel, options) {
            document.getElementById('modalBundleSlug').value = slug;
            document.getElementById('modalBundleTitle').textContent = title;
            document.getElementById('modalItemLabel').textContent = itemLabel;
            
            const selectDropdown = document.getElementById('modalItemOptions');
            selectDropdown.innerHTML = '';
            
            if (Array.isArray(options)) {
                options.forEach(function(option) {
                    let opt = document.createElement('option');
                    opt.value = option;
                    opt.textContent = option;
                    selectDropdown.appendChild(opt);
                });
            }

            document.getElementById('claimModal').style.display = 'flex';
        }

        function closeClaimModal() {
            document.getElementById('claimModal').style.display = 'none';
        }

        function showAlert(message, type) {
            const toast = document.getElementById('alertToast');
            toast.textContent = message;
            toast.className = 'alert-toast ' + (type === 'success' ? 'alert-success' : 'alert-error');
            toast.style.display = 'block';
            setTimeout(() => { toast.style.display = 'none'; }, 4000);
        }

        function submitClaim(event) {
            event.preventDefault();
            const btn = document.getElementById('submitClaimBtn');
            btn.disabled = true;
            btn.textContent = 'Processing...';

            const formData = new FormData(document.getElementById('claimForm'));

            fetch('claim_promo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.textContent = 'Confirm & Avail';
                if (data.success) {
                    showAlert(data.message, 'success');
                    closeClaimModal();
                    setTimeout(() => { window.location.href = 'promo_history.php'; }, 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.textContent = 'Confirm & Avail';
                showAlert('An unexpected network error occurred.', 'error');
            });
        }

        window.onclick = function(event) {
            let modal = document.getElementById('claimModal');
            if (event.target === modal) {
                closeClaimModal();
            }
        }
    </script>

    <?php require_once 'components/footer.php'; ?>
</body>
</html>