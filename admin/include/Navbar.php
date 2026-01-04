<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <!-- Mobile Header -->
    <div class="mobile-header">
        <button class="toggle-sidebar" id="toggleSidebar">
            <i class="fas fa-bars"></i>
        </button>
        <div class="mobile-logo">
            <i class="fas fa-shoe-prints"></i>
            <span>StepStyle</span>
        </div>
    </div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <i class="fas fa-shoe-prints"></i>
            <span>StepStyle Admin</span>
        </div>
        <nav>
            <a href="../dashboard/dashboard.php" <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : '' ?>>
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="../products/products.php" <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'class="active"' : '' ?>>
                <i class="fas fa-box"></i> Products
            </a>
            <a href="../categories/categories.php" <?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'class="active"' : '' ?>>
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="../orders/manage_order.php" <?= basename($_SERVER['PHP_SELF']) == 'manage_order.php' ? 'class="active"' : '' ?>>
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
            <a href="../Total_user/Mangement_user.php" <?= basename($_SERVER['PHP_SELF']) == 'User.php' ? 'class="active"' : '' ?>>
                <i class="fas fa-users"></i> Users
            </a>
            <a href="../../auth/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('toggleSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const mobileHeader = document.querySelector('.mobile-header');

            function toggleSidebar() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            }

            function checkScreenSize() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }

            toggleBtn.addEventListener('click', toggleSidebar);
            overlay.addEventListener('click', toggleSidebar);
            window.addEventListener('resize', checkScreenSize);

            // Close sidebar when clicking links on mobile
            const navLinks = sidebar.querySelectorAll('nav a');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 768) {
                        toggleSidebar();
                    }
                });
            });
        });
    </script>
</body>

</html>