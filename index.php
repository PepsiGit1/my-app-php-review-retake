<?php
    session_start();


    $host     = "localhost";
    $dbname   = "reviewAndRetake";
    $username = "root";
    $password = "";

    try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
    }

    $error = "";

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $inputUsername = trim($_POST["username"]);
    $inputPassword = trim($_POST["password"]);

    try {
        // ===== Check Teacher =====
        $stmt = $pdo->prepare("SELECT * FROM tb_Teacher WHERE Teacher_fullname = ?");
        $stmt->execute([$inputUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $inputPassword === $user["Password"]) {
            session_regenerate_id(true);
            $_SESSION["Teacher_fullname"] = $user["Teacher_fullname"];
            $_SESSION["role"]             = "teacher";
            header("Location: class.php");
            exit();
        }

        // ===== Check Student =====
        $stmt = $pdo->prepare("SELECT * FROM tb_Student WHERE Student_full_name = ?");
        $stmt->execute([$inputUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $inputPassword === $user["Password"]) {
            session_regenerate_id(true);
            $_SESSION["Student_full_name"] = $user["Student_full_name"];
            $_SESSION["role"]              = "student";
            header("Location: class.php");
            exit();
        }

        $error = "Invalid username or password.";

    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(13, 71, 161, 0.3);
            width: 100%;
            max-width: 400px;
        }
        .login-container h2 {
            text-align: center;
            margin-bottom: 24px;
            color: #1a73e8;
            font-size: 28px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
            outline: none;
        }
        .form-group input:focus {
            border-color: #1a73e8;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.2);
        }
        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        .btn-login:hover { opacity: 0.9; }
        .btn-login:disabled { opacity: 0.7; cursor: not-allowed; }
        .footer-text {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #777;
        }
        .footer-text a {
            color: #1a73e8;
            text-decoration: none;
            font-weight: 600;
        }
        .footer-text a:hover { text-decoration: underline; }
        .error-message {
            background: #ffe0e0;
            color: #c0392b;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            text-align: center;
        }
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.5);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            vertical-align: middle;
            margin-right: 8px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="login-container">
    <h2>🔐 Login</h2>

    <?php if (! empty($error)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Enter your username" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
        </div>
        <button type="submit" class="btn-login" id="loginBtn">Login</button>
    </form>

    <div class="footer-text">
        Don't have an account? <a href="register.php">Register</a>
    </div>
</div>

<script>
    document.querySelector("form").addEventListener("submit", function () {
        const btn = document.getElementById("loginBtn");
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span>Logging in...';
    });
</script>
</body>
</html>