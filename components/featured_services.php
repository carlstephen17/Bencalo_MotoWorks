<?php
// Ensure $services is available even if included independently
if (!isset($services)) {
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
}

// Fallback image mapping matching service IDs when database values are missing
$default_images = [
    1 => 'images/featured_services/premium_oil_and_change_service.png',
    2 => 'images/featured_services/battery_service.png',
    3 => 'images/featured_services/wheel_and_tire_service.png',
    4 => 'images/featured_services/vehicle_washing_service.png'
];
?>
<!-- ==================== FEATURED SERVICES SECTION ==================== -->
<section id="services" class="container section-padding-top">
    <div class="section-title">
        <h2>Featured Services</h2>
    </div>
    <div class="card-grid service-grid">
        <?php foreach ($services as $s): ?>
            <?php
                $id   = $s['service_id'] ?? $s['id'] ?? '';
                $name = $s['service_name'] ?? $s['name'] ?? 'Service';
                $desc = $s['description'] ?? $s['service_description'] ?? $s['desc'] ?? '';
                
                // Get raw image path from DB column
                $raw_icon = $s['image_url'] ?? $s['image'] ?? $s['icon'] ?? $s['icon_path'] ?? '';
                
                if (!empty($raw_icon)) {
                    $icon = ltrim(str_replace('../', '', $raw_icon), '/');
                } else {
                    // Fall back to distinct images per ID instead of repeating the oil bottle
                    $icon = $default_images[$id] ?? 'images/featured_services/premium_oil_and_change_service.png';
                }
            ?>
            <div class="service-card">
                <div class="service-icon">
                    <img src="<?= htmlspecialchars($icon) ?>" alt="<?= htmlspecialchars($name) ?>">
                </div>
                <h3 class="service-name"><?= htmlspecialchars($name) ?></h3>
                <?php if (!empty($desc)): ?>
                    <p class="service-desc"><?= htmlspecialchars($desc) ?></p>
                <?php endif; ?>
                <a href="booking.php?service_id=<?= htmlspecialchars($id) ?>" class="btn btn-primary btn-block requires-auth">Book Service</a>
            </div>
        <?php endforeach; ?>
    </div>
</section>