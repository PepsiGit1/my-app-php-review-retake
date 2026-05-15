<?php
    date_default_timezone_set('Asia/Vientiane');
    session_start();

    $is_teacher = isset($_SESSION["role"]) && $_SESSION["role"] === "teacher";
    $is_student = isset($_SESSION["role"]) && $_SESSION["role"] === "student";

    if (! $is_teacher && ! $is_student) {
    header("Location: index.php");
    exit();
    }

    $display_name = $is_teacher
    ? $_SESSION["Teacher_fullname"]
    : $_SESSION["Student_full_name"];

    $class_id = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
    if (! $class_id) {
    header("Location: class.php");
    exit();
    }

    $conn = new mysqli("localhost", "root", "", "reviewAndRetake");
    if ($conn->connect_error) {
    die("ການເຊື່ອມຕໍ່ລົ້ມເຫລວ: " . $conn->connect_error);
    }

    // ===== Get Teacher_id =====
    $teacher_id = 0;
    if ($is_teacher) {
    $t_name = $_SESSION["Teacher_fullname"];
    $t_stmt = $conn->prepare("SELECT Teacher_id FROM tb_Teacher WHERE Teacher_fullname = ? LIMIT 1");
    if ($t_stmt) {
        $t_stmt->bind_param("s", $t_name);
        $t_stmt->execute();
        $t_stmt->bind_result($teacher_id);
        $t_stmt->fetch();
        $t_stmt->close();
    } else {
        die("ການສອບຖາມຄູລົ້ມເຫລວ: " . $conn->error);
    }
    }

    // ===== Get Student_id =====
    $display_student_id = 0;
    if ($is_student) {
    $s_name  = $_SESSION["Student_full_name"];
    $sd_stmt = $conn->prepare("SELECT Student_id FROM tb_Student WHERE Student_full_name = ? LIMIT 1");
    if ($sd_stmt) {
        $sd_stmt->bind_param("s", $s_name);
        $sd_stmt->execute();
        $sd_stmt->bind_result($display_student_id);
        $sd_stmt->fetch();
        $sd_stmt->close();
    }
    }

    $success_msg = "";
    $error_msg   = "";

    if (isset($_GET['success'])) {
    if ($_GET['success'] === 'lesson_created') {$success_msg = "ສ້າງບົດຮຽນສຳເລັດ!";}
    if ($_GET['success'] === 'lesson_updated') {$success_msg = "ອັບເດດບົດຮຽນສຳເລັດ!";}
    if ($_GET['success'] === 'lesson_deleted') {$success_msg = "ລຶບບົດຮຽນສຳເລັດ!";}
    if ($_GET['success'] === 'type_created') {$success_msg = "ສ້າງປະເພດບົດຮຽນສຳເລັດ!";}
    if ($_GET['success'] === 'exam_created') {$success_msg = "ສ້າງການສອບເສັງສຳເລັດ!";}
    if ($_GET['success'] === 'exam_updated') {$success_msg = "ອັບເດດການສອບເສັງສຳເລັດ!";}
    if ($_GET['success'] === 'exam_deleted') {$success_msg = "ລຶບການສອບເສັງສຳເລັດ!";}
    if ($_GET['success'] === 'exam_type_created') {$success_msg = "ສ້າງປະເພດການສອບເສັງສຳເລັດ!";}
    if ($_GET['success'] === 'enrolled') {$success_msg = "ລົງທະບຽນສຳເລັດ!";}
    if ($_GET['success'] === 'enroll_updated') {$success_msg = "ອັບເດດການລົງທະບຽນສຳເລັດ!";}
    if ($_GET['success'] === 'enroll_deleted') {$success_msg = "ລຶບການລົງທະບຽນສຳເລັດ!";}
    if ($_GET['success'] === 'exam_submitted') {$success_msg = "ສົ່ງການສອບເສັງສຳເລັດ!";}
    if ($_GET['success'] === 'score_given') {$success_msg = "ໃຫ້ຄະແນນສຳເລັດ!";}
    }
    if (isset($_GET['error'])) {
    $error_msg = urldecode($_GET['error']);
    }

    // ===== Handle Create Lesson Type (Teacher Only) =====
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_lesson_type']) && $is_teacher) {
    $l_type_name = trim($_POST['l_type_name']);
    if ($l_type_name !== "") {
        $stmt = $conn->prepare("INSERT INTO tb_Lesson_Type (L_Type_name) VALUES (?)");
        if (! $stmt) {die("ການກຽມລົ້ມເຫລວ: " . $conn->error);}
        $stmt->bind_param("s", $l_type_name);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: home.php?class_id=$class_id&success=type_created");
            exit();
        } else { $error_msg = "ຂໍ້ຜິດພາດ: " . $stmt->error;
            $stmt->close();}
    } else { $error_msg = "ຕ້ອງການຊື່ປະເພດບົດຮຽນ.";}
    }

    // ===== Handle Create Lesson (Teacher Only) =====
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_lesson']) && $is_teacher) {
    $lesson_name = trim($_POST['lesson_name']);
    $l_type_id   = intval($_POST['l_type_id']);
    $file_path   = "";

    if (isset($_FILES['lesson_pdf']) && $_FILES['lesson_pdf']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . "/uploads/lessons/";
        if (! is_dir($upload_dir)) {mkdir($upload_dir, 0777, true);}
        if (! is_writable($upload_dir)) {
            $error_msg = "ໄດເລກທໍລີອັບໂຫຼດບໍ່ສາມາດຂຽນໄດ້.";
        } else {
            $file_ext = strtolower(pathinfo($_FILES['lesson_pdf']['name'], PATHINFO_EXTENSION));
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mime     = finfo_file($finfo, $_FILES['lesson_pdf']['tmp_name']);
            finfo_close($finfo);
            if ($file_ext === "pdf" && $mime === "application/pdf") {
                $new_filename = substr(md5(uniqid()), 0, 12) . ".pdf";
                $target_path  = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['lesson_pdf']['tmp_name'], $target_path)) {
                    $file_path = "uploads/lessons/" . $new_filename;
                } else { $error_msg = "ການຍ້າຍໄຟລ໌ທີ່ອັບໂຫຼດລົ້ມເຫລວ.";}
            } else { $error_msg = "ອະນຸຍາດໃຫ້ເທົ່ານັ້ນໄຟລ໌ PDF.";}
        }
    }

    if ($error_msg === "" && $lesson_name !== "") {
        $stmt = $conn->prepare("INSERT INTO tb_Lesson (Lesson_name, File_Path, L_Type_id, Room_id, Teacher_id) VALUES (?, ?, ?, ?, ?)");
        if (! $stmt) {die("ການກຽມລົ້ມເຫລວ: " . $conn->error);}
        $stmt->bind_param("ssiii", $lesson_name, $file_path, $l_type_id, $class_id, $teacher_id);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: home.php?class_id=$class_id&success=lesson_created");
            exit();
        } else { $error_msg = "ຂໍ້ຜິດພາດ: " . $stmt->error;
            $stmt->close();}
    } elseif ($lesson_name === "") {$error_msg = "ຕ້ອງການຊື່ບົດຮຽນ.";}

    if ($error_msg !== "") {
        $conn->close();
        header("Location: home.php?class_id=$class_id&error=" . urlencode($error_msg));
        exit();
    }
    }

    // ===== Handle Update Lesson (Teacher Only) =====
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_lesson']) && $is_teacher) {
    $lesson_id   = intval($_POST['lesson_id']);
    $lesson_name = trim($_POST['lesson_name']);
    $l_type_id   = intval($_POST['l_type_id']);
    $old_path    = trim($_POST['old_file_path']);
    $file_path   = $old_path;

    if (isset($_FILES['lesson_pdf']) && $_FILES['lesson_pdf']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . "/uploads/lessons/";
        if (! is_dir($upload_dir)) {mkdir($upload_dir, 0777, true);}
        $file_ext = strtolower(pathinfo($_FILES['lesson_pdf']['name'], PATHINFO_EXTENSION));
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mime     = finfo_file($finfo, $_FILES['lesson_pdf']['tmp_name']);
        finfo_close($finfo);
        if ($file_ext === "pdf" && $mime === "application/pdf") {
            $new_filename = substr(md5(uniqid()), 0, 12) . ".pdf";
            $target_path  = $upload_dir . $new_filename;
            if (move_uploaded_file($_FILES['lesson_pdf']['tmp_name'], $target_path)) {
                if ($old_path && file_exists(__DIR__ . "/" . $old_path)) {unlink(__DIR__ . "/" . $old_path);}
                $file_path = "uploads/lessons/" . $new_filename;
            } else { $error_msg = "ການຍ້າຍໄຟລ໌ທີ່ອັບໂຫຼດລົ້ມເຫລວ.";}
        } else { $error_msg = "ອະນຸຍາດໃຫ້ເທົ່ານັ້ນໄຟລ໌ PDF.";}
    }

    if ($error_msg === "" && $lesson_name !== "") {
        $stmt = $conn->prepare("UPDATE tb_Lesson SET Lesson_name=?, File_Path=?, L_Type_id=? WHERE Lesson_id=?");
        if (! $stmt) {die("ການກຽມລົ້ມເຫລວ: " . $conn->error);}
        $stmt->bind_param("ssii", $lesson_name, $file_path, $l_type_id, $lesson_id);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: home.php?class_id=$class_id&success=lesson_updated");
            exit();
        } else { $error_msg = "ຂໍ້ຜິດພາດ: " . $stmt->error;
            $stmt->close();}
    } elseif ($lesson_name === "") {$error_msg = "ຕ້ອງການຊື່ບົດຮຽນ.";}

    if ($error_msg !== "") {
        $conn->close();
        header("Location: home.php?class_id=$class_id&error=" . urlencode($error_msg));
        exit();
    }
    }

    // ===== Handle Delete Lesson (Teacher Only) =====
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_lesson']) && $is_teacher) {
    $lesson_id = intval($_POST['lesson_id']);
    $stmt      = $conn->prepare("SELECT File_Path FROM tb_Lesson WHERE Lesson_id=?");
    if (! $stmt) {die("ການກຽມລົ້ມເຫລວ: " . $conn->error);}
    $stmt->bind_param("i", $lesson_id);
    $stmt->execute();
    $stmt->bind_result($fp);
    $stmt->fetch();
    $stmt->close();
    if ($fp && file_exists(__DIR__ . "/" . $fp)) {unlink(__DIR__ . "/" . $fp);}
    $stmt = $conn->prepare("DELETE FROM tb_Lesson WHERE Lesson_id=?");
    $stmt->bind_param("i", $lesson_id);
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: home.php?class_id=$class_id&success=lesson_deleted");
        exit();
    } else { $error_msg = "ຂໍ້ຜິດພາດ: " . $stmt->error;
        $stmt->close();}
    }

    // ===== Handle Create Exam Type (Teacher Only) =====
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_exam_type']) && $is_teacher) {
    $e_type_name = trim($_POST['e_type_name']);
    if ($e_type_name !== "") {
        $stmt = $conn->prepare("INSERT INTO tb_Exam_Type (E_Type_name) VALUES (?)");
        if (! $stmt) {die("ການກຽມລົ້ມເຫລວ: " . $conn->error);}
        $stmt->bind_param("s", $e_type_name);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: home.php?class_id=$class_id&success=exam_type_created&tab=exams");
            exit();
        } else { $error_msg = "ຂໍ້ຜິດພາດ: " . $stmt->error;
            $stmt->close();}
    } else { $error_msg = "ຕ້ອງການຊື່ປະເພດການສອບເສັງ.";}
    }

    // ===== Handle Create Exam (Teacher Only) =====
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_exam']) && $is_teacher) {
    $exam_name = trim($_POST['exam_name']);
    $e_type_id = intval($_POST['e_type_id']);
    $lesson_id = intval($_POST['lesson_id']);
    $start_at  = ! empty($_POST['start_at']) ? $_POST['start_at'] : null;
    $end_at    = ! empty($_POST['end_at']) ? $_POST['end_at'] : null;
    $file_path = "";

    if (isset($_FILES['exam_pdf']) && $_FILES['exam_pdf']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . "/uploads/exams/";
        if (! is_dir($upload_dir)) {mkdir($upload_dir, 0777, true);}
        if (! is_writable($upload_dir)) {
            $error_msg = "ໄດເລກທໍລີອັບໂຫຼດບໍ່ສາມາດຂຽນໄດ້.";
        } else {
            $file_ext = strtolower(pathinfo($_FILES['exam_pdf']['name'], PATHINFO_EXTENSION));
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mime     = finfo_file($finfo, $_FILES['exam_pdf']['tmp_name']);
            finfo_close($finfo);
            if ($file_ext === "pdf" && $mime === "application/pdf") {
                $new_filename = substr(md5(uniqid()), 0, 12) . ".pdf";
                $target_path  = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['exam_pdf']['tmp_name'], $target_path)) {
                    $file_path = "uploads/exams/" . $new_filename;
                } else { $error_msg = "ການຍ້າຍໄຟລ໌ທີ່ອັບໂຫຼດລົ້ມເຫລວ.";}
            } else { $error_msg = "ອະນຸຍາດໃຫ້ເທົ່ານັ້ນໄຟລ໌ PDF.";}
        }
    }

    if ($error_msg === "" && $exam_name !== "") {
        $stmt = $conn->prepare("INSERT INTO tb_Exam (Exam_name, File_Path, Lesson_id, E_Type_id, Room_id, start_at, end_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (! $stmt) {die("ການກຽມລົ້ມເຫລວ: " . $conn->error);}
        $stmt->bind_param("ssiisss", $exam_name, $file_path, $lesson_id, $e_type_id, $class_id, $start_at, $end_at);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: home.php?class_id=$class_id&success=exam_created&tab=exams");
            exit();
        } else { $error_msg = "ຂໍ້ຜິດພາດ: " . $stmt->error;
            $stmt->close();}
    } elseif ($exam_name === "") {$error_msg = "ຕ້ອງການຊື່ການສອບເສັງ.";}

    if ($error_msg !== "") {
        $conn->close();
        header("Location: home.php?class_id=$class_id&error=" . urlencode($error_msg) . "&tab=exams");
        exit();
    }
    }

    // ===== Handle Update Exam (Teacher Only) =====
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_exam']) && $is_teacher) {
    $exam_id   = intval($_POST['exam_id']);
    $exam_name = trim($_POST['exam_name']);
    $e_type_id = intval($_POST['e_type_id']);
    $lesson_id = intval($_POST['lesson_id']);
    $start_at  = ! empty($_POST['start_at']) ? $_POST['start_at'] : null;
    $end_at    = ! empty($_POST['end_at']) ? $_POST['end_at'] : null;
    $old_path  = trim($_POST['old_file_path']);
    $file_path = $old_path;

    if (isset($_FILES['exam_pdf']) && $_FILES['exam_pdf']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . "/uploads/exams/";
        if (! is_dir($upload_dir)) {mkdir($upload_dir, 0777, true);}
        $file_ext = strtolower(pathinfo($_FILES['exam_pdf']['name'], PATHINFO_EXTENSION));
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mime     = finfo_file($finfo, $_FILES['exam_pdf']['tmp_name']);
        finfo_close($finfo);
        if ($file_ext === "pdf" && $mime === "application/pdf") {
            $new_filename = substr(md5(uniqid()), 0, 12) . ".pdf";
            $target_path  = $upload_dir . $new_filename;
            if (move_uploaded_file($_FILES['exam_pdf']['tmp_name'], $target_path)) {
                if ($old_path && file_exists(__DIR__ . "/" . $old_path)) {unlink(__DIR__ . "/" . $old_path);}
                $file_path = "uploads/exams/" . $new_filename;
            } else { $error_msg = "ການຍ້າຍໄຟລ໌ທີ່ອັບໂຫຼດລົ້ມເຫລວ.";}
        } else { $error_msg = "ອະນຸຍາດໃຫ້ເທົ່ານັ້ນໄຟລ໌ PDF.";}
    }

    if ($error_msg === "" && $exam_name !== "") {
        $stmt = $conn->prepare("UPDATE tb_Exam SET Exam_name=?, File_Path=?, Lesson_id=?, E_Type_id=?, start_at=?, end_at=? WHERE Exam_id=?");
        if (! $stmt) {die("ການກຽມລົ້ມເຫລວ: " . $conn->error);}
        $stmt->bind_param("ssiissi", $exam_name, $file_path, $lesson_id, $e_type_id, $start_at, $end_at, $exam_id);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: home.php?class_id=$class_id&success=exam_updated&tab=exams");
            exit();
        } else { $error_msg = "ຂໍ້ຜິດພາດ: " . $stmt->error;
            $stmt->close();}
    } elseif ($exam_name === "") {$error_msg = "ຕ້ອງການຊື່ການສອບເສັງ.";}

    if ($error_msg !== "") {
        $conn->close();
        header("Location: home.php?class_id=$class_id&error=" . urlencode($error_msg) . "&tab=exams");
        exit();
    }
    }

    // ===== Handle Delete Exam (Teacher Only) =====
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_exam']) && $is_teacher) {
    $exam_id = intval($_POST['exam_id']);
    $stmt    = $conn->prepare("SELECT File_Path FROM tb_Exam WHERE Exam_id=?");
    if (! $stmt) {die("ການກຽມລົ້ມເຫລວ: " . $conn->error);}
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $stmt->bind_result($fp);
    $stmt->fetch();
    $stmt->close();
    if ($fp && file_exists(__DIR__ . "/" . $fp)) {unlink(__DIR__ . "/" . $fp);}
    $stmt = $conn->prepare("DELETE FROM tb_Exam WHERE Exam_id=?");
    $stmt->bind_param("i", $exam_id);
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: home.php?class_id=$class_id&success=exam_deleted&tab=exams");
        exit();
    } else { $error_msg = "ຂໍ້ຜິດພາດ: " . $stmt->error;
        $stmt->close();}
    }

    // ===== Handle Create Enrollment (Student Only) =====
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_enrollment']) && $is_student) {
    $enroll_lesson_id = intval($_POST['enroll_lesson_id']);
    $status           = isset($_POST['enroll_status']) ? trim($_POST['enroll_status']) : 'Studying';
    $allowed_statuses = ['Studying', 'Completed'];
    if (! in_array($status, $allowed_statuses)) {$status = 'Studying';}
    $student_id = $display_student_id;

    if ($student_id && $enroll_lesson_id) {
        $check = $conn->prepare("SELECT Enroll_id FROM tb_Enrollment WHERE Student_id=? AND Lesson_id=? AND Room_id=?");
        $check->bind_param("iii", $student_id, $enroll_lesson_id, $class_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $check->close();
            $conn->close();
            header("Location: home.php?class_id=$class_id&error=" . urlencode("ລົງທະບຽນໃນບົດຮຽນນີ້ແລ້ວ.") . "&tab=enrollment");
            exit();
        }
        $check->close();

        $stmt = $conn->prepare("INSERT INTO tb_Enrollment (Student_id, Lesson_id, Room_id, Status , approve) VALUES (?, ?, ?, ? , 0)");
        if (! $stmt) {
            $err = $conn->error;
            $conn->close();
            header("Location: home.php?class_id=$class_id&error=" . urlencode("ການກຽມລົ້ມເຫລວ: $err") . "&tab=enrollment");
            exit();
        }
        $stmt->bind_param("iiis", $student_id, $enroll_lesson_id, $class_id, $status);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: home.php?class_id=$class_id&success=enrolled&tab=enrollment");
            exit();
        } else {
            $err = $stmt->error;
            $stmt->close();
            $conn->close();
            header("Location: home.php?class_id=$class_id&error=" . urlencode("ຂໍ້ຜິດພາດ: $err") . "&tab=enrollment");
            exit();
        }
    } else {
        $conn->close();
        header("Location: home.php?class_id=$class_id&error=" . urlencode("ກະລຸນາເລືອກບົດຮຽນ.") . "&tab=enrollment");
        exit();
    }
    }

    // ===== Handle Update Enrollment (Student Only) =====
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_enrollment']) && $is_student) {
    $enroll_id        = intval($_POST['enroll_id']);
    $enroll_lesson_id = intval($_POST['enroll_lesson_id']);
    $status           = isset($_POST['enroll_status']) ? trim($_POST['enroll_status']) : 'Studying';
    $allowed_statuses = ['Studying', 'Completed'];
    if (! in_array($status, $allowed_statuses)) {$status = 'Studying';}
    $student_id = $display_student_id;

    $stmt = $conn->prepare("UPDATE tb_Enrollment SET Lesson_id=?, Status=? WHERE Enroll_id=? AND Student_id=? AND Room_id=?");
    if (! $stmt) {
        $err = $conn->error;
        $conn->close();
        header("Location: home.php?class_id=$class_id&error=" . urlencode("ການກຽມລົ້ມເຫລວ: $err") . "&tab=enrollment");
        exit();
    }
    $stmt->bind_param("isiii", $enroll_lesson_id, $status, $enroll_id, $student_id, $class_id);
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: home.php?class_id=$class_id&success=enroll_updated&tab=enrollment");
        exit();
    } else {
        $err = $stmt->error;
        $stmt->close();
        $conn->close();
        header("Location: home.php?class_id=$class_id&error=" . urlencode("ຂໍ້ຜິດພາດ: $err") . "&tab=enrollment");
        exit();
    }
    }

    // ===== Handle Delete Enrollment (Student Only) =====
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_enrollment']) && $is_student) {
    $enroll_id  = intval($_POST['enroll_id']);
    $student_id = $display_student_id;

    $stmt = $conn->prepare("DELETE FROM tb_Enrollment WHERE Enroll_id=? AND Student_id=? AND Room_id=?");
    if (! $stmt) {
        $err = $conn->error;
        $conn->close();
        header("Location: home.php?class_id=$class_id&error=" . urlencode("ການກຽມລົ້ມເຫລວ: $err") . "&tab=enrollment");
        exit();
    }
    $stmt->bind_param("iii", $enroll_id, $student_id, $class_id);
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: home.php?class_id=$class_id&success=enroll_deleted&tab=enrollment");
        exit();
    } else {
        $err = $stmt->error;
        $stmt->close();
        $conn->close();
        header("Location: home.php?class_id=$class_id&error=" . urlencode("ຂໍ້ຜິດພາດ: $err") . "&tab=enrollment");
        exit();
    }
    }
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['approve_enrollment']) && $is_teacher) {
    $enroll_id = intval($_POST['enroll_id']);
    $stmt      = $conn->prepare("UPDATE tb_Enrollment SET approve=1 WHERE Enroll_id=?");
    $stmt->bind_param("i", $enroll_id);
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: home.php?class_id=$class_id&success=enrolled&tab=enrollment");
        exit();
    } else {
        $err = $stmt->error;
        $stmt->close();
        $conn->close();
        header("Location: home.php?class_id=$class_id&error=" . urlencode($err) . "&tab=enrollment");
        exit();
    }
    }

    // ===== Handle Submit Exam (Student Only) =====
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_exam']) && $is_student) {
    $exam_id   = intval($_POST['exam_id']);
    $enroll_id = intval($_POST['enroll_id']) ?: null;
    $file_path = "";

    if (! $exam_id) {
        $conn->close();
        header("Location: home.php?class_id=$class_id&error=" . urlencode("ກະລຸນາເລືອກການສອບເສັງ.") . "&tab=submit_exam");
        exit();
    }

    $time_chk = $conn->prepare("SELECT start_at, end_at FROM tb_Exam WHERE Exam_id=?");
    $time_chk->bind_param("i", $exam_id);
    $time_chk->execute();
    $time_chk->bind_result($exam_start_at, $exam_end_at);
    $time_chk->fetch();
    $time_chk->close();

    $now = time();
    if (! empty($exam_start_at) && strtotime($exam_start_at) > $now) {
        $conn->close();
        header("Location: home.php?class_id=$class_id&error=" . urlencode("ການສອບເສັງນີ້ຍັງບໍ່ທັນເລີ່ມ.") . "&tab=submit_exam");
        exit();
    }
    if (! empty($exam_end_at) && strtotime($exam_end_at) < $now) {
        $conn->close();
        header("Location: home.php?class_id=$class_id&error=" . urlencode("ການສອບເສັງນີ້ສິ້ນສຸດແລ້ວ. ການສົ່ງປິດແລ້ວ.") . "&tab=submit_exam");
        exit();
    }

    if (isset($_FILES['submit_pdf']) && $_FILES['submit_pdf']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . "/uploads/submissions/";
        if (! is_dir($upload_dir)) {mkdir($upload_dir, 0777, true);}
        $file_ext = strtolower(pathinfo($_FILES['submit_pdf']['name'], PATHINFO_EXTENSION));
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mime     = finfo_file($finfo, $_FILES['submit_pdf']['tmp_name']);
        finfo_close($finfo);
        if ($file_ext === "pdf" && $mime === "application/pdf") {
            $new_filename = substr(md5(uniqid()), 0, 12) . ".pdf";
            $target_path  = $upload_dir . $new_filename;
            if (move_uploaded_file($_FILES['submit_pdf']['tmp_name'], $target_path)) {
                $file_path = "uploads/submissions/" . $new_filename;
            } else { $error_msg = "ການຍ້າຍໄຟລ໌ທີ່ອັບໂຫຼດລົ້ມເຫລວ.";}
        } else { $error_msg = "ອະນຸຍາດໃຫ້ເທົ່ານັ້ນໄຟລ໌ PDF.";}
    } else { $error_msg = "ກະລຸນາອັບໂຫຼດໄຟລ໌ PDF.";}

    if ($error_msg === "") {
        $chk = $conn->prepare("SELECT Submit_id FROM tb_Exam_Submit WHERE Exam_id=? AND Student_id=?");
        $chk->bind_param("ii", $exam_id, $display_student_id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $chk->close();
            $conn->close();
            header("Location: home.php?class_id=$class_id&error=" . urlencode("ສົ່ງການສອບເສັງນີ້ແລ້ວ.") . "&tab=submit_exam");
            exit();
        }
        $chk->close();

        $status = "Submitted";
        $stmt   = $conn->prepare("INSERT INTO tb_Exam_Submit (Submit_Status, File_Path, Enroll_id, Exam_id, Student_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiii", $status, $file_path, $enroll_id, $exam_id, $display_student_id);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: home.php?class_id=$class_id&success=exam_submitted&tab=submit_exam");
            exit();
        } else { $error_msg = "ຂໍ້ຜິດພາດ: " . $stmt->error;
            $stmt->close();}
    }

    if ($error_msg !== "") {
        $conn->close();
        header("Location: home.php?class_id=$class_id&error=" . urlencode($error_msg) . "&tab=submit_exam");
        exit();
    }
    }

    // ===== Handle Give Score (Teacher Only) =====
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['give_score']) && $is_teacher) {
    $submit_id = intval($_POST['submit_id']);
    $score     = intval($_POST['score']);
    $status    = "Graded";

    $stmt = $conn->prepare("UPDATE tb_Exam_Submit SET Score=?, Submit_Status=? WHERE Submit_id=?");
    $stmt->bind_param("isi", $score, $status, $submit_id);
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: home.php?class_id=$class_id&success=score_given&tab=submit_exam");
        exit();
    } else {
        $err = $stmt->error;
        $stmt->close();
        $conn->close();
        header("Location: home.php?class_id=$class_id&error=" . urlencode("ຂໍ້ຜິດພາດ: $err") . "&tab=submit_exam");
        exit();
    }
    }

    // ===== Fetch Lesson Types =====
    $lesson_types = [];
    $res_lt       = $conn->query("SELECT L_Type_id, L_Type_name FROM tb_Lesson_Type");
    if ($res_lt) {
    while ($row = $res_lt->fetch_assoc()) {$lesson_types[] = $row;}
    }

    // ===== Fetch Lessons filtered by Room_id =====
    $lessons = [];
    $stmt    = $conn->prepare("SELECT l.Lesson_id, l.Lesson_name, l.File_Path, l.L_Type_id, lt.L_Type_name
                               FROM tb_Lesson l
                               LEFT JOIN tb_Lesson_Type lt ON l.L_Type_id = lt.L_Type_id
                               WHERE l.Room_id = ?");
    if (! $stmt) {die("ການກຽມລົ້ມເຫລວ: " . $conn->error);}
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {$lessons[] = $row;}
    $stmt->close();

    // ===== Fetch Exam Types =====
    $exam_types = [];
    $res_et     = $conn->query("SELECT E_Type_id, E_Type_name FROM tb_Exam_Type");
    if ($res_et) {
    while ($row = $res_et->fetch_assoc()) {$exam_types[] = $row;}
    }

    // ===== Fetch Exams filtered by Room_id =====
    $exams = [];
    $stmt2 = $conn->prepare("SELECT e.Exam_id, e.Exam_name, e.File_Path, e.Lesson_id, e.E_Type_id,
                                     et.E_Type_name, l.Lesson_name, e.start_at, e.end_at
                              FROM tb_Exam e
                              LEFT JOIN tb_Exam_Type et ON e.E_Type_id = et.E_Type_id
                              LEFT JOIN tb_Lesson l ON e.Lesson_id = l.Lesson_id
                              WHERE e.Room_id = ?");
    if (! $stmt2) {die("ການກຽມລົ້ມເຫລວ: " . $conn->error);}
    $stmt2->bind_param("i", $class_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row = $result2->fetch_assoc()) {$exams[] = $row;}
    $stmt2->close();

    // ===== Fetch Enrollments for Student =====
    $enrollments = [];
    if ($is_student && $display_student_id) {
    $e_stmt = $conn->prepare("
            SELECT en.Enroll_id, en.Enroll_date, en.Status,
                   en.Student_id, en.Lesson_id, en.Room_id, en.approve,
                   l.Lesson_name, r.Room_name
            FROM tb_Enrollment en
            LEFT JOIN tb_Lesson l ON en.Lesson_id = l.Lesson_id
            LEFT JOIN tb_Room r ON en.Room_id = r.Room_id
            WHERE en.Student_id = ? AND en.Room_id = ?
        ");
    if ($e_stmt) {
        $e_stmt->bind_param("ii", $display_student_id, $class_id);
        $e_stmt->execute();
        $e_result = $e_stmt->get_result();
        while ($row = $e_result->fetch_assoc()) {$enrollments[] = $row;}
        $e_stmt->close();
    }
    }

    // ===== Fetch Student's Enrollments for Submit dropdown =====
    $student_enrollments = [];
    if ($is_student && $display_student_id) {
    $se_stmt = $conn->prepare("
            SELECT en.Enroll_id, en.Lesson_id, l.Lesson_name
            FROM tb_Enrollment en
            JOIN tb_Lesson l ON en.Lesson_id = l.Lesson_id
            WHERE en.Student_id = ? AND en.Room_id = ?
        ");
    if ($se_stmt) {
        $se_stmt->bind_param("ii", $display_student_id, $class_id);
        $se_stmt->execute();
        $se_res = $se_stmt->get_result();
        while ($row = $se_res->fetch_assoc()) {$student_enrollments[] = $row;}
        $se_stmt->close();
    }
    }

    // ===== Fetch My Submissions (Student) =====
    $submissions = [];
    if ($is_student && $display_student_id) {
    $sub_stmt = $conn->prepare("
            SELECT es.Submit_id, es.Submit_date, es.Submit_Status, es.Score, es.File_Path,
                   es.Enroll_id, es.Exam_id, e.Exam_name
            FROM tb_Exam_Submit es
            JOIN tb_Exam e ON es.Exam_id = e.Exam_id
            WHERE es.Student_id = ? AND e.Room_id = ?
        ");
    if ($sub_stmt) {
        $sub_stmt->bind_param("ii", $display_student_id, $class_id);
        $sub_stmt->execute();
        $sub_res = $sub_stmt->get_result();
        while ($row = $sub_res->fetch_assoc()) {$submissions[] = $row;}
        $sub_stmt->close();
    }
    }

    // ===== Fetch All Submissions (Teacher) =====
    $all_submissions = [];
    if ($is_teacher) {
    $all_sub_stmt = $conn->prepare("
            SELECT es.Submit_id, es.Submit_date, es.Submit_Status, es.Score, es.File_Path,
                   es.Enroll_id, es.Exam_id, e.Exam_name,
                   COALESCE(s.Student_full_name, 'Unknown') AS Student_full_name,
                   es.Student_id
            FROM tb_Exam_Submit es
            JOIN tb_Exam e ON es.Exam_id = e.Exam_id
            LEFT JOIN tb_Student s ON es.Student_id = s.Student_id
            WHERE e.Room_id = ?
            ORDER BY es.Submit_date DESC
        ");
    if ($all_sub_stmt) {
        $all_sub_stmt->bind_param("i", $class_id);
        $all_sub_stmt->execute();
        $all_sub_res = $all_sub_stmt->get_result();
        while ($row = $all_sub_res->fetch_assoc()) {$all_submissions[] = $row;}
        $all_sub_stmt->close();
    }
    }

    // ===== Fetch All Enrollments (Teacher Only) =====
    $all_enrollments = [];
    if ($is_teacher) {
    $all_enroll_stmt = $conn->prepare("
    SELECT en.Enroll_id, en.Enroll_date, en.Status, en.approve,
           en.Student_id, en.Lesson_id, en.Room_id,
           l.Lesson_name,
           COALESCE(s.Student_full_name, 'Unknown') AS Student_full_name
    FROM tb_Enrollment en
    LEFT JOIN tb_Lesson l ON en.Lesson_id = l.Lesson_id
    LEFT JOIN tb_Student s ON en.Student_id = s.Student_id
    WHERE en.Room_id = ?
    ORDER BY en.Enroll_date DESC
");
    if ($all_enroll_stmt) {
        $all_enroll_stmt->bind_param("i", $class_id);
        $all_enroll_stmt->execute();
        $all_enroll_res = $all_enroll_stmt->get_result();
        while ($row = $all_enroll_res->fetch_assoc()) {$all_enrollments[] = $row;}
        $all_enroll_stmt->close();
    }
    }

    $conn->close();
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ໜ້າຫຼັກ – EduRoom</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f4ff;
            min-height: 100vh;
        }

        /* ===== NAVBAR ===== */
        .top-bar {
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 12px rgba(13,71,161,0.3);
        }
        .top-bar h2 { color: #fff; font-size: 20px; font-weight: 700; }
        .role-badge {
            font-size: 12px;
            padding: 4px 12px;
            border-radius: 20px;
            margin-left: 10px;
            color: #fff;
            vertical-align: middle;
            background: rgba(255,255,255,0.25) !important;
            border: 1px solid rgba(255,255,255,0.4);
        }
        .btn-back {
            background: rgba(255,255,255,0.2);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.4);
            border-radius: 20px;
            padding: 8px 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
        }
        .btn-back:hover { background: rgba(255,255,255,0.35); }

        /* ===== MAIN CONTENT ===== */
        .main-content { max-width: 1200px; margin: 0 auto; padding: 28px 24px; }

        /* ===== TAB NAV ===== */
        .tab-nav {
            display: flex;
            gap: 6px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            background: #fff;
            padding: 8px;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(26,115,232,0.1);
        }
        .tab-btn {
            padding: 10px 22px;
            border: none;
            border-radius: 10px;
            background: transparent;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            transition: all 0.2s;
        }
        .tab-btn:hover { background: #e8f0fe; color: #1a73e8; }
        .tab-btn.active {
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
            color: #fff;
            box-shadow: 0 4px 12px rgba(26,115,232,0.3);
        }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* ===== SECTION HEADER ===== */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .section-header h3 { font-size: 20px; color: #1a73e8; font-weight: 700; }

        /* ===== BUTTONS ===== */
        .btn-create {
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-right: 8px;
            margin-bottom: 4px;
            transition: opacity 0.2s, transform 0.1s;
            box-shadow: 0 4px 12px rgba(26,115,232,0.3);
        }
        .btn-create:hover { opacity: 0.88; transform: translateY(-1px); }
        .btn-create.green {
            background: linear-gradient(135deg, #00897b, #00695c);
            box-shadow: 0 4px 12px rgba(0,137,123,0.3);
        }

        /* ===== CARD GRID ===== */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 18px;
        }
        .item-card {
            background: #fff;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 16px rgba(26,115,232,0.08);
            border: 1px solid #e8f0fe;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }
        .item-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 28px rgba(26,115,232,0.15);
        }
        .icon { font-size: 30px; margin-bottom: 10px; display: block; }
        .item-card strong { font-size: 15px; color: #222; display: block; margin-bottom: 6px; }
        .type-tag {
            display: inline-block;
            background: #e8f0fe;
            color: #1a73e8;
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 20px;
            margin-top: 5px;
            margin-right: 4px;
            font-weight: 500;
        }

        /* ===== CARD ACTIONS ===== */
        .card-actions { display: flex; gap: 8px; margin-top: 14px; }
        .btn-edit {
            flex: 1;
            background: #fff8e1;
            color: #f57c00;
            border: 1px solid #ffe082;
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-edit:hover { background: #ffe082; }
        .btn-delete {
            flex: 1;
            background: #fce4ec;
            color: #c62828;
            border: 1px solid #ffcdd2;
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-delete:hover { background: #ffcdd2; }

        /* ===== PDF BADGE ===== */
        .pdf-badge {
            display: inline-block;
            background: #e53935;
            color: #fff;
            font-size: 10px;
            padding: 2px 7px;
            border-radius: 5px;
            margin-right: 4px;
            font-weight: 700;
        }

        /* ===== ALERTS ===== */
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-weight: 500;
        }
        .alert-error {
            background: #fce4ec;
            color: #c62828;
            border: 1px solid #ffcdd2;
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-weight: 500;
        }

        /* ===== MODAL ===== */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(13,71,161,0.25);
            backdrop-filter: blur(4px);
            z-index: 999;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            width: 440px;
            box-shadow: 0 16px 48px rgba(13,71,161,0.2);
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.25s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        .modal h3 { margin-top: 0; color: #1a73e8; font-size: 18px; margin-bottom: 20px; }
        .modal label { display: block; margin-bottom: 6px; font-weight: 600; color: #444; font-size: 13px; }
        .modal input, .modal select {
            width: 100%;
            padding: 10px 13px;
            border: 1.5px solid #c5cae9;
            border-radius: 9px;
            font-size: 14px;
            margin-bottom: 16px;
            box-sizing: border-box;
            transition: border-color 0.2s;
            outline: none;
        }
        .modal input:focus, .modal select:focus {
            border-color: #1a73e8;
            box-shadow: 0 0 0 3px rgba(26,115,232,0.12);
        }
        .modal input[readonly] { background: #f5f5f5; color: #999; cursor: not-allowed; }
        .modal input[type="file"] { padding: 8px; background: #f8f9ff; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 4px; }
        .btn-submit {
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
            color: #fff;
            border: none;
            border-radius: 9px;
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(26,115,232,0.3);
            transition: opacity 0.2s;
        }
        .btn-submit:hover { opacity: 0.88; }
        .btn-cancel {
            background: #f0f4ff;
            color: #1a73e8;
            border: 1.5px solid #c5cae9;
            border-radius: 9px;
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-cancel:hover { background: #e8f0fe; }

        /* ===== CONFIRM OVERLAY ===== */
        .confirm-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(13,71,161,0.25);
            backdrop-filter: blur(4px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .confirm-overlay.open { display: flex; }
        .confirm-box {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            width: 360px;
            box-shadow: 0 16px 48px rgba(13,71,161,0.2);
            text-align: center;
            animation: slideUp 0.25s ease;
        }
        .confirm-box h4 { color: #c62828; margin-top: 0; font-size: 20px; margin-bottom: 10px; }
        .confirm-box p  { color: #555; margin-bottom: 22px; font-size: 14px; }
        .confirm-actions { display: flex; gap: 12px; justify-content: center; }

        /* ===== INFO ROW ===== */
        .info-row { display: flex; gap: 10px; margin-bottom: 16px; }
        .info-box {
            flex: 1;
            background: #e8f0fe;
            border: 1px solid #c5cae9;
            border-radius: 9px;
            padding: 10px 14px;
        }
        .info-box span { display: block; font-size: 10px; color: #1a73e8; font-weight: 700; margin-bottom: 3px; text-transform: uppercase; }
        .info-box strong { font-size: 15px; color: #333; }

        /* ===== SUBMIT TABLE ===== */
        .submit-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(26,115,232,0.08);
            overflow: hidden;
            border: 1px solid #e8f0fe;
        }
        .submit-table thead tr { background: linear-gradient(135deg, #1a73e8, #0d47a1); }
        .submit-table th { padding: 14px 16px; text-align: left; font-size: 13px; color: #fff; font-weight: 600; }
        .submit-table td { padding: 12px 16px; border-bottom: 1px solid #f0f4ff; font-size: 13px; color: #444; }
        .submit-table tbody tr:hover { background: #f8f9ff; }
        .submit-table tr:last-child td { border-bottom: none; }
        .badge-submitted {
            background: #fff8e1; color: #f57c00;
            padding: 4px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 600;
            border: 1px solid #ffe082;
        }
        .badge-graded {
            background: #e8f5e9; color: #2e7d32;
            padding: 4px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 600;
            border: 1px solid #a5d6a7;
        }

        /* ===== ANALYSIS CARDS ===== */
        .analysis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 18px;
        }
        .analysis-card {
            background: #fff;
            border-radius: 14px;
            padding: 28px 20px;
            text-align: center;
            box-shadow: 0 4px 16px rgba(26,115,232,0.08);
            border: 1px solid #e8f0fe;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .analysis-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 28px rgba(26,115,232,0.15);
        }
        .analysis-card .a-icon { font-size: 36px; margin-bottom: 12px; display: block; }
        .analysis-card .a-label { color: #777; font-size: 13px; margin-bottom: 8px; font-weight: 500; }
        .analysis-card .a-count { font-size: 40px; font-weight: 700; color: #1a73e8; line-height: 1; }
        .analysis-card .a-count.green { color: #2e7d32; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<div class="top-bar">
    <h2>
        👋 <?php echo htmlspecialchars($display_name); ?>
        <span class="role-badge">
            <?php echo $is_teacher ? '👨‍🏫 ຄູສອນ' : '🎓 ນັກຮຽນ'; ?>
        </span>
    </h2>
    <a href="class.php" class="btn-back">← ກັບໄປຫ້ອງຮຽນ</a>
</div>

<div class="main-content">

    <!-- TAB NAV -->
    <div class="tab-nav">
        <button class="tab-btn active" onclick="showTab('lessons', this)">📚 ບົດຮຽນ</button>
        <button class="tab-btn" onclick="showTab('exams', this)">📝 ການສອບເສັງ</button>
        <?php if ($is_teacher): ?>
            <button class="tab-btn" onclick="showTab('analysis', this)">📊 ການວິເຄາະ</button>
        <?php endif; ?>

        <button class="tab-btn" onclick="showTab('enrollment', this)">📋 ການລົງທະບຽນ</button>

        <button class="tab-btn" onclick="showTab('submit_exam', this)">📤 ສົ່ງການສອບເສັງ</button>
    </div>

    <!-- ===== LESSON PANEL ===== -->
    <div id="lessons" class="tab-panel active">
        <?php if ($success_msg && ! isset($_GET['tab'])): ?>
            <div class="alert-success" id="successAlert">✅ <?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>
        <?php if ($error_msg && ! isset($_GET['tab'])): ?>
            <div class="alert-error" id="errorAlert">❌ <?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>
        <div class="section-header">
            <h3>📚 ບົດຮຽນ</h3>
            <?php if ($is_teacher): ?>
            <div>
                <button class="btn-create" onclick="openModal('lessonModal')">➕ ບົດຮຽນໃໝ່</button>
                <button class="btn-create green" onclick="openModal('lessonTypeModal')">🗂️ ປະເພດໃໝ່</button>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-grid">
            <?php if (! empty($lessons)): ?>
                <?php foreach ($lessons as $lesson): ?>
                    <div class="item-card">
                        <span class="icon">📖</span>
                        <strong><?php echo htmlspecialchars($lesson['Lesson_name']); ?></strong>
                        <span class="type-tag"><?php echo htmlspecialchars($lesson['L_Type_name'] ?? 'ບໍ່ມີ'); ?></span>
                        <?php if (! empty($lesson['File_Path'])): ?>
                            <div style="margin-top:10px;">
                                <a href="<?php echo htmlspecialchars($lesson['File_Path']); ?>" target="_blank"
                                   style="font-size:13px;color:#1a73e8;text-decoration:none;font-weight:600;">
                                    <span class="pdf-badge">PDF</span> ເບິ່ງ / ດາວໂຫຼດ
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if ($is_teacher): ?>
                            <div class="card-actions">
                                <button class="btn-edit" onclick="openEditLesson(<?php echo $lesson['Lesson_id']; ?>,'<?php echo addslashes($lesson['Lesson_name']); ?>',<?php echo intval($lesson['L_Type_id']); ?>,'<?php echo addslashes($lesson['File_Path'] ?? ''); ?>')">✏️ ແກ້ໄຂ</button>
                                <button class="btn-delete" onclick="confirmDeleteItem('lesson', <?php echo $lesson['Lesson_id']; ?>)">🗑️ ລຶບ</button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:#999;grid-column:1/-1;padding:20px 0;">ບໍ່ມີບົດຮຽນ.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== EXAM PANEL ===== -->
    <div id="exams" class="tab-panel">
        <?php if ($success_msg && isset($_GET['tab']) && $_GET['tab'] === 'exams'): ?>
            <div class="alert-success" id="successAlert">✅ <?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>
        <?php if ($error_msg && isset($_GET['tab']) && $_GET['tab'] === 'exams'): ?>
            <div class="alert-error" id="errorAlert">❌ <?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>
        <div class="section-header">
            <h3>📝 ການສອບເສັງ</h3>
            <?php if ($is_teacher): ?>
            <div>
                <button class="btn-create" onclick="openModal('examModal')">➕ ການສອບເສັງໃໝ່</button>
                <button class="btn-create green" onclick="openModal('examTypeModal')">🗂️ ປະເພດໃໝ່</button>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-grid">
            <?php if (! empty($exams)): ?>
                <?php foreach ($exams as $exam):
                        $now          = time();
                        $exam_ended   = ! empty($exam['end_at']) && strtotime($exam['end_at']) < $now;
                        $exam_not_yet = ! empty($exam['start_at']) && strtotime($exam['start_at']) > $now;
                        $exam_open    = ! $exam_ended && ! $exam_not_yet;
                ?>
                    <div class="item-card">
                        <span class="icon">📝</span>
                        <strong><?php echo htmlspecialchars($exam['Exam_name']); ?></strong>
                        <span class="type-tag"><?php echo htmlspecialchars($exam['E_Type_name'] ?? 'ບໍ່ມີ'); ?></span>
                        <?php if (! empty($exam['Lesson_name'])): ?>
                            <span class="type-tag" style="background:#fff8e1;color:#f57c00;">📚 <?php echo htmlspecialchars($exam['Lesson_name']); ?></span>
                        <?php endif; ?>
                        <?php if (! empty($exam['start_at'])): ?>
                            <div style="font-size:11px;color:#555;margin-top:8px;">🟢 ເລີ່ມ: <strong><?php echo date('d M Y H:i', strtotime($exam['start_at'])); ?></strong></div>
                        <?php endif; ?>
                        <?php if (! empty($exam['end_at'])): ?>
                            <div style="font-size:11px;color:#555;margin-top:4px;">🔴 ສິ້ນສຸດ: <strong><?php echo date('d M Y H:i', strtotime($exam['end_at'])); ?></strong></div>
                        <?php endif; ?>
                        <?php if ($exam_ended): ?>
                            <span class="type-tag" style="background:#fce4ec;color:#c62828;margin-top:6px;">🔒 ປິດແລ້ວ</span>
                        <?php elseif ($exam_not_yet): ?>
                            <span class="type-tag" style="background:#fff8e1;color:#f57c00;margin-top:6px;">⏳ ຍັງບໍ່ທັນເລີ່ມ</span>
                        <?php else: ?>
                            <span class="type-tag" style="background:#e8f5e9;color:#2e7d32;margin-top:6px;">✅ ເປີດຢູ່</span>
                        <?php endif; ?>
                        <?php if (! empty($exam['File_Path'])): ?>
                            <div style="margin-top:10px;">
                                <a href="<?php echo htmlspecialchars($exam['File_Path']); ?>" target="_blank"
                                   style="font-size:13px;color:#1a73e8;text-decoration:none;font-weight:600;">
                                    <span class="pdf-badge">PDF</span> ເບິ່ງ / ດາວໂຫຼດ
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if ($is_teacher): ?>
                            <div class="card-actions">
                                <button class="btn-edit" onclick="openEditExam(
                                    <?php echo $exam['Exam_id']; ?>,
                                    '<?php echo addslashes($exam['Exam_name']); ?>',
                                    <?php echo intval($exam['E_Type_id']); ?>,
                                    <?php echo intval($exam['Lesson_id']); ?>,
                                    '<?php echo addslashes($exam['File_Path'] ?? ''); ?>',
                                    '<?php echo $exam['start_at'] ?? ''; ?>',
                                    '<?php echo $exam['end_at'] ?? ''; ?>'
                                )">✏️ ແກ້ໄຂ</button>
                                <button class="btn-delete" onclick="confirmDeleteItem('exam', <?php echo $exam['Exam_id']; ?>)">🗑️ ລຶບ</button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:#999;grid-column:1/-1;padding:20px 0;">ບໍ່ມີການສອບເສັງ.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== ANALYSIS PANEL (Teacher Only) ===== -->
    <?php if ($is_teacher): ?>
    <div id="analysis" class="tab-panel">
        <?php if ($success_msg && isset($_GET['tab']) && $_GET['tab'] === 'analysis'): ?>
            <div class="alert-success" id="successAlert">✅ <?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>

        <div class="section-header"><h3>📊 ການວິເຄາະ</h3></div>
        <div class="analysis-grid">
            <div class="analysis-card">
                <span class="a-icon">📚</span>
                <div class="a-label">ບົດຮຽນທັງໝົດ</div>
                <div class="a-count"><?php echo count($lessons); ?></div>
            </div>
            <div class="analysis-card">
                <span class="a-icon">🗂️</span>
                <div class="a-label">ປະເພດບົດຮຽນ</div>
                <div class="a-count"><?php echo count($lesson_types); ?></div>
            </div>
            <div class="analysis-card">
                <span class="a-icon">📝</span>
                <div class="a-label">ການສອບເສັງທັງໝົດ</div>
                <div class="a-count"><?php echo count($exams); ?></div>
            </div>
            <div class="analysis-card">
                <span class="a-icon">🗂️</span>
                <div class="a-label">ປະເພດການສອບເສັງ</div>
                <div class="a-count"><?php echo count($exam_types); ?></div>
            </div>
            <div class="analysis-card">
                <span class="a-icon">📋</span>
                <div class="a-label">ການລົງທະບຽນທັງໝົດ</div>
                <div class="a-count"><?php echo count($all_enrollments); ?></div>
            </div>
            <div class="analysis-card">
                <span class="a-icon">📤</span>
                <div class="a-label">ການສົ່ງທັງໝົດ</div>
                <div class="a-count"><?php echo count($all_submissions); ?></div>
            </div>
            <div class="analysis-card">
                <span class="a-icon">✅</span>
                <div class="a-label">ຕັດເກຣດແລ້ວ</div>
                <div class="a-count green"><?php echo count(array_filter($all_submissions, fn($s) => $s['Submit_Status'] === 'Graded')); ?></div>
            </div>
        </div>

        <!-- ===== Enrollment Table for Teacher ===== -->
        <div class="section-header" style="margin-top:36px;">
            <h3>📋 ນັກຮຽນທີ່ລົງທະບຽນ</h3>
        </div>
        <div style="overflow-x:auto;margin-top:10px;">
            <table class="submit-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ນັກຮຽນ</th>
                        <th>ບົດຮຽນ</th>
                        <th>ສະຖານະ</th>
                        <th>ວັນທີລົງທະບຽນ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($all_enrollments)): ?>
                        <?php foreach ($all_enrollments as $i => $enroll): ?>
                            <tr>
                                <td style="color:#999;font-weight:600;"><?php echo $i + 1; ?></td>
                                <td>👤 <?php echo htmlspecialchars($enroll['Student_full_name']); ?></td>
                                <td>📚 <?php echo htmlspecialchars($enroll['Lesson_name'] ?? 'ບໍ່ມີ'); ?></td>
                                <td>
                                    <span class="<?php echo $enroll['Status'] === 'Completed' ? 'badge-graded' : 'badge-submitted'; ?>">
                                        <?php echo $enroll['Status'] === 'Completed' ? '✅' : '📖'; ?>
                                        <?php echo htmlspecialchars($enroll['Status']); ?>
                                    </span>
                                </td>
                                <td style="color:#aaa;font-size:12px;">📅 <?php echo date('d M Y H:i', strtotime($enroll['Enroll_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center;color:#aaa;padding:30px;">ຍັງບໍ່ມີນັກຮຽນລົງທະບຽນ.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== ENROLLMENT PANEL (Student Only) ===== -->

    <div id="enrollment" class="tab-panel">
        <?php if ($success_msg && isset($_GET['tab']) && $_GET['tab'] === 'enrollment'): ?>
            <div class="alert-success" id="successAlert">✅ <?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>
        <?php if ($error_msg && isset($_GET['tab']) && $_GET['tab'] === 'enrollment'): ?>
            <div class="alert-error" id="errorAlert">❌ <?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>
        <div class="section-header">
            <h3>📋 <?php echo $is_teacher ? 'ການລົງທະບຽນທັງໝົດ' : 'ການລົງທະບຽນຂອງຂ້ອຍ'; ?></h3>
            <?php if ($is_student): ?>
            <button class="btn-create" onclick="openModal('enrollModal')">➕ ລົງທະບຽນໃນບົດຮຽນ</button>
            <?php endif; ?>
        </div>
        <div class="card-grid">
            <?php
                $display_enrollments = $is_teacher ? $all_enrollments : $enrollments;
            if (! empty($display_enrollments)): ?>
            <?php foreach ($display_enrollments as $enroll): ?>
                <div class="item-card">
                <span class="icon">📋</span>
                <?php if ($is_teacher): ?>
                    <strong>👤 <?php echo htmlspecialchars($enroll['Student_full_name'] ?? 'Unknown'); ?></strong>
                <?php endif; ?>
                <strong><?php echo htmlspecialchars($enroll['Lesson_name'] ?? 'ບໍ່ມີ'); ?></strong>
                <span class="type-tag">🏫 ຫ້ອງ: <?php echo htmlspecialchars($enroll['Room_id']); ?></span>
                <span class="type-tag">👤 ລະຫັດນັກຮຽນ: <?php echo htmlspecialchars($enroll['Student_id']); ?></span>
                <span class="type-tag">📖 ລະຫັດບົດຮຽນ: <?php echo htmlspecialchars($enroll['Lesson_id']); ?></span>
                <span class="type-tag" style="background:<?php echo $enroll['Status'] === 'Completed' ? '#e8f5e9' : '#e8f0fe'; ?>;color:<?php echo $enroll['Status'] === 'Completed' ? '#2e7d32' : '#1a73e8'; ?>;">
                    <?php echo $enroll['Status'] === 'Completed' ? '✅' : '📖'; ?> <?php echo htmlspecialchars($enroll['Status'] ?? 'Studying'); ?>
                </span>
                <?php if (isset($enroll['approve'])): ?>
                    <span class="type-tag" style="background:<?php echo $enroll['approve'] == 1 ? '#e8f5e9' : '#fff8e1'; ?>;color:<?php echo $enroll['approve'] == 1 ? '#2e7d32' : '#f57c00'; ?>;">
                    <?php echo $enroll['approve'] == 1 ? '✅ ອະນຸມັດແລ້ວ' : '⏳ ລໍຖ້າອະນຸມັດ'; ?>
                    </span>
                <?php endif; ?>
                <div style="font-size:11px;color:#aaa;margin-top:8px;">📅 <?php echo date('d M Y H:i', strtotime($enroll['Enroll_date'])); ?></div>
                <div class="card-actions">
                    <?php if ($is_student): ?>
                        <?php if (isset($enroll['approve']) && $enroll['approve'] == 1): ?>
                            <button class="btn-edit" style="background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;cursor:default;flex:2;" disabled>✅ ອະນຸມັດແລ້ວ</button>
                        <?php else: ?>
                            <button class="btn-edit" onclick="openEditEnroll(<?php echo $enroll['Enroll_id']; ?>,<?php echo $enroll['Lesson_id']; ?>,'<?php echo addslashes($enroll['Status']); ?>')">✏️ ແກ້ໄຂ</button>
                            <button class="btn-delete" onclick="confirmDeleteEnroll(<?php echo $enroll['Enroll_id']; ?>)">🗑️ ລຶບ</button>
                        <?php endif; ?>
                    <?php elseif ($is_teacher): ?>
                        <?php if (! isset($enroll['approve']) || $enroll['approve'] != 1): ?>
                            <form method="POST" action="home.php?class_id=<?php echo $class_id; ?>" style="display:inline;">
                            <input type="hidden" name="enroll_id" value="<?php echo $enroll['Enroll_id']; ?>">
                            <button type="submit" name="approve_enrollment" class="btn-edit" style="background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;">✅ ອະນຸມັດ</button>
                            </form>
                        <?php else: ?>
                            <button class="btn-edit" style="background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;opacity:0.5;cursor:not-allowed;" disabled>✅ ອະນຸມັດແລ້ວ</button>
                        <?php endif; ?>
                        <button class="btn-delete" onclick="confirmDeleteEnrollTeacher(<?php echo $enroll['Enroll_id']; ?>)">🗑️ ລຶບ</button>
                    <?php endif; ?>
                </div>
                </div>
            <?php endforeach; ?>
            <?php else: ?>
            <p style="color:#999;grid-column:1/-1;padding:20px 0;">ບໍ່ພົບການລົງທະບຽນ.</p>
            <?php endif; ?>
        </div>
        </div>


    <!-- ===== SUBMIT EXAM PANEL ===== -->
    <div id="submit_exam" class="tab-panel">
        <?php if ($success_msg && isset($_GET['tab']) && $_GET['tab'] === 'submit_exam'): ?>
            <div class="alert-success" id="successAlert">✅ <?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>
        <?php if ($error_msg && isset($_GET['tab']) && $_GET['tab'] === 'submit_exam'): ?>
            <div class="alert-error" id="errorAlert">❌ <?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <?php if ($is_student): ?>
            <div class="section-header">
                <h3>📤 ການສົ່ງຂອງຂ້ອຍ</h3>
                <button class="btn-create" onclick="openModal('submitExamModal')">📤 ສົ່ງການສອບເສັງ</button>
            </div>
            <div class="card-grid">
                <?php if (! empty($submissions)): ?>
                    <?php foreach ($submissions as $sub): ?>
                        <div class="item-card">
                            <span class="icon">📤</span>
                            <strong><?php echo htmlspecialchars($sub['Exam_name']); ?></strong>
                            <span class="type-tag" style="background:<?php echo $sub['Submit_Status'] === 'Graded' ? '#e8f5e9' : '#fff8e1'; ?>;color:<?php echo $sub['Submit_Status'] === 'Graded' ? '#2e7d32' : '#f57c00'; ?>;">
                                <?php echo $sub['Submit_Status'] === 'Graded' ? '✅' : '⏳'; ?> <?php echo htmlspecialchars($sub['Submit_Status']); ?>
                            </span>
                            <?php if ($sub['Score'] !== null): ?>
                                <span class="type-tag" style="background:#e8f0fe;color:#1a73e8;font-size:14px;font-weight:700;">🏆 ຄະແນນ: <?php echo $sub['Score']; ?></span>
                            <?php endif; ?>
                            <?php if (! empty($sub['File_Path'])): ?>
                                <div style="margin-top:10px;">
                                    <a href="<?php echo htmlspecialchars($sub['File_Path']); ?>" target="_blank"
                                       style="font-size:13px;color:#1a73e8;text-decoration:none;font-weight:600;">
                                        <span class="pdf-badge">PDF</span> ເບິ່ງການສົ່ງ
                                    </a>
                                </div>
                            <?php endif; ?>
                            <div style="font-size:11px;color:#aaa;margin-top:8px;">📅 <?php echo date('d M Y H:i', strtotime($sub['Submit_date'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#999;grid-column:1/-1;padding:20px 0;">ຍັງບໍ່ມີການສົ່ງ.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($is_teacher): ?>
            <div class="section-header"><h3>📤 ການສົ່ງທັງໝົດ</h3></div>
            <div style="overflow-x:auto;margin-top:10px;">
                <table class="submit-table">
                    <thead>
                        <tr>
                            <th>#</th><th>ນັກຮຽນ</th><th>ການສອບເສັງ</th><th>ສະຖານະ</th>
                            <th>ຄະແນນ</th><th>ໄຟລ໌</th><th>ວັນທີ</th><th>ການດຳເນີນການ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (! empty($all_submissions)): ?>
                            <?php foreach ($all_submissions as $i => $sub): ?>
                                <tr>
                                    <td style="color:#999;font-weight:600;"><?php echo $i + 1; ?></td>
                                    <td>👤 <?php echo htmlspecialchars($sub['Student_full_name']); ?></td>
                                    <td>📝 <?php echo htmlspecialchars($sub['Exam_name']); ?></td>
                                    <td>
                                        <span class="<?php echo $sub['Submit_Status'] === 'Graded' ? 'badge-graded' : 'badge-submitted'; ?>">
                                            <?php echo $sub['Submit_Status'] === 'Graded' ? '✅' : '⏳'; ?>
                                            <?php echo htmlspecialchars($sub['Submit_Status']); ?>
                                        </span>
                                    </td>
                                    <td style="font-weight:700;color:#1a73e8;">
                                        <?php echo $sub['Score'] !== null ? $sub['Score'] : '—'; ?>
                                    </td>
                                    <td>
                                        <?php if (! empty($sub['File_Path'])): ?>
                                            <a href="<?php echo htmlspecialchars($sub['File_Path']); ?>" target="_blank"
                                               style="color:#1a73e8;font-size:13px;text-decoration:none;font-weight:600;">
                                                <span class="pdf-badge">PDF</span> ເບິ່ງ
                                            </a>
                                        <?php else: ?>
                                            <span style="color:#ccc;">ບໍ່ມີໄຟລ໌</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color:#aaa;font-size:12px;"><?php echo date('d M Y H:i', strtotime($sub['Submit_date'])); ?></td>
                                    <td>
                                        <button class="btn-edit" onclick="openGiveScore(<?php echo $sub['Submit_id']; ?>,'<?php echo addslashes($sub['Student_full_name']); ?>','<?php echo addslashes($sub['Exam_name']); ?>',<?php echo $sub['Score'] !== null ? $sub['Score'] : 0; ?>)">
                                            🏆 ໃຫ້ຄະແນນ
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center;color:#aaa;padding:30px;">ຍັງບໍ່ມີການສົ່ງ.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div><!-- end main-content -->

<!-- ===== MODALS ===== -->

<?php if ($is_student): ?>
<!-- CREATE ENROLL MODAL -->
<div class="modal-overlay" id="enrollModal">
    <div class="modal">
        <h3>➕ ລົງທະບຽນໃນບົດຮຽນ</h3>
        <form method="POST" action="home.php?class_id=<?php echo $class_id; ?>">
            <div class="info-row">
                <div class="info-box"><span>👤 ລະຫັດນັກຮຽນ</span><strong><?php echo $display_student_id; ?></strong></div>
                <div class="info-box"><span>🏫 ລະຫັດຫ້ອງ</span><strong><?php echo $class_id; ?></strong></div>
            </div>
            <label>ເລືອກບົດຮຽນ <span style="color:#e53935">*</span></label>
            <select name="enroll_lesson_id" id="enroll_lesson_select" required onchange="updateLessonId(this.value)">
                <option value="">-- ເລືອກບົດຮຽນ --</option>
                <?php foreach ($lessons as $ls): ?>
                    <option value="<?php echo $ls['Lesson_id']; ?>">[ID: <?php echo $ls['Lesson_id']; ?>] <?php echo htmlspecialchars($ls['Lesson_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="info-row">
                <div class="info-box" style="flex:none;min-width:120px;"><span>📖 ລະຫັດບົດຮຽນ</span><strong id="lesson_id_display">—</strong></div>
            </div>
            <label>ສະຖານະ</label>
            <select name="enroll_status">
                <option value="Studying">ຍັງຮຽນ (Studying)</option>
                <option value="Completed">ຮຽນຈົບແລ້ວ (Completed)</option>
            </select>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('enrollModal')">ຍົກເລີກ</button>
                <button type="submit" name="create_enrollment" class="btn-submit">📋 ລົງທະບຽນ</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT ENROLL MODAL -->
<div class="modal-overlay" id="editEnrollModal">
    <div class="modal">
        <h3>✏️ ແກ້ໄຂການລົງທະບຽນ</h3>
        <form method="POST" action="home.php?class_id=<?php echo $class_id; ?>">
            <input type="hidden" name="enroll_id" id="edit_enroll_id">
            <div class="info-row">
                <div class="info-box"><span>👤 ລະຫັດນັກຮຽນ</span><strong><?php echo $display_student_id; ?></strong></div>
                <div class="info-box"><span>🏫 ລະຫັດຫ້ອງ</span><strong><?php echo $class_id; ?></strong></div>
            </div>
            <label>ເລືອກບົດຮຽນ <span style="color:#e53935">*</span></label>
            <select name="enroll_lesson_id" id="edit_enroll_lesson" required>
                <option value="">-- ເລືອກບົດຮຽນ --</option>
                <?php foreach ($lessons as $ls): ?>
                    <option value="<?php echo $ls['Lesson_id']; ?>">[ID: <?php echo $ls['Lesson_id']; ?>] <?php echo htmlspecialchars($ls['Lesson_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <label>ສະຖານະ</label>
            <select name="enroll_status" id="edit_enroll_status">
                <option value="Studying">ຍັງຮຽນ (Studying)</option>
                <option value="Completed">ຮຽນຈົບແລ້ວ (Completed)</option>
            </select>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('editEnrollModal')">ຍົກເລີກ</button>
                <button type="submit" name="update_enrollment" class="btn-submit">💾 ອັບເດດ</button>
            </div>
        </form>
    </div>
</div>

<!-- CONFIRM DELETE ENROLL -->
<div class="confirm-overlay" id="confirmEnrollOverlay">
    <div class="confirm-box">
        <h4>🗑️ ຢືນຢັນການລຶບ</h4>
        <p>ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລຶບການລົງທະບຽນນີ້?</p>
        <form method="POST" action="home.php?class_id=<?php echo $class_id; ?>">
            <input type="hidden" name="enroll_id" id="delete_enroll_id">
            <div class="confirm-actions">
                <button type="button" class="btn-cancel" onclick="closeConfirmEnroll()">ຍົກເລີກ</button>
                <button type="submit" name="delete_enrollment" class="btn-delete">🗑️ ລຶບ</button>
            </div>
        </form>
    </div>
</div>

<!-- SUBMIT EXAM MODAL -->
<div class="modal-overlay" id="submitExamModal">
    <div class="modal">
        <h3>📤 ສົ່ງການສອບເສັງ</h3>
        <form method="POST" action="home.php?class_id=<?php echo $class_id; ?>" enctype="multipart/form-data">
            <div class="info-row">
                <div class="info-box"><span>👤 ລະຫັດນັກຮຽນ</span><strong><?php echo $display_student_id; ?></strong></div>
                <div class="info-box"><span>🏫 ລະຫັດຫ້ອງ</span><strong><?php echo $class_id; ?></strong></div>
            </div>
            <label>ເລືອກການສອບເສັງ <span style="color:#e53935">*</span></label>
            <select name="exam_id" required>
                <option value="">-- ເລືອກການສອບເສັງ --</option>
                <?php foreach ($exams as $ex):
                        $now        = time();
                        $is_ended   = ! empty($ex['end_at']) && strtotime($ex['end_at']) < $now;
                        $is_not_yet = ! empty($ex['start_at']) && strtotime($ex['start_at']) > $now;
                        $disabled   = ($is_ended || $is_not_yet) ? 'disabled style="color:#aaa;"' : '';
                        $suffix     = $is_ended ? ' 🔒 ປິດແລ້ວ' : ($is_not_yet ? ' ⏳ ຍັງບໍ່ທັນເລີ່ມ' : ' ✅ ເປີດຢູ່');
                ?>
                    <option value="<?php echo $ex['Exam_id']; ?>" <?php echo $disabled; ?>>
                        [ID: <?php echo $ex['Exam_id']; ?>] <?php echo htmlspecialchars($ex['Exam_name']); ?><?php echo $suffix; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label>ເລືອກການລົງທະບຽນ</label>
            <select name="enroll_id">
                <option value="">-- ເລືອກການລົງທະບຽນ (ທາງເລືອກ) --</option>
                <?php foreach ($student_enrollments as $en): 
                    // Check if this enrollment is approved
                    $is_approved = false;
                    foreach ($enrollments as $enroll) {
                        if ($enroll['Enroll_id'] == $en['Enroll_id'] && isset($enroll['approve']) && $enroll['approve'] == 1) {
                            $is_approved = true;
                            break;
                        }
                    }
                    $disabled = !$is_approved ? 'disabled style="color:#aaa;"' : '';
                    $suffix = !$is_approved ? ' ⏳ ລໍຖ້າອະນຸມັດ' : ' ✅ ອະນຸມັດແລ້ວ';
                ?>
                    <option value="<?php echo $en['Enroll_id']; ?>" <?php echo $disabled; ?>>
                        [#<?php echo $en['Enroll_id']; ?>] <?php echo htmlspecialchars($en['Lesson_name']); ?><?php echo $suffix; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label>ອັບໂຫຼດ PDF ຄຳຕອບ <span style="color:#e53935">*</span></label>
            <input type="file" name="submit_pdf" accept="application/pdf" required>
            <small style="color:#999;display:block;margin-top:-10px;margin-bottom:14px;">ຮັບສະເພາະໄຟລ໌ .pdf</small>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('submitExamModal')">ຍົກເລີກ</button>
                <button type="submit" name="submit_exam" class="btn-submit">📤 ສົ່ງ</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($is_teacher): ?>
<!-- CREATE LESSON MODAL -->
<div class="modal-overlay" id="lessonModal">
    <div class="modal">
        <h3>➕ ສ້າງບົດຮຽນໃໝ່</h3>
        <form method="POST" action="home.php?class_id=<?php echo $class_id; ?>" enctype="multipart/form-data">
            <label>ຊື່ບົດຮຽນ <span style="color:#e53935">*</span></label>
            <input type="text" name="lesson_name" placeholder="ປ້ອນຊື່ບົດຮຽນ" required>
            <label>ປະເພດບົດຮຽນ</label>
            <select name="l_type_id">
                <option value="">-- ເລືອກປະເພດ --</option>
                <?php foreach ($lesson_types as $lt): ?>
                    <option value="<?php echo $lt['L_Type_id']; ?>"><?php echo htmlspecialchars($lt['L_Type_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <label>ອັບໂຫຼດໄຟລ໌ PDF</label>
            <input type="file" name="lesson_pdf" accept="application/pdf">
            <small style="color:#999;display:block;margin-top:-10px;margin-bottom:14px;">ຮັບສະເພາະໄຟລ໌ .pdf</small>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('lessonModal')">ຍົກເລີກ</button>
                <button type="submit" name="create_lesson" class="btn-submit">💾 ບັນທຶກ</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT LESSON MODAL -->
<div class="modal-overlay" id="editLessonModal">
    <div class="modal">
        <h3>✏️ ແກ້ໄຂບົດຮຽນ</h3>
        <form method="POST" action="home.php?class_id=<?php echo $class_id; ?>" enctype="multipart/form-data">
            <input type="hidden" name="lesson_id" id="edit_lesson_id">
            <input type="hidden" name="old_file_path" id="edit_lesson_old_path">
            <label>ຊື່ບົດຮຽນ <span style="color:#e53935">*</span></label>
            <input type="text" name="lesson_name" id="edit_lesson_name" required>
            <label>ປະເພດບົດຮຽນ</label>
            <select name="l_type_id" id="edit_lesson_type">
                <option value="">-- ເລືອກປະເພດ --</option>
                <?php foreach ($lesson_types as $lt): ?>
                    <option value="<?php echo $lt['L_Type_id']; ?>"><?php echo htmlspecialchars($lt['L_Type_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <label>ແທນທີ່ PDF <small style="color:#999;">(ປ່ອຍຫວ່າງເພື່ອຮັກສາໄຟລ໌ເດີມ)</small></label>
            <input type="file" name="lesson_pdf" accept="application/pdf">
            <small style="color:#999;display:block;margin-top:-10px;margin-bottom:14px;">ຮັບສະເພາະໄຟລ໌ .pdf</small>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('editLessonModal')">ຍົກເລີກ</button>
                <button type="submit" name="update_lesson" class="btn-submit">💾 ອັບເດດ</button>
            </div>
        </form>
    </div>
</div>

<!-- CREATE LESSON TYPE MODAL -->
<div class="modal-overlay" id="lessonTypeModal">
    <div class="modal">
        <h3>🗂️ ສ້າງປະເພດບົດຮຽນ</h3>
        <form method="POST" action="home.php?class_id=<?php echo $class_id; ?>">
            <label>ຊື່ປະເພດບົດຮຽນ <span style="color:#e53935">*</span></label>
            <input type="text" name="l_type_name" placeholder="ເຊັ່ນ: ຄະນິດສາດ, ວິທະຍາສາດ" required>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('lessonTypeModal')">ຍົກເລີກ</button>
                <button type="submit" name="create_lesson_type" class="btn-submit">💾 ບັນທຶກ</button>
            </div>
        </form>
    </div>
</div>

<!-- CREATE EXAM MODAL -->
<div class="modal-overlay" id="examModal">
    <div class="modal">
        <h3>➕ ສ້າງການສອບເສັງໃໝ່</h3>
        <form method="POST" action="home.php?class_id=<?php echo $class_id; ?>" enctype="multipart/form-data">
            <label>ຊື່ການສອບເສັງ <span style="color:#e53935">*</span></label>
            <input type="text" name="exam_name" placeholder="ປ້ອນຊື່ການສອບເສັງ" required>
            <label>ປະເພດການສອບເສັງ</label>
            <select name="e_type_id">
                <option value="">-- ເລືອກປະເພດການສອບເສັງ --</option>
                <?php foreach ($exam_types as $et): ?>
                    <option value="<?php echo $et['E_Type_id']; ?>"><?php echo htmlspecialchars($et['E_Type_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <label>ບົດຮຽນທີ່ກ່ຽວຂ້ອງ</label>
            <select name="lesson_id">
                <option value="">-- ເລືອກບົດຮຽນ --</option>
                <?php foreach ($lessons as $ls): ?>
                    <option value="<?php echo $ls['Lesson_id']; ?>"><?php echo htmlspecialchars($ls['Lesson_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <label>ວັນທີ &amp; ເວລາເລີ່ມ</label>
            <input type="datetime-local" name="start_at">
            <label>ວັນທີ &amp; ເວລາສິ້ນສຸດ</label>
            <input type="datetime-local" name="end_at">
            <label>ອັບໂຫຼດໄຟລ໌ PDF</label>
            <input type="file" name="exam_pdf" accept="application/pdf">
            <small style="color:#999;display:block;margin-top:-10px;margin-bottom:14px;">ຮັບສະເພາະໄຟລ໌ .pdf</small>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('examModal')">ຍົກເລີກ</button>
                <button type="submit" name="create_exam" class="btn-submit">💾 ບັນທຶກ</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT EXAM MODAL -->
<div class="modal-overlay" id="editExamModal">
    <div class="modal">
        <h3>✏️ ແກ້ໄຂການສອບເສັງ</h3>
        <form method="POST" action="home.php?class_id=<?php echo $class_id; ?>" enctype="multipart/form-data">
            <input type="hidden" name="exam_id" id="edit_exam_id">
            <input type="hidden" name="old_file_path" id="edit_exam_old_path">
            <label>ຊື່ການສອບເສັງ <span style="color:#e53935">*</span></label>
            <input type="text" name="exam_name" id="edit_exam_name" required>
            <label>ປະເພດການສອບເສັງ</label>
            <select name="e_type_id" id="edit_exam_type">
                <option value="">-- ເລືອກປະເພດການສອບເສັງ --</option>
                <?php foreach ($exam_types as $et): ?>
                    <option value="<?php echo $et['E_Type_id']; ?>"><?php echo htmlspecialchars($et['E_Type_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <label>ບົດຮຽນທີ່ກ່ຽວຂ້ອງ</label>
            <select name="lesson_id" id="edit_exam_lesson">
                <option value="">-- ເລືອກບົດຮຽນ --</option>
                <?php foreach ($lessons as $ls): ?>
                    <option value="<?php echo $ls['Lesson_id']; ?>"><?php echo htmlspecialchars($ls['Lesson_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <label>ວັນທີ &amp; ເວລາເລີ່ມ</label>
            <input type="datetime-local" name="start_at" id="edit_exam_start_at">
            <label>ວັນທີ &amp; ເວລາສິ້ນສຸດ</label>
            <input type="datetime-local" name="end_at" id="edit_exam_end_at">
            <label>ແທນທີ່ PDF <small style="color:#999;">(ປ່ອຍຫວ່າງເພື່ອຮັກສາໄຟລ໌ເດີມ)</small></label>
            <input type="file" name="exam_pdf" accept="application/pdf">
            <small style="color:#999;display:block;margin-top:-10px;margin-bottom:14px;">ຮັບສະເພາະໄຟລ໌ .pdf</small>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('editExamModal')">ຍົກເລີກ</button>
                <button type="submit" name="update_exam" class="btn-submit">💾 ອັບເດດ</button>
            </div>
        </form>
    </div>
</div>

<!-- CREATE EXAM TYPE MODAL -->
<div class="modal-overlay" id="examTypeModal">
    <div class="modal">
        <h3>🗂️ ສ້າງປະເພດການສອບເສັງ</h3>
        <form method="POST" action="home.php?class_id=<?php echo $class_id; ?>">
            <label>ຊື່ປະເພດການສອບເສັງ <span style="color:#e53935">*</span></label>
            <input type="text" name="e_type_name" placeholder="ເຊັ່ນ: ກາງສົກ, ປາຍສົກ" required>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('examTypeModal')">ຍົກເລີກ</button>
                <button type="submit" name="create_exam_type" class="btn-submit">💾 ບັນທຶກ</button>
            </div>
        </form>
    </div>
</div>

<!-- GIVE SCORE MODAL -->
<div class="modal-overlay" id="giveScoreModal">
    <div class="modal">
        <h3>🏆 ໃຫ້ຄະແນນ</h3>
        <form method="POST" action="home.php?class_id=<?php echo $class_id; ?>">
            <input type="hidden" name="submit_id" id="score_submit_id">
            <div class="info-row">
                <div class="info-box"><span>👤 ນັກຮຽນ</span><strong id="score_student_name">—</strong></div>
                <div class="info-box"><span>📝 ການສອບເສັງ</span><strong id="score_exam_name">—</strong></div>
            </div>
            <label>ຄະແນນ (0 – 100) <span style="color:#e53935">*</span></label>
            <input type="number" name="score" id="score_input" min="0" max="100" placeholder="ປ້ອນຄະແນນ" required>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('giveScoreModal')">ຍົກເລີກ</button>
                <button type="submit" name="give_score" class="btn-submit">💾 ບັນທຶກຄະແນນ</button>
            </div>
        </form>
    </div>
</div>

<!-- CONFIRM DELETE (Lesson / Exam) -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <h4>🗑️ ຢືນຢັນການລຶບ</h4>
        <p>ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລຶບລາຍການນີ້?<br>ການດຳເນີນການນີ້ບໍ່ສາມາດຍ້ອນກັບໄດ້.</p>
        <form method="POST" action="home.php?class_id=<?php echo $class_id; ?>" id="deleteForm">
            <div class="confirm-actions">
                <button type="button" class="btn-cancel" onclick="closeConfirm()">ຍົກເລີກ</button>
                <button type="submit" class="btn-delete">🗑️ ລຶບ</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    function showTab(tabId, btn) {
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        btn.classList.add('active');
    }
    function openModal(id)  { document.getElementById(id).classList.add('open'); }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }

    function updateLessonId(val) {
        document.getElementById('lesson_id_display').textContent = val ? val : '—';
    }

    function openEditEnroll(enrollId, lessonId, status) {
        document.getElementById('edit_enroll_id').value     = enrollId;
        document.getElementById('edit_enroll_lesson').value = lessonId;
        document.getElementById('edit_enroll_status').value = status;
        openModal('editEnrollModal');
    }
    function confirmDeleteEnroll(enrollId) {
        document.getElementById('delete_enroll_id').value = enrollId;
        document.getElementById('confirmEnrollOverlay').classList.add('open');
    }
    function closeConfirmEnroll() {
        document.getElementById('confirmEnrollOverlay').classList.remove('open');
    }

    function confirmDeleteEnrollTeacher(enrollId) {
    if (confirm('ທ່ານແນ່ໃຈບໍ່ວ່າຕ້ອງການລຶບການລົງທະບຽນນີ້?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'home.php?class_id=<?php echo $class_id; ?>';
        const input1 = document.createElement('input');
        input1.type = 'hidden'; input1.name = 'enroll_id'; input1.value = enrollId;
        const input2 = document.createElement('input');
        input2.type = 'hidden'; input2.name = 'delete_enrollment_teacher'; input2.value = '1';
        form.appendChild(input1);
        form.appendChild(input2);
        document.body.appendChild(form);
        form.submit();
    }
}
    <?php if ($is_teacher): ?>
    function openEditLesson(id, name, typeId, filePath) {
        document.getElementById('edit_lesson_id').value       = id;
        document.getElementById('edit_lesson_name').value     = name;
        document.getElementById('edit_lesson_old_path').value = filePath;
        document.getElementById('edit_lesson_type').value     = typeId;
        openModal('editLessonModal');
    }
    function openEditExam(id, name, typeId, lessonId, filePath, startAt, endAt) {
        document.getElementById('edit_exam_id').value       = id;
        document.getElementById('edit_exam_name').value     = name;
        document.getElementById('edit_exam_old_path').value = filePath;
        document.getElementById('edit_exam_type').value     = typeId;
        document.getElementById('edit_exam_lesson').value   = lessonId;
        document.getElementById('edit_exam_start_at').value = startAt ? startAt.replace(' ', 'T').substring(0, 16) : '';
        document.getElementById('edit_exam_end_at').value   = endAt   ? endAt.replace(' ', 'T').substring(0, 16)   : '';
        openModal('editExamModal');
    }
    function confirmDeleteItem(type, id) {
        const form = document.getElementById('deleteForm');
        form.querySelectorAll('input[type="hidden"]').forEach(el => el.remove());
        const hiddenType = document.createElement('input');
        hiddenType.type  = 'hidden';
        hiddenType.name  = type === 'lesson' ? 'delete_lesson' : 'delete_exam';
        hiddenType.value = '1';
        const hiddenId   = document.createElement('input');
        hiddenId.type    = 'hidden';
        hiddenId.name    = type === 'lesson' ? 'lesson_id' : 'exam_id';
        hiddenId.value   = id;
        form.appendChild(hiddenType);
        form.appendChild(hiddenId);
        document.getElementById('confirmOverlay').classList.add('open');
    }
    function closeConfirm() {
        document.getElementById('confirmOverlay').classList.remove('open');
    }
    function openGiveScore(submitId, studentName, examName, currentScore) {
        document.getElementById('score_submit_id').value          = submitId;
        document.getElementById('score_student_name').textContent = studentName;
        document.getElementById('score_exam_name').textContent    = examName;
        document.getElementById('score_input').value              = currentScore || '';
        openModal('giveScoreModal');
    }
    <?php endif; ?>

    // Auto-activate tab from URL
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab');
    if (activeTab) {
        const tabBtn = document.querySelector(`.tab-btn[onclick*="${activeTab}"]`);
        if (tabBtn) showTab(activeTab, tabBtn);
    }

    // Auto-dismiss alerts after 3s
    ['successAlert', 'errorAlert'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            setTimeout(() => {
                el.style.transition = 'opacity 0.5s ease';
                el.style.opacity    = '0';
                setTimeout(() => el.remove(), 500);
            }, 3000);
        }
    });

    // Clean success/error from URL
    if (urlParams.has('success') || urlParams.has('error')) {
        const cleanParams = new URLSearchParams(window.location.search);
        cleanParams.delete('success');
        cleanParams.delete('error');
        const newUrl = window.location.pathname + (cleanParams.toString() ? '?' + cleanParams.toString() : '');
        window.history.replaceState({}, document.title, newUrl);
    }

    // Re-open modal on error redirect
    <?php if ($is_teacher && isset($_GET['error'])): ?>
        <?php if (isset($_GET['tab']) && $_GET['tab'] === 'exams'): ?>
            openModal('examModal');
        <?php elseif (! isset($_GET['tab']) || $_GET['tab'] === 'submit_exam'): ?>
            // stay on tab only
        <?php else: ?>
            openModal('lessonModal');
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($is_student && isset($_GET['tab']) && $_GET['tab'] === 'enrollment' && isset($_GET['error'])): ?>
        openModal('enrollModal');
    <?php endif; ?>

    <?php if ($is_student && isset($_GET['tab']) && $_GET['tab'] === 'submit_exam' && isset($_GET['error'])): ?>
        openModal('submitExamModal');
    <?php endif; ?>
</script>
</body>
</html>