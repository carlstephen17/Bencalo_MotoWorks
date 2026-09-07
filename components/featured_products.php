<?php
// Ensure $products is available
$products = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM products WHERE active = 1 LIMIT 4");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Fallback handled below
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
?>

<!-- ==================== FEATURED PRODUCTS SECTION ==================== -->
<section id="products" class="container section-padding-top">
    <div class="section-title">
        <h2>Featured Products</h2>
    </div>

    <div class="card-grid product-grid" id="product-grid-container">
        <?php foreach ($products as $p):
            $priceFormatted = is_numeric($p['price']) ? '₱' . number_format($p['price'], 2) : $p['price'];
            $isFeatured = !empty($p['featured']);
            $cardClass = $isFeatured ? 'product-card product-card-featured' : 'product-card';
            $btnClass = $isFeatured ? 'btn btn-cyan btn-block' : 'btn btn-primary btn-block';
        ?>
            <div class="<?= $cardClass ?>">
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