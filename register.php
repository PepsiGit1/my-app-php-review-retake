<?php
    session_start();

    $conn = new mysqli("localhost", "root", "", "reviewAndRetake");

    if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8");

    $error = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST["full_name"]);
    $email     = trim($_POST["Email"]);
    $password  = $_POST["Password"];
    $role      = $_POST["role"];

    if (empty($full_name) || empty($email) || empty($password) || empty($role)) {
        $error = "ກະລຸນາໃສ່ຂໍ້ມູນໃຫ້ຄົບ!";
    } elseif (strlen($password) < 6) {
        $error = "ລະຫັດຜ່ານຕ້ອງມີຢ່າງໜ້ອຍ 6 ຕົວອັກສອນ!";
    } else {
        if ($role === "student") {
            $table     = "tb_Student";
            $nameField = "Student_full_name";
        } else {
            $table     = "tb_Teacher";
            $nameField = "Teacher_fullname";
        }

        $check = $conn->prepare("SELECT * FROM $table WHERE Email = ?");
        if (! $check) {
            $error = "DB Error: " . $conn->error;
        } else {
            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = "Email ມີຢູ່ແລ້ວ!";
            } else {
                $stmt = $conn->prepare("INSERT INTO $table ($nameField, Email, Password) VALUES (?, ?, ?)");
                if (! $stmt) {
                    $error = "DB Error: " . $conn->error;
                } else {
                    $stmt->bind_param("sss", $full_name, $email, $password);
                    if ($stmt->execute()) {
                        header("Location: index.php");
                        exit();
                    } else {
                        $error = "ເກີດຂໍ້ຜິດພາດ: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
            $check->close();
        }
    }
    }
    $conn->close();
?>

<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <title>ລົງທະບຽນ</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Phetsarath OT', sans-serif;
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .form-container {
            background: white;
            padding: 35px 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(13, 71, 161, 0.3);
            width: 400px;
        }
        h2 { text-align: center; margin-bottom: 24px; color: #1a73e8; font-size: 22px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; font-weight: bold; color: #555; font-size: 14px; }
        input, select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
        }
        input:focus, select:focus { border-color: #1a73e8; box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.2); }
        .role-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .role-btn {
            flex: 1;
            padding: 10px;
            border: 2px solid #1a73e8;
            background: white;
            color: #1a73e8;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
        }
        .role-btn.active {
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
            color: white;
        }
        button[type="submit"] {
            width: 100%;
            padding: 11px;
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 6px;
        }
        button[type="submit"]:hover { opacity: 0.9; }
        .error { color: #c0392b; text-align: center; margin-bottom: 14px; background: #ffe0e0; padding: 10px; border-radius: 6px; }
        .login-link { text-align: center; margin-top: 16px; font-size: 14px; color: #666; }
        .login-link a { color: #1a73e8; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
<div class="form-container" style="font-family: 'Phetsarath OT', serif;">
    <h2>📝 ລົງທະບຽນ</h2>

    <?php if ($error): ?>
        <p class="error">⚠️ <?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <!-- Role Selector -->
        <div class="form-group">
            <label>ເລືອກບົດບາດ (Role)</label>
            <div class="role-selector">
                <button type="button" class="role-btn active" id="btn-student" onclick="selectRole('student')" style="font-family: 'Phetsarath OT', serif;">🎓 ນັກຮຽນ</button>
                <button type="button" class="role-btn" id="btn-teacher" onclick="selectRole('teacher')" style="font-family: 'Phetsarath OT', serif;">👨‍🏫 ຄູສອນ</button>
            </div>
            <input type="hidden" name="role" id="role" value="student">
        </div>

        <div class="form-group">
            <label id="name-label" style="font-family: 'Phetsarath OT', serif;">ຊື່ເຕັມນັກຮຽນ (Student Full Name)</label>
            <input style="font-family: 'Phetsarath OT', serif;" type="text" name="full_name" id="full_name" placeholder="ກະລຸນາໃສ່ຊື່ເຕັມ"
                value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label>ອີເມວ (Email)</label>
            <input style="font-family: 'Phetsarath OT', serif;" type="email" name="Email" placeholder="ກະລຸນາໃສ່ອີເມວ"
                value="<?php echo isset($_POST['Email']) ? htmlspecialchars($_POST['Email']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label>ລະຫັດຜ່ານ (Password)</label>
            <input style="font-family: 'Phetsarath OT', serif;" type="password" name="Password" placeholder="ຢ່າງໜ້ອຍ 6 ຕົວອັກສອນ" required>
        </div>
        <button style="font-family: 'Phetsarath OT', serif;" type="submit">ລົງທະບຽນ</button>
    </form>

    <div class="login-link">
        <p>ມີບັນຊີແລ້ວ? <a href="index.php">ເຂົ້າສູ່ລະບົບ</a></p>
    </div>
</div>

<script>
    function selectRole(role) {
        document.getElementById('role').value = role;
        document.getElementById('btn-student').classList.toggle('active', role === 'student');
        document.getElementById('btn-teacher').classList.toggle('active', role === 'teacher');
        document.getElementById('name-label').textContent = role === 'student'
            ? 'ຊື່ເຕັມນັກຮຽນ (Student Full Name)'
            : 'ຊື່ເຕັມຄູສອນ (Teacher Full Name)';
    }
</script>
</body>
</html>