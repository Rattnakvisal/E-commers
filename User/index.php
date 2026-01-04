<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}
include '../config/conn.php';

// Initialize messages
$success_message = '';
$error_message = '';

// Show success message if redirected after order
if (isset($_GET['order']) && $_GET['order'] === 'success') {
    $success_message = "Order placed successfully!";
}

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_submit'])) {
    $user_id = $_SESSION['user_id'];
    $name = trim($_POST['checkoutName']);
    $email = trim($_POST['checkoutEmail']);
    $address = trim($_POST['checkoutAddress']);
    $cart_json = isset($_POST['cart_data']) ? $_POST['cart_data'] : '';
    $cart = [];
    $total = 0;

    // Validate fields
    if (!$name || !$email || !$address) {
        $error_message = "Please fill in all checkout fields.";
    } elseif (empty($cart_json)) {
        $error_message = "Your cart is empty.";
    } else {
        $cart = json_decode($cart_json, true);
        if (!is_array($cart) || count($cart) == 0) {
            $error_message = "Your cart is empty.";
        }
    }

    if (!$error_message) {
        foreach ($cart as $item) {
            $total += floatval($item['price']) * intval($item['qty']);
        }

        // Insert order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, name, email, address, total_amount, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        if ($stmt) {
            $stmt->bind_param("isssd", $user_id, $name, $email, $address, $total);
            if ($stmt->execute()) {
                $order_id = $conn->insert_id;

                // Insert order items
                $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_name, price, quantity) VALUES (?, ?, ?, ?)");
                if ($stmt_item) {
                    foreach ($cart as $item) {
                        $product_name = $item['name'];
                        $price = floatval($item['price']);
                        $qty = intval($item['qty']);
                        $stmt_item->bind_param("isdi", $order_id, $product_name, $price, $qty);
                        if (!$stmt_item->execute()) {
                            $error_message = "Failed to insert order item: " . $stmt_item->error;
                            break;
                        }
                    }
                    if (!$error_message) {
                        // Redirect to avoid resubmission
                        header("Location: index.php?order=success");
                        exit();
                    }
                } else {
                    $error_message = "Failed to prepare order items statement: " . $conn->error;
                }
            } else {
                $error_message = "Failed to insert order: " . $stmt->error;
            }
        } else {
            $error_message = "Failed to prepare order statement: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>StepStyle | Premium Footwear</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../assets/css/Style.css">
    <style>
        html {                                                                                                              
            scroll-behavior: smooth;
        }

        /* Loader Overlay Styles */
        #pageLoader {
            position: fixed;
            z-index: 99999;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.4s;
        }

        #pageLoader.hide {
            opacity: 0;
            pointer-events: none;
        }

        .loader-spinner {
            width: 60px;
            height: 60px;
            border: 6px solid #4a6de5;
            border-top: 6px solid #ff6b6b;
            border-radius: 50%;
            animation: spinLoader 1s linear infinite;
        }

        @keyframes spinLoader {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Loader for checkout */
        #checkoutLoader {
            position: fixed;
            z-index: 99999;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            display: none;
            align-items: center;
            justify-content: center;
        }

        #checkoutLoader.show {
            display: flex;
        }

        .checkout-spinner {
            width: 60px;
            height: 60px;
            border: 6px solid #10b981;
            border-top: 6px solid #3b82f6;
            border-radius: 50%;
            animation: spinLoader 1s linear infinite;
        }
    </style>
</head>

