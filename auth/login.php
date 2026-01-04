<?php
include '../config/conn.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row["password"])) {
            $_SESSION["user_id"] = $row["id"];
            $_SESSION["user_name"] = $row["name"];
            $_SESSION["user_email"] = $row["email"];
            if ($row["email"] === "admin@ecommerce.com") {
                header("Location: ../admin/dashboard/dashboard.php");
            } else {
                header("Location: ../User/index.php");
            }
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Login</title>
    <link rel="stylesheet" href="assets/css/Style.css">
</head>
<style>
    /* Reset default browser styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    /* Container */
    .container {
        background: #fff;
        padding: 40px 30px;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        width: 350px;
        text-align: center;
        animation: fadeIn 0.6s ease-in-out;
    }

    h2 {
        margin-bottom: 20px;
        font-size: 28px;
        color: #333;
    }

    /* Inputs */
    input[type="email"],
    input[type="password"] {
        width: 100%;
        padding: 12px;
        margin: 8px 0 16px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s ease;
    }

    input[type="email"]:focus,
    input[type="password"]:focus {
        border-color: #4facfe;
        outline: none;
    }

    /* Button */
    .btn {
        width: 100%;
        padding: 12px;
        background: linear-gradient(to right, #4facfe, #00f2fe);
        border: none;
        border-radius: 8px;
        font-size: 18px;
        font-weight: 600;
        color: #fff;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    .btn:hover {
        background: linear-gradient(to right, #00f2fe, #4facfe);
    }

    /* Error message */
    p[style*='color:red'] {
        color: #ff4d4d;
        margin-bottom: 10px;
        font-size: 14px;
    }

    /* Register link */
    p a {
        color: #4facfe;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    p a:hover {
        color: #0078ff;
    }

    /* Animation */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive */
    @media (max-width: 400px) {
        .container {
            width: 90%;
            padding: 30px 20px;
        }
    }
</style>

<body>
    <div class="container">
        <h2>Login</h2>
        <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form method="post">
            <input type="email" name="email" placeholder="Email" required><br><br>
            <input type="password" name="password" placeholder="Password" required><br><br>
            <button type="submit" class="btn">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register</a></p>
    </div>
</body>

</html>