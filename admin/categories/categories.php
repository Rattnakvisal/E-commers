<?php
session_start();
if (!isset($_SESSION["user_email"]) || $_SESSION["user_email"] !== "admin@ecommerce.com") {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../../config/conn.php';

// Handle Add / Edit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $description = $_POST['description'];

    if ($_POST['action'] === 'add') {
        $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $name, $description);
        if ($stmt->execute()) $success = "Category added successfully.";
        else $error = "Error: " . $stmt->error;
    }

    if ($_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $sql = "UPDATE categories SET name=?, description=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $name, $description, $id);
        if ($stmt->execute()) $success = "Category updated successfully.";
        else $error = "Update failed: " . $stmt->error;
    }
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM categories WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) $success = "Category deleted successfully.";
    else $error = "Delete failed: " . $stmt->error;
}

// Fetch categories
$categories = $conn->query("
    SELECT 
        c.*, 
        COUNT(p.id) as product_count,
        SUM(CASE WHEN p.status = 'featured' THEN 1 ELSE 0 END) as featured_count,
        SUM(CASE WHEN p.status = 'new_arrival' THEN 1 ELSE 0 END) as new_arrival_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY c.created_at DESC
") or die("Query failed: " . $conn->error);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Categories</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../categories/categories.css">
</head>

<body>
    <div class="admin-container">
        <?php require '../include/Navbar.php'; ?>

        <div class="main-content">
            <header>
                <h1>Category Management</h1>
                <button class="btn-add" onclick="openAddForm()">
                    <i class="fas fa-plus"></i> Add New Category
                </button>
            </header>

            <?php if (isset($success)): ?>
                <div class="alert success"><?= $success ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert error"><?= $error ?></div>
            <?php endif; ?>

            <!-- Categories Grid -->
            <div class="categories-dashboard-grid">
                <?php while ($category = $categories->fetch_assoc()): ?>
                    <div class="category-dashboard-card"
                        data-id="<?= $category['id'] ?>"
                        data-name="<?= htmlspecialchars($category['name'], ENT_QUOTES) ?>"
                        data-description="<?= htmlspecialchars($category['description'], ENT_QUOTES) ?>">
                        <div class="category-details">
                            <h3><?= htmlspecialchars($category['name']) ?></h3>
                            <p><?= htmlspecialchars($category['description']) ?></p>
                            <div class="category-stats">
                                <span><i class="fas fa-box"></i> <?= $category['product_count'] ?> Products</span>
                                <span><i class="fas fa-star"></i> <?= $category['featured_count'] ?> Featured</span>
                                <span><i class="fas fa-fire"></i> <?= $category['new_arrival_count'] ?> New</span>
                            </div>
                        </div>
                        <div class="category-actions">
                            <button class="btn-edit" onclick="editCategory(<?= $category['id'] ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-delete" onclick="deleteCategory(<?= $category['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Modal Form -->
            <div id="addCategoryForm" class="modal">
                <div class="modal-content">
                    <h2>Add New Category</h2>
                    <form method="POST" class="category-form">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="id" value="">

                        <div class="form-group">
                            <label>Category Name:</label>
                            <input type="text" name="name" required>
                        </div>

                        <div class="form-group">
                            <label>Description:</label>
                            <textarea name="description" required></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn-cancel" onclick="closeForm()">Cancel</button>
                            <button type="submit" class="btn-submit">Add Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Script -->
    <script src="../categories/categories.js"></script>
</body>

</html>