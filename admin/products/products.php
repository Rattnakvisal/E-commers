<?php
session_start();
if (!isset($_SESSION["user_email"]) || $_SESSION["user_email"] !== "admin@ecommerce.com") {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../../config/conn.php';

$success = $error = "";

// Handle Add or Update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST['id'] ?? null;  // for edit
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $sale_price = empty($_POST['sale_price']) ? NULL : $_POST['sale_price'];
    $stock = $_POST['stock'];
    $status = $_POST['status'];

    // Image upload handling (optional on edit)
    $image_path = null;
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('prod_', true) . '.' . $ext;
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
            $image_path = $target_file;
        }
    }

    if ($id) {
        // Update existing product
        if ($image_path) {
            $sql = "UPDATE products SET name=?, category_id=?, description=?, price=?, sale_price=?, image_url=?, stock=?, status=? WHERE id=?";
        } else {
            // If no new image uploaded, don't update image_url
            $sql = "UPDATE products SET name=?, category_id=?, description=?, price=?, sale_price=?, stock=?, status=? WHERE id=?";
        }
        $stmt = $conn->prepare($sql);

        if ($image_path) {
            $stmt->bind_param("sisddsssi", $name, $category_id, $description, $price, $sale_price, $image_path, $stock, $status, $id);
        } else {
            $stmt->bind_param("sisddssi", $name, $category_id, $description, $price, $sale_price, $stock, $status, $id);
        }

        if ($stmt->execute()) {
            $success = "Product updated successfully.";
        } else {
            $error = "Update failed: " . $stmt->error;
        }
    } else {
        // Insert new product
        $sql = "INSERT INTO products (name, category_id, description, price, sale_price, image_url, stock, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisddsis", $name, $category_id, $description, $price, $sale_price, $image_path, $stock, $status);

        if ($stmt->execute()) {
            $success = "Product added successfully.";
        } else {
            $error = "Insert failed: " . $stmt->error;
        }
    }
}

// Handle Delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = $_GET['id'];
    // Optionally, delete image file here if needed

    $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        $success = "Product deleted successfully.";
    } else {
        $error = "Delete failed: " . $stmt->error;
    }
}

// Fetch categories for dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

// Fetch all products with category name
$products = $conn->query("
    SELECT p.*, c.name as category_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
") or die($conn->error);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard - Products</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../products/products.css">
</head>

<body>
    <div class="admin-container">
        <?php require '../include/Navbar.php'; ?>

        <div class="container">
            <h2>Manage Products</h2>

            <?php if ($success): ?>
                <p class="success"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <button class="btn-add" onclick="openForm()">+ Add New Product</button>

            <!-- Products Table -->
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Sale Price</th>
                        <th>Status</th>
                        <th>Stock</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <tr
                            data-id="<?= $product['id'] ?>"
                            data-name="<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>"
                            data-category_id="<?= $product['category_id'] ?>"
                            data-description="<?= htmlspecialchars($product['description'], ENT_QUOTES) ?>"
                            data-price="<?= $product['price'] ?>"
                            data-sale_price="<?= $product['sale_price'] ?>"
                            data-stock="<?= $product['stock'] ?>"
                            data-status="<?= $product['status'] ?>"
                            data-image_url="<?= htmlspecialchars($product['image_url'], ENT_QUOTES) ?>">
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td><?= htmlspecialchars($product['category_name']) ?></td>
                            <td>$<?= number_format($product['price'], 2) ?></td>
                            <td>
                                <?= $product['sale_price'] !== null ? "$" . number_format($product['sale_price'], 2) : '-' ?>
                            </td>
                            <td><?= htmlspecialchars($product['status']) ?></td>
                            <td><?= $product['stock'] ?></td>
                            <td>
                                <?php if ($product['image_url']): ?>
                                    <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="Img" style="width:50px; height:auto; border-radius:4px;">
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn-edit" onclick="openForm(<?= $product['id'] ?>)">Edit</button>
                                <a href="?action=delete&id=<?= $product['id'] ?>" onclick="return confirm('Delete this product?')" class="btn-delete">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Modal Form -->
            <div id="productFormModal" class="modal">
                <div class="modal-content">
                    <h2 id="modalTitle">Add New Product</h2>
                    <form method="POST" enctype="multipart/form-data" id="productForm">
                        <input type="hidden" name="id" id="productId" value="" />
                        <div class="form-group">
                            <label for="name">Product Name</label>
                            <input type="text" id="name" name="name" required />
                        </div>

                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">-- Select Category --</option>
                                <?php
                                // Reset pointer to start categories result
                                $categories->data_seek(0);
                                while ($cat = $categories->fetch_assoc()):
                                ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="price">Price ($)</label>
                            <input type="number" step="0.01" id="price" name="price" required />
                        </div>

                        <div class="form-group">
                            <label for="sale_price">Sale Price ($) (optional)</label>
                            <input type="number" step="0.01" id="sale_price" name="sale_price" />
                        </div>

                        <div class="form-group">
                            <label for="image_file">Product Image <small>(Upload to replace existing)</small></label>
                            <input type="file" id="image_file" name="image_file" accept="image/*" />
                            <div id="currentImagePreview" style="margin-top:8px;"></div>
                        </div>

                        <div class="form-group">
                            <label for="stock">Stock</label>
                            <input type="number" id="stock" name="stock" required />
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="regular">Regular</option>
                                <option value="featured">Featured</option>
                                <option value="collection">Collection</option>
                                <option value="new_arrival">New Arrival</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn-cancel" onclick="closeForm()">Cancel</button>
                            <button type="submit" class="btn-submit">Save Product</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
    <script src="../products/products.js"></script>
</body>

</html>