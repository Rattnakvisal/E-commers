<?php
include '../config/conn.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $address = $_POST["address"];
    $sql = "INSERT INTO users (name, email, password, address) VALUES ('$name', '$email', '$password', '$address')";
    if ($conn->query($sql) === TRUE) {
        $user_id = $conn->insert_id;
        $_SESSION["user_id"] = $user_id;
        $_SESSION["user_name"] = $name;
        $_SESSION["user_email"] = $email;
        header("Location: ../User/index.php");
        exit();
    } else {
        $error = "Registration failed: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Register</title>
    <link rel="stylesheet" href="assets/css/Style.css">
</head>
<style>
    /* Reset default styles */
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
        padding: 20px;
    }

    /* Form Container */
    .container {
        background: #fff;
        padding: 40px 30px;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        width: 400px;
        text-align: center;
        animation: fadeIn 0.6s ease-in-out;
    }

    h2 {
        margin-bottom: 20px;
        font-size: 28px;
        color: #333;
    }

    /* Input Fields */
    input[type="text"],
    input[type="email"],
    input[type="password"],
    textarea {
        width: 100%;
        padding: 12px;
        margin: 8px 0 16px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s ease;
    }

    textarea {
        resize: none;
        height: 80px;
    }

    input:focus,
    textarea:focus {
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

    /* Error Message */
    p[style*='color:red'] {
        color: #ff4d4d;
        margin-bottom: 10px;
        font-size: 14px;
    }

    /* Link */
    p a {
        color: #4facfe;
        text-decoration: none;
        font-weight: 500;
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
    @media (max-width: 450px) {
        .container {
            width: 100%;
            padding: 30px 20px;
        }
    }
</style>

<body>
    <div class="container">
        <h2>Register</h2>
        <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form method="post">
            <input type="text" name="name" placeholder="Name" required><br><br>
            <input type="email" name="email" placeholder="Email" required><br><br>
            <input type="password" name="password" placeholder="Password" required><br><br>
            <textarea name="address" placeholder="Address"></textarea><br><br>
            <button type="submit" class="btn">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
</body>

</html>