<body>
    <!-- Loader Overlay -->
    <div id="pageLoader">
        <div class="loader-spinner"></div>
    </div>
    <!-- Loader for checkout -->
    <div id="checkoutLoader">
        <div class="checkout-spinner"></div>
    </div>
    <!-- Header -->
    <header>
        <div class="container header-container">
            <a href="#" id="home" class="logo"><i class="fas fa-shoe-prints"></i>Step<span>Style</span></a>
            <nav>
                <ul id="navMenu">
                    <li>
                        <a href="#home" id="home"><i class="fas fa-home"></i>Home</a>
                    </li>
                    <li>
                        <a href="#featured" id="shopLink"><i class="fas fa-store"></i>Shop</a>
                    </li>
                    <li>
                        <a href="#new-arrivals"><i class="fas fa-fire"></i>New Arrivals</a>
                    </li>
                    <li>
                        <a href="#collections"><i class="fas fa-box-open"></i>Collections</a>
                    </li>
                    <li>
                        <a href="#contact"><i class="fas fa-phone"></i>Contact</a>
                    </li>
                </ul>
            </nav>
            <div class="nav-icons">
                <div class="nav-icon">
                    <i class="fas fa-search"></i>
                </div>
                <div class="nav-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="nav-icon cart-icon" id="cartIcon">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="cart-count" id="cartCount">0</span>
                </div>
                <a href="../auth/logout.php" class="nav-icon" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
            <div class="mobile-menu" id="mobileMenu">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </header>

    <!-- Slideshow Section -->

    <section class="container slideshow">
        <div class="slideshow-container">
            <!-- Slide 1 -->
            <div class="slide active">
                <img src="https://i.pinimg.com/1200x/e7/1f/48/e71f480bd75e40b75dfb3b18e5611f45.jpg"
                    alt="Retro High-Tops" class="slide-image">
                <div class="progress-bar"></div>
                <div class="slide-content">
                    <h2>Retro High-Tops</h2>
                    <p>Classic style, modern comfort. Limited edition now available.</p>
                </div>
            </div>

            <!-- Slide 2 -->
            <div class="slide">
                <img src="https://i.pinimg.com/1200x/1d/05/89/1d05891a227b694a056aa268ae67f6a5.jpg"
                    alt="Designer Loafers" class="slide-image">
                <div class="progress-bar"></div>
                <div class="slide-content">
                    <h2>Designer Loafers</h2>
                    <p>Handcrafted luxury for every occasion. Shop exclusive designs.</p>
                </div>
            </div>

            <!-- Slide 3 -->
            <div class="slide">
                <img src="https://i.pinimg.com/1200x/3d/cf/d1/3dcfd156575f37ce12cd5d5bf02065e1.jpg"
                    alt="Street Sneakers" class="slide-image">
                <div class="progress-bar"></div>
                <div class="slide-content">
                    <h2>Street Sneakers</h2>
                    <p>Urban look for everyday wear. Trending styles for you.</p>
                </div>
            </div>

            <!-- Slide 4 -->
            <div class="slide">
                <img src="https://i.pinimg.com/1200x/aa/b4/5a/aab45a26d24ea57310206b52c61f132f.jpg"
                    alt="UltraBoost Runners" class="slide-image">
                <div class="progress-bar"></div>
                <div class="slide-content">
                    <h2>UltraBoost Runners</h2>
                    <p>Experience maximum comfort with our latest running technology.</p>
                </div>
            </div>

            <!-- Navigation -->
            <div class="navigation">
                <div class="prev" id="slidePrev">
                    <i class="fas fa-chevron-left"></i>
                </div>
                <div class="next" id="slideNext">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </div>

            <!-- Dots -->
            <div class="dots-container" id="slideDots">
                <span class="dot active"></span>
                <span class="dot"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>
        </div>
    </section>
    <!-- Featured Products -->
    <section id="featured" class="featured-products">
        <div class="container">
            <h2 class="section-title">Featured Shoes</h2>
            <div class="products-grid">
                <?php
                $featured = $conn->query("SELECT * FROM products WHERE status='featured' LIMIT 10");
                while ($product = $featured->fetch_assoc()):
                ?>
                    <div class="product-card">
                        <div class="product-badge">Featured</div>
                        <div class="product-image">
                            <img src="<?= !empty($product['image_url']) ? '../admin/uploads/' . $product['image_url'] : '../assets/images/default-product.jpg' ?>"
                                alt="<?= htmlspecialchars($product['name']) ?>" />
                            <div class="product-actions">
                                <div class="action-btn"><i class="fas fa-heart"></i></div>
                                <div class="action-btn"><i class="fas fa-eye"></i></div>
                                <div class="action-btn add-to-cart"
                                    data-id="<?= $product['id'] ?>"
                                    data-name="<?= htmlspecialchars($product['name']) ?>"
                                    data-price="<?= $product['price'] ?>">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                            </div>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="product-price">
                                <span class="current-price">$<?= number_format($product['price'], 2) ?></span>
                                <?php if ($product['sale_price']): ?>
                                    <span class="original-price">$<?= number_format($product['sale_price'], 2) ?></span>
                                <?php endif; ?>
                            </div>
                            <p><?= htmlspecialchars($product['description']) ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Collections Section -->
    <section id="collections" class="collections-section">
        <div class="container">
            <h2 class="section-title">Collections</h2>
            <div class="products-grid">
                <?php
                $collections = $conn->query("SELECT * FROM products WHERE status='collection' LIMIT 10");
                while ($product = $collections->fetch_assoc()):
                ?>
                    <div class="product-card">
                        <div class="product-badge">Limited</div>
                        <div class="product-image">
                            <img src="<?= !empty($product['image_url']) ? '../admin/uploads/' . $product['image_url'] : '../assets/images/default-product.jpg' ?>"
                                alt="<?= htmlspecialchars($product['name']) ?>" />
                            <div class="product-actions">
                                <div class="action-btn"><i class="fas fa-heart"></i></div>
                                <div class="action-btn"><i class="fas fa-eye"></i></div>
                                <div class="action-btn add-to-cart"
                                    data-id="<?= $product['id'] ?>"
                                    data-name="<?= htmlspecialchars($product['name']) ?>"
                                    data-price="<?= $product['price'] ?>">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                            </div>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="product-price">
                                <span class="current-price">$<?= number_format($product['price'], 2) ?></span>
                            </div>
                            <p><?= htmlspecialchars($product['description']) ?></p>
                            <div class="product-meta">
                                <div class="rating">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                    <i class="far fa-star"></i>
                                    (<?= rand(10, 50) ?>)
                                </div>
                                <div>Limited stock</div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- New Arrivals -->
    <section class="new-arrivals" id="new-arrivals">
        <div class="container">
            <h2 class="section-title">New Arrivals</h2>
            <div class="arrivals-grid">
                <?php
                $new_arrivals = $conn->query("SELECT * FROM products WHERE status='new_arrival' LIMIT 10");
                while ($product = $new_arrivals->fetch_assoc()):
                ?>
                    <div class="product-card">
                        <div class="product-badge">New</div>
                        <div class="product-image">
                            <img src="<?= !empty($product['image_url']) ? '../admin/uploads/' . $product['image_url'] : '../assets/images/default-product.jpg' ?>"
                                alt="<?= htmlspecialchars($product['name']) ?>" />
                        </div>
                        <div class="product-info">
                            <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="product-price">
                                <span class="current-price">$<?= number_format($product['price'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2 class="section-title">Why Choose StepStyle</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-truck"></i>
                    <h3>Free Shipping</h3>
                    <p>Free worldwide shipping on all orders over $100</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-undo"></i>
                    <h3>30-Day Returns</h3>
                    <p>Not satisfied? Return within 30 days for a full refund</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>2-Year Warranty</h3>
                    <p>All products come with a 2-year manufacturer warranty</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-headset"></i>
                    <h3>24/7 Support</h3>
                    <p>Our customer service team is always ready to help</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials">
        <div class="container">
            <h2 class="section-title">What Our Customers Say</h2>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">
                            <img
                                src="https://randomuser.me/api/portraits/women/43.jpg"
                                alt="Sarah Johnson" />
                        </div>
                        <div>
                            <h3>Sarah Johnson</h3>
                            <div class="testimonial-rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-content">
                        "The UltraBoost Runners are the most comfortable shoes I've ever
                        worn. Perfect for my daily runs and gym sessions. Will definitely
                        buy again!"
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">
                            <img
                                src="https://randomuser.me/api/portraits/men/32.jpg"
                                alt="Michael Chen" />
                        </div>
                        <div>
                            <h3>Michael Chen</h3>
                            <div class="testimonial-rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-content">
                        "The customization options are fantastic! I was able to create the
                        perfect pair of Oxfords for my wedding. Great quality and
                        service."
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="testimonial-header">
                        <div class="testimonial-avatar">
                            <img
                                src="https://randomuser.me/api/portraits/women/68.jpg"
                                alt="Emily Rodriguez" />
                        </div>
                        <div>
                            <h3>Emily Rodriguez</h3>
                            <div class="testimonial-rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-content">
                        "I bought the TrailMaster boots for my hiking trip to the Rockies.
                        They performed exceptionally well in all conditions. Highly
                        recommended!"
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="newsletter">
        <div class="container">
            <h2>Join Our Newsletter</h2>
            <p>
                Subscribe to get exclusive offers, new product announcements, and
                style inspiration
            </p>
            <form class="newsletter-form">
                <input type="email" placeholder="Enter your email address" />
                <button type="submit">Subscribe</button>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>StepStyle</h3>
                    <p>
                        Premium footwear for every occasion. Quality, comfort, and style
                        in every step.
                    </p>
                    <div class="social-icons">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-pinterest-p"></i></a>
                    </div>
                </div>

                <div class="footer-col">
                    <h3>Shop</h3>
                    <ul>
                        <li>
                            <a href="#"><i class="fas fa-chevron-right"></i>Men's Collection</a>
                        </li>
                        <li>
                            <a href="#"><i class="fas fa-chevron-right"></i>Women's Collection</a>
                        </li>
                        <li>
                            <a href="#"><i class="fas fa-chevron-right"></i>New Arrivals</a>
                        </li>
                        <li>
                            <a href="#"><i class="fas fa-chevron-right"></i>Best Sellers</a>
                        </li>
                        <li>
                            <a href="#"><i class="fas fa-chevron-right"></i>Special Offers</a>
                        </li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h3>Information</h3>
                    <ul>
                        <li>
                            <a href="#"><i class="fas fa-chevron-right"></i>About Us</a>
                        </li>
                        <li>
                            <a href="#"><i class="fas fa-chevron-right"></i>Contact Us</a>
                        </li>
                        <li>
                            <a href="#"><i class="fas fa-chevron-right"></i>Shipping Policy</a>
                        </li>
                        <li>
                            <a href="#"><i class="fas fa-chevron-right"></i>Returns & Exchanges</a>
                        </li>
                        <li>
                            <a href="#"><i class="fas fa-chevron-right"></i>FAQs</a>
                        </li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h3>Contact Us</h3>
                    <ul>
                        <li>
                            <a href="#"><i class="fas fa-map-marker-alt"></i>123 Fashion Street, New
                                York, NY</a>
                        </li>
                        <li>
                            <a href="#"><i class="fas fa-phone"></i>+1 (555) 123-4567</a>
                        </li>
                        <li>
                            <a href="#"><i class="fas fa-envelope"></i>info@stepstyle.com</a>
                        </li>
                        <li>
                            <a href="#"><i class="fas fa-clock"></i>Mon-Fri: 9AM - 8PM</a>
                        </li>
                        <li>
                            <a href="#"><i class="fas fa-clock"></i>Sat-Sun: 10AM - 6PM</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2023 StepStyle. All rights reserved.</p>
        </div>
    </footer>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage">Item added to cart!</span>
    </div>

    <!-- Cart Modal -->
    <div class="cart-modal" id="cartModal">
        <div class="cart-modal-content">
            <span class="cart-modal-close" id="cartModalClose">&times;</span>
            <h2>Your Cart</h2>
            <?php if (!empty($success_message)): ?>
                <div style="color:green; margin-bottom:10px;"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div style="color:red; margin-bottom:10px;"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <!-- Cart Info Summary -->
            <div id="cartInfoSummary" style="margin-bottom: 16px; font-size: 16px; color: #333;"></div>

            <!-- Order Items Table -->
            <div id="cartItemsTableWrapper">
                <table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
                    <thead>
                        <tr>
                            <th style="text-align:left; padding:12px; background:#f5f7fb;">Product</th>
                            <th style="text-align:left; padding:12px; background:#f5f7fb;">Price</th>
                            <th style="text-align:left; padding:12px; background:#f5f7fb;">Qty</th>
                            <th style="text-align:left; padding:12px; background:#f5f7fb;">Total</th>
                            <th style="text-align:left; padding:12px; background:#f5f7fb;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="cartItems">
                        <!-- JS will fill this -->
                    </tbody>
                </table>
            </div>
            <div id="cartSummary" style="padding:15px; background:#f5f7fb; border-radius:8px; font-size:1.1rem; font-weight:600; text-align:right;"></div>

            <!-- Checkout Form -->
            <form id="cartForm" method="POST" autocomplete="off">
                <div class="form-group">
                    <input type="text" name="checkoutNameVisible" id="checkoutNameVisible" placeholder="Full Name" required />
                </div>
                <div class="form-group">
                    <input type="email" name="checkoutEmailVisible" id="checkoutEmailVisible" placeholder="Email Address" required />
                </div>
                <div class="form-group">
                    <textarea name="checkoutAddressVisible" id="checkoutAddressVisible" placeholder="Shipping Address" required></textarea>
                </div>
                <!-- Hidden fields for PHP submission -->
                <input type="hidden" name="checkoutName" id="checkoutName" />
                <input type="hidden" name="checkoutEmail" id="checkoutEmail" />
                <input type="hidden" name="checkoutAddress" id="checkoutAddress" />
                <input type="hidden" name="cart_data" id="cartDataInput" />
                <input type="hidden" name="checkout_submit" value="1" />
                <button type="submit" class="btn btn-secondary" style="width: 100%">Checkout</button>
            </form>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
    <?php
    // Only show cart modal automatically if there is an error or just placed an order
    $showCartModal = (!empty($error_message) || (isset($_GET['order']) && $_GET['order'] === 'success'));
    if ($showCartModal):
    ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                document.getElementById("cartModal").classList.add("show");
            });
        </script>
    <?php endif; ?>
    <script>
        // Hide loader after short delay on page load
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                document.getElementById("pageLoader").classList.add("hide");
            }, 1200);
        });

        // Show loader on checkout submit
        document.getElementById("cartForm").addEventListener("submit", function() {
            document.getElementById("checkoutLoader").classList.add("show");
        });
    </script>
</body>

</html>