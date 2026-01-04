<?php
session_start();
if (!isset($_SESSION["user_email"]) || $_SESSION["user_email"] !== "admin@ecommerce.com") {
    header("Location: ../auth/login.php");
    exit();
}
require_once '../../config/conn.php';

// Fetch statistics
$totalProducts = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$featuredProducts = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'featured'")->fetch_assoc()['count'];
$newArrivals = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'new_arrival'")->fetch_assoc()['count'];
$collections = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'collection'")->fetch_assoc()['count'];

// Chart data
$totalOrders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$totalOrderItems = $conn->query("SELECT COUNT(*) as count FROM order_items")->fetch_assoc()['count'];

// Total revenue
$totalRevenueRow = $conn->query("SELECT SUM(total_amount) as revenue FROM orders")->fetch_assoc();
$totalRevenue = $totalRevenueRow['revenue'] ? $totalRevenueRow['revenue'] : 0;

// Top 5 selling products (by quantity)
$topProducts = $conn->query("
    SELECT product_name, SUM(quantity) as total_qty
    FROM order_items
    GROUP BY product_name
    ORDER BY total_qty DESC
    LIMIT 5
");
$topProductNames = [];
$topProductQtys = [];
while ($row = $topProducts->fetch_assoc()) {
    $topProductNames[] = $row['product_name'];
    $topProductQtys[] = (int)$row['total_qty'];
}

// Top 5 products by revenue
$topRevenueProducts = $conn->query("
    SELECT product_name, SUM(price * quantity) as total_revenue
    FROM order_items
    GROUP BY product_name
    ORDER BY total_revenue DESC
    LIMIT 5
");
$topRevenueProductNames = [];
$topRevenueProductAmounts = [];
while ($row = $topRevenueProducts->fetch_assoc()) {
    $topRevenueProductNames[] = $row['product_name'];
    $topRevenueProductAmounts[] = (float)$row['total_revenue'];
}

// Fetch all products
$allProducts = $conn->query("
    SELECT p.*, c.name as category_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
");

// Fetch all orders
$allOrders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC");

// Fetch all order items (with order and product info)
$allOrderItems = $conn->query("
    SELECT oi.*, o.name as customer_name, o.email as customer_email, o.created_at as order_date
    FROM order_items oi
    LEFT JOIN orders o ON oi.order_id = o.id
    ORDER BY oi.order_id DESC
");

// Total revenue and orders for today
// Use CURDATE() for MySQL date comparison to ensure correct results
$todayRevenueRow = $conn->query("SELECT SUM(total_amount) as revenue FROM orders WHERE DATE(created_at) = CURDATE()")->fetch_assoc();
$todayRevenue = $todayRevenueRow['revenue'] ? $todayRevenueRow['revenue'] : 0;
$todayOrdersRow = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()")->fetch_assoc();
$todayOrders = $todayOrdersRow['count'];

// Total revenue and orders for current month
$thisMonth = date('Y-m');
$monthRevenueRow = $conn->query("SELECT SUM(total_amount) as revenue FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = '$thisMonth'")->fetch_assoc();
$monthRevenue = $monthRevenueRow['revenue'] ? $monthRevenueRow['revenue'] : 0;
$monthOrdersRow = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = '$thisMonth'")->fetch_assoc();
$monthOrders = $monthOrdersRow['count'];

// Total revenue and orders for current year
$thisYear = date('Y');
$yearRevenueRow = $conn->query("SELECT SUM(total_amount) as revenue FROM orders WHERE YEAR(created_at) = '$thisYear'")->fetch_assoc();
$yearRevenue = $yearRevenueRow['revenue'] ? $yearRevenueRow['revenue'] : 0;
$yearOrdersRow = $conn->query("SELECT COUNT(*) as count FROM orders WHERE YEAR(created_at) = '$thisYear'")->fetch_assoc();
$yearOrders = $yearOrdersRow['count'];

// Handle selected date filter (from GET param)
$selectedDate = isset($_GET['date']) ? $_GET['date'] : '';
$selectedDateRevenue = 0;
$selectedDateOrders = 0;
if ($selectedDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $row = $conn->query("SELECT SUM(total_amount) as revenue FROM orders WHERE DATE(created_at) = '$selectedDate'")->fetch_assoc();
    $selectedDateRevenue = $row['revenue'] ? $row['revenue'] : 0;
    $row = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = '$selectedDate'")->fetch_assoc();
    $selectedDateOrders = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - StepStyle</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="admin-container">
        <?php require '../include/Navbar.php'; ?>
        <div class="main-content">
            <header>
                <h1>Dashboard</h1>
                <div class="admin-info">
                    <img src="https://i.pinimg.com/736x/5f/40/6a/5f406ab25e8942cbe0da6485afd26b71.jpg" alt="Admin" class="admin-avatar">
                </div>
            </header>

            <!-- Filter for Today/Monthly/Yearly/Date -->
            <div style="margin-bottom: 18px; display: flex; align-items: center; gap: 16px;">
                <label for="revenueFilter" style="font-weight:600;margin-right:10px;">Revenue Filter:</label>
                <select id="revenueFilter" style="padding:6px 14px; border-radius:6px;">
                    <option value="today">Today</option>
                    <option value="month">Monthly</option>
                    <option value="year">Yearly</option>
                    <option value="date">Date</option>
                </select>
                <input type="date" id="revenueDateInput" value="<?= htmlspecialchars($selectedDate) ?>" style="padding:6px 14px; border-radius:6px; display:none;" />
            </div>

            <!-- Revenue Cards (all, toggle via JS) -->
            <div id="todayRevenueCard" style="display:block;">
                <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;">
                    <div style="flex:1; min-width:220px; background:#fff; border-radius:10px; box-shadow:0 2px 8px #0001; padding:18px;">
                        <h4 style="color:#374151; font-size:15px; margin-bottom:6px;">Today's Revenue</h4>
                        <div style="font-size:22px; font-weight:700; color:#16a34a;">
                            $<?= number_format($todayRevenue, 2) ?>
                        </div>
                        <div style="font-size:14px; color:#374151; margin-top:4px;">
                            Orders: <?= $todayOrders ?>
                        </div>
                    </div>
                </div>
            </div>
            <div id="monthRevenueCard" style="display:none;">
                <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;">
                    <div style="flex:1; min-width:220px; background:#fff; border-radius:10px; box-shadow:0 2px 8px #0001; padding:18px;">
                        <h4 style="color:#374151; font-size:15px; margin-bottom:6px;">This Month's Revenue</h4>
                        <div style="font-size:22px; font-weight:700; color:#2563eb;">
                            $<?= number_format($monthRevenue, 2) ?>
                        </div>
                        <div style="font-size:14px; color:#374151; margin-top:4px;">
                            Orders: <?= $monthOrders ?>
                        </div>
                    </div>
                </div>
            </div>
            <div id="yearRevenueCard" style="display:none;">
                <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;">
                    <div style="flex:1; min-width:220px; background:#fff; border-radius:10px; box-shadow:0 2px 8px #0001; padding:18px;">
                        <h4 style="color:#374151; font-size:15px; margin-bottom:6px;">This Year's Revenue</h4>
                        <div style="font-size:22px; font-weight:700; color:#0ea5e9;">
                            $<?= number_format($yearRevenue, 2) ?>
                        </div>
                        <div style="font-size:14px; color:#374151; margin-top:4px;">
                            Orders: <?= $yearOrders ?>
                        </div>
                    </div>
                </div>
            </div>
            <div id="dateRevenueCard" style="display:none;">
                <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px;">
                    <div style="flex:1; min-width:220px; background:#fff; border-radius:10px; box-shadow:0 2px 8px #0001; padding:18px;">
                        <h4 style="color:#374151; font-size:15px; margin-bottom:6px;">
                            Revenue for <?= $selectedDate ? date('M d, Y', strtotime($selectedDate)) : 'Selected Date' ?>
                        </h4>
                        <div style="font-size:22px; font-weight:700; color:#eab308;">
                            $<?= number_format($selectedDateRevenue, 2) ?>
                        </div>
                        <div style="font-size:14px; color:#374151; margin-top:4px;">
                            Orders: <?= $selectedDateOrders ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4CAF50;">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Total Products</h3>
                        <p><?= $totalProducts ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #FF9800;">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Featured Products</h3>
                        <p><?= $featuredProducts ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: #E91E63;">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div class="stat-details">
                        <h3>New Arrivals</h3>
                        <p><?= $newArrivals ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: #e91ec0ff;">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Collections</h3>
                        <p><?= $collections ?></p>
                    </div>
                </div>
            </div>

            <!-- Chart Reports -->
            <div style="display: flex; flex-wrap: wrap; gap: 40px; margin-bottom: 40px;">
                <div style="flex:1; min-width:320px; background:#fff; border-radius:12px; box-shadow:0 2px 8px #0001; padding:24px;">
                    <h3 style="margin-bottom:16px;">System Overview</h3>
                    <canvas id="systemBarChart" height="180"></canvas>
                </div>
                <div style="flex:1; min-width:320px; background:#fff; border-radius:12px; box-shadow:0 2px 8px #0001; padding:24px;">
                    <h3 style="margin-bottom:16px;">Top 5 Selling Products</h3>
                    <canvas id="topProductsPieChart" height="180"></canvas>
                </div>
                <div style="flex:1; min-width:320px; background:#fff; border-radius:12px; box-shadow:0 2px 8px #0001; padding:24px;">
                    <h3 style="margin-bottom:16px;">Top 5 Products by Revenue</h3>
                    <canvas id="topRevenueProductsBarChart" height="180"></canvas>
                </div>
            </div>

            <!-- Recent Products -->
            <div class="recent-section">
                <h2>Recent Products</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Sale Price</th>
                                <th>Status</th>
                                <th>Stock</th>
                                <th>Added Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recentProducts = $conn->query("
                                SELECT p.*, c.name as category_name 
                                FROM products p
                                LEFT JOIN categories c ON p.category_id = c.id
                                ORDER BY p.created_at DESC 
                                LIMIT 5
                            ");
                            while ($product = $recentProducts->fetch_assoc()):
                                $status_class = strtolower($product['status']);
                            ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($product['image_url'])): ?>
                                            <img src="../../admin/uploads/<?= htmlspecialchars($product['image_url']) ?>"
                                                alt="<?= htmlspecialchars($product['name']) ?>"
                                                style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: #eee; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-image" style="color: #aaa;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= htmlspecialchars($product['category_name']) ?></td>
                                    <td>$<?= number_format($product['price'], 2) ?></td>
                                    <td><?= $product['sale_price'] ? '$' . number_format($product['sale_price'], 2) : '-' ?></td>
                                    <td><span class="status-badge <?= $status_class ?>"><?= ucfirst($product['status']) ?></span></td>
                                    <td><?= $product['stock'] ?></td>
                                    <td><?= date('M d, Y', strtotime($product['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- All Orders Table -->
            <div class="recent-section" style="margin-top:40px;">
                <h2>All Orders</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>User ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $allOrders->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $order['id'] ?></td>
                                    <td><?= $order['user_id'] ?></td>
                                    <td><?= htmlspecialchars($order['name']) ?></td>
                                    <td><?= htmlspecialchars($order['email']) ?></td>
                                    <td><?= htmlspecialchars($order['address']) ?></td>
                                    <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($order['status']) ?></td>
                                    <td><?= $order['created_at'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- All Order Items Table -->
            <div class="recent-section" style="margin-top:40px;">
                <h2>All Order Items</h2>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Order Item ID</th>
                                <th>Order ID</th>
                                <th>Product Name</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Order Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = $allOrderItems->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $item['id'] ?></td>
                                    <td><?= $item['order_id'] ?></td>
                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td>$<?= number_format($item['price'], 2) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td><?= htmlspecialchars($item['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($item['customer_email']) ?></td>
                                    <td><?= $item['order_date'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/admin.js"></script>
    <script>
        // Chart Data from PHP
        const totalProducts = <?= (int)$totalProducts ?>;
        const totalOrders = <?= (int)$totalOrders ?>;
        const totalOrderItems = <?= (int)$totalOrderItems ?>;
        const topProductNames = <?= json_encode($topProductNames) ?>;
        const topProductQtys = <?= json_encode($topProductQtys) ?>;
        const topRevenueProductNames = <?= json_encode($topRevenueProductNames) ?>;
        const topRevenueProductAmounts = <?= json_encode($topRevenueProductAmounts) ?>;

        // System Overview Bar Chart
        new Chart(document.getElementById('systemBarChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Products', 'Orders', 'Order Items'],
                datasets: [{
                    label: 'Total Count',
                    data: [totalProducts, totalOrders, totalOrderItems],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(239, 68, 68, 0.7)'
                    ],
                    borderColor: [
                        'rgba(59, 130, 246, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(239, 68, 68, 1)'
                    ],
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Top 5 Selling Products Pie Chart
        new Chart(document.getElementById('topProductsPieChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: topProductNames,
                datasets: [{
                    data: topProductQtys,
                    backgroundColor: [
                        '#3b82f6', '#10b981', '#f59e42', '#ef4444', '#6366f1'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    title: {
                        display: false
                    }
                }
            }
        });

        // Top 5 Products by Revenue Bar Chart
        new Chart(document.getElementById('topRevenueProductsBarChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: topRevenueProductNames,
                datasets: [{
                    label: 'Revenue ($)',
                    data: topRevenueProductAmounts,
                    backgroundColor: [
                        '#6366f1', '#3b82f6', '#10b981', '#f59e42', '#ef4444'
                    ],
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Revenue filter toggle
        const filter = document.getElementById('revenueFilter');
        const dateInput = document.getElementById('revenueDateInput');
        filter.addEventListener('change', function() {
            document.getElementById('todayRevenueCard').style.display = 'none';
            document.getElementById('monthRevenueCard').style.display = 'none';
            document.getElementById('yearRevenueCard').style.display = 'none';
            document.getElementById('dateRevenueCard').style.display = 'none';
            dateInput.style.display = (this.value === 'date') ? 'inline-block' : 'none';
            if (this.value === 'today') {
                document.getElementById('todayRevenueCard').style.display = 'block';
            } else if (this.value === 'month') {
                document.getElementById('monthRevenueCard').style.display = 'block';
            } else if (this.value === 'year') {
                document.getElementById('yearRevenueCard').style.display = 'block';
            } else if (this.value === 'date') {
                document.getElementById('dateRevenueCard').style.display = 'block';
            }
        });

        // On date change, reload page with ?date=YYYY-MM-DD&revenueFilter=date
        dateInput.addEventListener('change', function() {
            if (this.value) {
                window.location.href = '?revenueFilter=date&date=' + this.value;
            }
        });

        // Show correct card on page load if filter=date
        document.addEventListener('DOMContentLoaded', function() {
            if (filter.value === 'date') {
                dateInput.style.display = 'inline-block';
                document.getElementById('dateRevenueCard').style.display = 'block';
                document.getElementById('todayRevenueCard').style.display = 'none';
                document.getElementById('monthRevenueCard').style.display = 'none';
                document.getElementById('yearRevenueCard').style.display = 'none';
            }
        });
    </script>
</body>

</html>