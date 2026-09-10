<?php
// Ensure $services is available
$services = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM services WHERE active = 1 LIMIT 4");
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Fallback handled below
    }
}

// Fallback image mapping matching service IDs when database values are missing
$default_images = [
    1 => 'images/featured_services/premium_oil_and_change_service.png',
    2 => 'images/featured_services/battery_service.png',
    3 => 'images/featured_services/wheel_and_tire_service.png',
    4 => 'images/featured_services/vehicle_washing_service.png'
];

if (empty($services)) {
    $services = [
        ["id" => 1, "name" => "Premium Oil & Change Service", "desc" => "Full synthetic oil, new filter, comprehensive check-up.", "icon" => "images/featured_services/premium_oil_and_change_service.png"],
        ["id" => 2, "name" => "Wheel & Tire Service", "desc" => "Mounting, precision balancing, and pressure check.", "icon" => "images/featured_services/wheel_and_tire_service.png"],
        ["id" => 3, "name" => "Vehicle Washing Service", "desc" => "Complete exterior wash, foam bath, wheel cleaning, and wax finishing.", "icon" => "images/featured_services/vehicle_washing_service.png"],
        ["id" => 4, "name" => "Battery Service", "desc" => "Battery inspection, voltage testing, terminal cleaning, and battery replacement.", "icon" => "images/featured_services/battery_service.png"]
    ];
}
?>

<!-- ==================== FEATURED SERVICES SECTION ==================== -->
<section id="services" class="container section-padding-top">
    <div class="section-title">
        <h2>Featured Services</h2>
    </div>

    <div class="card-grid service-grid" id="service-grid-container">
        <?php foreach ($services as $index => $s):
            $id = $s['service_id'] ?? $s['id'] ?? ($index + 1);
            $name = $s['service_name'] ?? $s['name'] ?? 'Service';
            $desc = $s['description'] ?? $s['service_description'] ?? $s['desc'] ?? '';
            
            // Get raw image path from DB column, check multiple possible keys
            $raw_icon = $s['image_url'] ?? $s['image'] ?? $s['icon'] ?? $s['icon_path'] ?? '';

            if (!empty($raw_icon)) {
                $image = ltrim(str_replace('../', '', $raw_icon), '/');
            } else {
                $image = $default_images[$id] ?? $default_images[$index + 1] ?? 'images/featured_services/premium_oil_and_change_service.png';
            }

            $serviceJson = htmlspecialchars(json_encode([
                'id' => $id,
                'name' => $name,
                'desc' => $desc,
                'icon' => $image
            ]), ENT_QUOTES, 'UTF-8');
        ?>
            <div class="product-card service-card" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 8px; padding: 25px; display: flex; flex-direction: column; justify-content: space-between;">
                <div class="product-image" style="margin-bottom: 15px; text-align: center; height: 140px; display: flex; align-items: center; justify-content: center;">
                    <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($name) ?>" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                </div>
                <h3 class="product-name" style="font-size: 18px; font-weight: 600; color: var(--text-main, #ffffff); margin-bottom: 10px;"><?= htmlspecialchars($name) ?></h3>
                <?php if (!empty($desc)): ?>
                    <p class="product-desc" style="font-size: 14px; color: var(--text-muted, #a0a0a0); line-height: 1.5; margin-bottom: 20px;"><?= htmlspecialchars($desc) ?></p>
                <?php endif; ?>
                <div class="product-card-actions" style="display: flex; gap: 10px;">
                    <button type="button" onclick='openServiceModal(<?= $serviceJson ?>)' class="btn btn-outline btn-block" style="flex: 1; padding: 10px; cursor: pointer;">View Details</button>
                    <button type="button" onclick="handleServiceBooking(<?= $id ?>, '<?= addslashes(htmlspecialchars($name, ENT_QUOTES)) ?>')" class="btn btn-primary btn-block requires-auth" style="flex: 1; padding: 10px; font-weight: 500; cursor: pointer;">Book Now</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Service Details Modal Structure -->
<div id="serviceDetailModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); justify-content:center; align-items:center; z-index:1000;">
    <div class="modal-content" style="background:#1e2124; padding:30px; border-radius:8px; width:450px; color:#fff; position:relative; text-align:center;">
        <div id="modalServiceImageWrapper" style="margin-bottom:15px; height:120px; display:flex; align-items:center; justify-content:center;">
            <img id="modalServiceImg" src="" alt="" style="max-height:100%; max-width:100%; object-fit:contain;">
        </div>
        <h3 id="modalServiceName" style="color:#00FFFF; margin-bottom:10px;"></h3>
        <p id="modalServiceDesc" style="color:#ccc; font-size:0.9rem; margin-bottom:20px;"></p>
        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" onclick="closeServiceDetailModal()" style="padding:10px 20px; background:#333; border:none; color:#fff; border-radius:4px; cursor:pointer;">Close</button>
        </div>
    </div>
</div>

<script>
    function openServiceModal(service) {
        document.getElementById('modalServiceName').textContent = service.name;
        document.getElementById('modalServiceDesc').textContent = service.desc || 'No description available for this service.';
        document.getElementById('modalServiceImg').src = service.icon;
        document.getElementById('serviceDetailModal').style.display = 'flex';
    }

    function closeServiceDetailModal() {
        document.getElementById('serviceDetailModal').style.display = 'none';
    }

    function handleServiceBooking(serviceId, serviceName) {
        alert("Successfully selected booking for: " + serviceName);
        if (typeof openServiceBookingModal === 'function') {
            openServiceBookingModal(serviceId, serviceName);
        } else {
            window.location.href = 'booking.php?service_id=' + serviceId;
        }
    }

    // Close modal on outside click
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('serviceDetailModal');
        if (event.target === modal) {
            closeServiceDetailModal();
        }
    });
</script>