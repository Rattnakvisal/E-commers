<?php
session_start();
if (!isset($_SESSION["user_email"]) || $_SESSION["user_email"] !== "admin@ecommerce.com") {
    header("Location: ../auth/login.php");
    exit();
}
require_once '../../config/conn.php';

// Handle Delete User
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND email != 'admin@ecommerce.com'");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "User deleted successfully.";
    } else {
        $error = "Error deleting user: " . $stmt->error;
    }
}

// Fetch all users except admin
$users = $conn->query("
    SELECT id, email, name, created_at, 
           (SELECT COUNT(*) FROM orders WHERE user_id = users.id) as total_orders
    FROM users 
    WHERE email != 'admin@ecommerce.com'
    ORDER BY created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../Total_user/user.css">
</head>

<body>
    <div class="admin-container">
        <?php require '../include/Navbar.php'; ?>

        <div class="users-container">
            <div class="users-header">
                <h1>User Management</h1>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <table class="users-table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Registration Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td class="user-email"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="user-name"><?php echo htmlspecialchars($user['name']); ?></td>
                            <td class="user-date"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td><span class="user-status status-active">Active</span></td>
                            <td>
                                <button class="btn-delete"
                                    onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['email']); ?>')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function deleteUser(id, email) {
            if (confirm(`Are you sure you want to delete user ${email}?`)) {
                window.location.href = `?action=delete&id=${id}`;
            }
        }
    </script>
</body>

</html>