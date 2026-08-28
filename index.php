<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bencalo MotoWorks</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/style.css">
</head>

<body>

    <a href="#main-content" class="skip-link">Skip to main content</a>

    <header class="site-header">
        <div class="container header-inner">
            <a href="#" class="logo">
                <img src="styles/Bencalo MotoWorks Logo.svg" alt="Bencalo MotoWorks Logo" class="logo-img">
                </object>
            </a>

            <nav class="main-nav" id="main-nav">
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#products">Shop</a></li>
                    <li><a href="#services">Guides</a></li>
                    <li><a href="#about">About Us</a></li>
                </ul>
            </nav>

            <div class="header-cta">
                <a href="#contact" class="btn btn-primary">Book Appointment</a>
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
            <img src="styles/hero_section.png" alt="Motorcycle workshop background" class="hero-bg">
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
                    ["name" => "High-Performance Brake Pads", "price" => "₱1,250", "image" => ""],
                    ["name" => "Synthetic Motor Oil 1L", "price" => "₱450", "image" => ""],
                    ["name" => "Heavy-Duty Drive Chain", "price" => "₱1,890", "image" => ""],
                    ["name" => "LED Headlight Assembly", "price" => "₱950", "image" => ""]
                ];

                foreach ($products as $p) {
                    echo '
            <div class="product-card">
                <div class="product-image">
                    <img src="' . $p['image'] . '" alt="' . $p['name'] . '">
                </div>
                <h3 class="product-name">' . $p['name'] . '</h3>
                <div class="product-price">' . $p['price'] . '</div>
                <a href="#" class="btn btn-primary btn-block">Add to Cart</a>
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
                    ["name" => "Wheel & Tire Service", "desc" => "Mounting, precision balancing, and pressure check,", "icon" => "images/featured_services/wheel_and_tire_service.png"],
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
                        <a href="#" class="btn btn-primary btn-block">Book Appointment</a>
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
                <div class="promo-content">
                    <h2>BUNDLE & SAVE: GET 15% OFF
                        WHEN YOU BUY PARTS + SERVICE.</h2>
                    <ul class="promo-list">
                        <li>Includes genuine parts.Expert installation and services.</li>
                        <li>Expert installation and services</li>
                        <li>15% total savings.</li>
                        <li>Applicable to all makes & models.</li>
                    </ul>
                    <a href="#contact" class="btn btn-primary">View All Promos</a>
                </div>
                <div class="promo-image">
                    <img src="" alt="Mechanic working on a motorcycle">
                </div>
            </div>
        </section>

        <section id="contact" class="cta">
            <img src="" alt="Workshop background" class="cta-bg">
            <div class="cta-overlay"></div>
            <div class="container cta-content">
                <span>Get Started</span>
                <h2>Ready to Upgrade Your Ride?</h2>
                <p>Schedule your service appointment or order premium genuine parts online in just a few clicks.</p>
                <div class="btn-row">
                    <a href="#" class="btn btn-primary">Book Appointment</a>
                    <a href="#" class="btn btn-primary">Explore Parts</a>
                </div>
            </div>
        </section>
    </main>

    <section class="container">
        <div class="section-title">HERE’S THE REASON WHY YOU SHOULD CHOOSE US
            <h2></h2>
        </div>
        <div class="card-grid testimonial-grid">
            <?php
            $testimonials = [
                [
                    "name" => "Mark Anthony",
                    "location" => "Bayawan City",
                    "text" => "Fast service and genuine parts. My bike feels brand new every time I bring it here!",
                    "image" => "images/testimonials/testimonial_1.png"
                ],
                [
                    "name" => "Sarah Jane",
                    "location" => "Negros Oriental",
                    "text" => "Very professional mechanics. They explained everything clearly before starting the repair.",
                    "image" => "images/testimonials/testimonial_2.png"
                ],
                [
                    "name" => "Dave Villanueva",
                    "location" => "Tanjay City",
                    "text" => "The detailing and wash service is top-notch. Highly recommended for daily riders.",
                    "image" => "images/testimonials/testimonial_3.png"
                ],
                [
                    "name" => "Kenneth Roy",
                    "location" => "Dumaguete",
                    "text" => "Ordering parts online and picking them up at the shop saves me so much hassle.",
                    "image" => "images/testimonials/testimonial_4.png"
                ]
            ];

            foreach ($testimonials as $t) {
                echo '
        <div class="testimonial-card">
            <img src="' . $t['image'] . '" alt="' . $t['name'] . '" class="testimonial-avatar">
            <h3 class="testimonial-name">' . $t['name'] . '</h3>
            <div class="testimonial-location">' . $t['location'] . '</div>
            <div class="stars">★★★★★</div>
            <p class="testimonial-text">"' . $t['text'] . '"</p>
            <img src="images/arrow.png" alt="Arrow" class="testimonial-arrow">
        </div>';
            }
            ?>
        </div>
    </section>



    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="logo">
                        <img src="styles/Bencalo MotoWorks Logo.svg" alt="Bencalo MotoWorks Logo" class="logo-img">
                    </div>
                    <p>Expert parts and vehicle care.
                        Driven by passion.</p>
                    <ul>
                        <li><a href="#">Our Team</a></li>
                        <li><a href="#\">About Us</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Maintenance Guides</h4>
                    <ul>
                        <li><a href="#">5 Signs Your Battery is Failing</a></li>
                        <li><a href="#">Importance of Oil Changes</a></li>
                        <li><a href="#">Proper Tire Inflation Guide</a></li>
                        <li><a href="#\">Cooling System Maintenance</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Customer Support</h4>
                    <ul>
                        <li><a href="#">My Account</a></li>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Customer Support</a></li>
                    </ul>
                </div>
                <div class="footer-col footer-contact">
                    <h4>Contact Us</h4>
                    <ul>
                        <li>Bencalo Motoworks - Bollos Street, Boyco, Bayawan City, Negros Oriental, Philippines 6221</li>
                        <li>(123)-456-7890</li>
                        <li>bencalomotoworks@gmail.com</li>
                        <li>Mon-Sat: 8:00 AM – 6:00 PM,
                            Sun: CLOSED</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date("Y"); ?> Bencalo MotoWorks. All rights reserved.</p>
            </div>
        </div>
    </footer>

</body>

</html>