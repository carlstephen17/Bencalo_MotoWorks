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

    <a href="#main-content" class="skip-link">Skip to main content</a>

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
                <a href="#login" class="btn-login-header">Log In</a>
                <a href="#signup" class="btn-signup-header">Sign Up</a>
            </div>

            <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="main-nav">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <main id="main-content">
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
                    $btnClass = $p['featured'] ? 'btn btn-cyan btn-block' : 'btn btn-primary btn-block';
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
                        <a href="#" class="btn btn-primary btn-block">Book Service</a>
                    </div>';
                }
                ?>
            </div>
        </section>

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
                        <a href="#" class="btn-cyan">Book Appointment</a>
                        <a href="#" class="btn-outline">Explore Parts</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

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
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </div>
                <div class="footer-social">
                    <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </footer>

</body>

</html>