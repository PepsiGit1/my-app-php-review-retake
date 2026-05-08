<?php
    session_start();

    if (! isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
    }
    $conn = new mysqli("localhost", "root", "", "reviewAndRetake");
    if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action'] ?? '';
    $message     = '';
    $messageType = '';

    // ── TEACHER ──
    if ($action === 'add_teacher') {
        $name     = trim($_POST['Teacher_fullname']);
        $email    = trim($_POST['Teacher_email']);
        $password = trim($_POST['Teacher_password']);
        $stmt     = $conn->prepare("INSERT INTO tb_Teacher (Teacher_fullname, Email, Password) VALUES (?,?,?)");
        $stmt->bind_param("sss", $name, $email, $password);
        $stmt->execute() ? ($message = "Teacher added!") && ($messageType = 'success')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();

    } elseif ($action === 'edit_teacher') {
        $name  = trim($_POST['Teacher_fullname']);
        $email = trim($_POST['Teacher_email']);
        if (! empty($_POST['Teacher_password'])) {
            $password = trim($_POST['Teacher_password']);
            $stmt     = $conn->prepare("UPDATE tb_Teacher SET Teacher_fullname=?, Email=?, Password=? WHERE Teacher_id=?");
            $stmt->bind_param("sssi", $name, $email, $password, $_POST['Teacher_id']);
        } else {
            $stmt = $conn->prepare("UPDATE tb_Teacher SET Teacher_fullname=?, Email=? WHERE Teacher_id=?");
            $stmt->bind_param("ssi", $name, $email, $_POST['Teacher_id']);
        }
        $stmt->execute() ? ($message = "Teacher updated!") && ($messageType = 'success')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();

    } elseif ($action === 'delete_teacher') {
        $stmt = $conn->prepare("DELETE FROM tb_Teacher WHERE Teacher_id=?");
        $stmt->bind_param("i", $_POST['Teacher_id']);
        $stmt->execute() ? ($message = "Teacher deleted.") && ($messageType = 'warning')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();

        // ── STUDENT ──
    } elseif ($action === 'add_student') {
        $name     = trim($_POST['Student_full_name']);
        $email    = trim($_POST['Student_email']);
        $password = trim($_POST['Student_password']);
        $stmt     = $conn->prepare("INSERT INTO tb_Student (Student_full_name, Email, Password) VALUES (?,?,?)");
        $stmt->bind_param("sss", $name, $email, $password);
        $stmt->execute() ? ($message = "Student added!") && ($messageType = 'success')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();

    } elseif ($action === 'edit_student') {
        $name  = trim($_POST['Student_full_name']);
        $email = trim($_POST['Student_email']);
        if (! empty($_POST['Student_password'])) {
            $password = trim($_POST['Student_password']);
            $stmt     = $conn->prepare("UPDATE tb_Student SET Student_full_name=?, Email=?, Password=? WHERE Student_id=?");
            $stmt->bind_param("sssi", $name, $email, $password, $_POST['Student_id']);
        } else {
            $stmt = $conn->prepare("UPDATE tb_Student SET Student_full_name=?, Email=? WHERE Student_id=?");
            $stmt->bind_param("ssi", $name, $email, $_POST['Student_id']);
        }
        $stmt->execute() ? ($message = "Student updated!") && ($messageType = 'success')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();

    } elseif ($action === 'delete_student') {
        $stmt = $conn->prepare("DELETE FROM tb_Student WHERE Student_id=?");
        $stmt->bind_param("i", $_POST['Student_id']);
        $stmt->execute() ? ($message = "Student deleted.") && ($messageType = 'warning')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();

        // ── ROOM ──
    } elseif ($action === 'add_room') {
        $stmt = $conn->prepare("INSERT INTO tb_Room (Room_name) VALUES (?)");
        $stmt->bind_param("s", $_POST['Room_name']);
        $stmt->execute() ? ($message = "Room created!") && ($messageType = 'success')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();

    } elseif ($action === 'edit_room') {
        $stmt = $conn->prepare("UPDATE tb_Room SET Room_name=? WHERE Room_id=?");
        $stmt->bind_param("si", $_POST['Room_name'], $_POST['Room_id']);
        $stmt->execute() ? ($message = "Room updated!") && ($messageType = 'success')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();

    } elseif ($action === 'delete_room') {
        $stmt = $conn->prepare("DELETE FROM tb_Room WHERE Room_id=?");
        $stmt->bind_param("i", $_POST['Room_id']);
        $stmt->execute() ? ($message = "Room deleted.") && ($messageType = 'warning')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();

        // ── LESSON TYPE ──
    } elseif ($action === 'add_lesson_type') {
        $stmt = $conn->prepare("INSERT INTO tb_Lesson_Type (L_Type_name) VALUES (?)");
        $stmt->bind_param("s", $_POST['L_Type_name']);
        $stmt->execute() ? ($message = "Lesson Type added!") && ($messageType = 'success')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();

    } elseif ($action === 'delete_lesson_type') {
        $stmt = $conn->prepare("DELETE FROM tb_Lesson_Type WHERE L_Type_id=?");
        $stmt->bind_param("i", $_POST['L_Type_id']);
        $stmt->execute() ? ($message = "Lesson Type deleted.") && ($messageType = 'warning')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();

        // ── LESSON ──
    } elseif ($action === 'add_lesson') {
        $lesson_name = trim($_POST['Lesson_name']);
        $l_type_id   = ! empty($_POST['L_Type_id']) ? $_POST['L_Type_id'] : null;
        $room_id     = ! empty($_POST['Room_id']) ? $_POST['Room_id'] : null;
        $teacher_id  = ! empty($_POST['Teacher_id']) ? $_POST['Teacher_id'] : null;
        $file_path   = '';

        if (isset($_FILES['lesson_pdf']) && $_FILES['lesson_pdf']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . "/uploads/lessons/";
            if (! is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $ext  = strtolower(pathinfo($_FILES['lesson_pdf']['name'], PATHINFO_EXTENSION));
            $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $_FILES['lesson_pdf']['tmp_name']);
            if ($ext === 'pdf' && $mime === 'application/pdf') {
                $fname = substr(md5(uniqid()), 0, 12) . ".pdf";
                if (move_uploaded_file($_FILES['lesson_pdf']['tmp_name'], $upload_dir . $fname)) {
                    $file_path = "uploads/lessons/" . $fname;
                }
            } else {
                $message     = "Only PDF allowed.";
                $messageType = 'danger';
            }
        }

        if ($messageType !== 'danger') {
            $stmt = $conn->prepare("INSERT INTO tb_Lesson (Lesson_name, File_Path, L_Type_id, Room_id, Teacher_id) VALUES (?,?,?,?,?)");
            $stmt->bind_param("sssss", $lesson_name, $file_path, $l_type_id, $room_id, $teacher_id);
            $stmt->execute() ? ($message = "Lesson created!") && ($messageType = 'success')
                : ($message = $stmt->error) && ($messageType = 'danger');
            $stmt->close();
        }

    } elseif ($action === 'edit_lesson') {
        $lesson_name = trim($_POST['Lesson_name']);
        $l_type_id   = ! empty($_POST['L_Type_id']) ? $_POST['L_Type_id'] : null;
        $room_id     = ! empty($_POST['Room_id']) ? $_POST['Room_id'] : null;
        $teacher_id  = ! empty($_POST['Teacher_id']) ? $_POST['Teacher_id'] : null;
        $lesson_id   = intval($_POST['Lesson_id']);
        $file_path   = trim($_POST['old_file_path']);

        if (isset($_FILES['lesson_pdf']) && $_FILES['lesson_pdf']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . "/uploads/lessons/";
            if (! is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $ext  = strtolower(pathinfo($_FILES['lesson_pdf']['name'], PATHINFO_EXTENSION));
            $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $_FILES['lesson_pdf']['tmp_name']);
            if ($ext === 'pdf' && $mime === 'application/pdf') {
                $fname = substr(md5(uniqid()), 0, 12) . ".pdf";
                if (move_uploaded_file($_FILES['lesson_pdf']['tmp_name'], $upload_dir . $fname)) {
                    if ($file_path && file_exists(__DIR__ . "/" . $file_path)) {
                        unlink(__DIR__ . "/" . $file_path);
                    }

                    $file_path = "uploads/lessons/" . $fname;
                }
            }
        }

        $stmt = $conn->prepare("UPDATE tb_Lesson SET Lesson_name=?, File_Path=?, L_Type_id=?, Room_id=?, Teacher_id=? WHERE Lesson_id=?");
        $stmt->bind_param("sssssi", $lesson_name, $file_path, $l_type_id, $room_id, $teacher_id, $lesson_id);
        $stmt->execute() ? ($message = "Lesson updated!") && ($messageType = 'success')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();

    } elseif ($action === 'delete_lesson') {
        $lesson_id = intval($_POST['Lesson_id']);
        $r         = $conn->query("SELECT File_Path FROM tb_Lesson WHERE Lesson_id=$lesson_id");
        if ($row = $r->fetch_assoc()) {
            if ($row['File_Path'] && file_exists(__DIR__ . "/" . $row['File_Path'])) {
                unlink(__DIR__ . "/" . $row['File_Path']);
            }

        }
        $stmt = $conn->prepare("DELETE FROM tb_Lesson WHERE Lesson_id=?");
        $stmt->bind_param("i", $lesson_id);
        $stmt->execute() ? ($message = "Lesson deleted.") && ($messageType = 'warning')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();

        // ── EXAM TYPE ──
    } elseif ($action === 'add_exam_type') {
        $stmt = $conn->prepare("INSERT INTO tb_Exam_Type (E_Type_name) VALUES (?)");
        $stmt->bind_param("s", $_POST['E_Type_name']);
        $stmt->execute() ? ($message = "Exam Type added!") && ($messageType = 'success')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();

    } elseif ($action === 'delete_exam_type') {
        $stmt = $conn->prepare("DELETE FROM tb_Exam_Type WHERE E_Type_id=?");
        $stmt->bind_param("i", $_POST['E_Type_id']);
        $stmt->execute() ? ($message = "Exam Type deleted.") && ($messageType = 'warning')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();

        // ── EXAM ──
    } elseif ($action === 'add_exam') {
        $exam_name = trim($_POST['Exam_name']);
        $e_type_id = ! empty($_POST['E_Type_id']) ? $_POST['E_Type_id'] : null;
        $lesson_id = ! empty($_POST['Lesson_id']) ? $_POST['Lesson_id'] : null;
        $room_id   = ! empty($_POST['Room_id']) ? $_POST['Room_id'] : null;
        $start_at  = ! empty($_POST['start_at']) ? $_POST['start_at'] : null;
        $end_at    = ! empty($_POST['end_at']) ? $_POST['end_at'] : null;
        $file_path = '';

        if (isset($_FILES['exam_pdf']) && $_FILES['exam_pdf']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . "/uploads/exams/";
            if (! is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $ext  = strtolower(pathinfo($_FILES['exam_pdf']['name'], PATHINFO_EXTENSION));
            $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $_FILES['exam_pdf']['tmp_name']);
            if ($ext === 'pdf' && $mime === 'application/pdf') {
                $fname = substr(md5(uniqid()), 0, 12) . ".pdf";
                if (move_uploaded_file($_FILES['exam_pdf']['tmp_name'], $upload_dir . $fname)) {
                    $file_path = "uploads/exams/" . $fname;
                }
            } else {
                $message     = "Only PDF allowed.";
                $messageType = 'danger';
            }
        }

        if ($messageType !== 'danger') {
            $stmt = $conn->prepare("INSERT INTO tb_Exam (Exam_name, File_Path, Lesson_id, E_Type_id, Room_id, start_at, end_at) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssss", $exam_name, $file_path, $lesson_id, $e_type_id, $room_id, $start_at, $end_at);
            $stmt->execute() ? ($message = "Exam created!") && ($messageType = 'success')
                : ($message = $stmt->error) && ($messageType = 'danger');
            $stmt->close();
        }

    } elseif ($action === 'edit_exam') {
        $exam_name = trim($_POST['Exam_name']);
        $e_type_id = ! empty($_POST['E_Type_id']) ? $_POST['E_Type_id'] : null;
        $lesson_id = ! empty($_POST['Lesson_id']) ? $_POST['Lesson_id'] : null;
        $room_id   = ! empty($_POST['Room_id']) ? $_POST['Room_id'] : null;
        $exam_id   = intval($_POST['Exam_id']);
        $file_path = trim($_POST['old_file_path']);
        $start_at  = ! empty($_POST['start_at']) ? $_POST['start_at'] : null;
        $end_at    = ! empty($_POST['end_at']) ? $_POST['end_at'] : null;

        if (isset($_FILES['exam_pdf']) && $_FILES['exam_pdf']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . "/uploads/exams/";
            if (! is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $ext  = strtolower(pathinfo($_FILES['exam_pdf']['name'], PATHINFO_EXTENSION));
            $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $_FILES['exam_pdf']['tmp_name']);
            if ($ext === 'pdf' && $mime === 'application/pdf') {
                $fname = substr(md5(uniqid()), 0, 12) . ".pdf";
                if (move_uploaded_file($_FILES['exam_pdf']['tmp_name'], $upload_dir . $fname)) {
                    if ($file_path && file_exists(__DIR__ . "/" . $file_path)) {
                        unlink(__DIR__ . "/" . $file_path);
                    }

                    $file_path = "uploads/exams/" . $fname;
                }
            }
        }

        $stmt = $conn->prepare("UPDATE tb_Exam SET Exam_name=?, File_Path=?, Lesson_id=?, E_Type_id=?, Room_id=?, start_at=?, end_at=? WHERE Exam_id=?");
        $stmt->bind_param("sssssssi", $exam_name, $file_path, $lesson_id, $e_type_id, $room_id, $start_at, $end_at, $exam_id);
        $stmt->execute() ? ($message = "Exam updated!") && ($messageType = 'success')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();

    } elseif ($action === 'delete_exam') {
        $exam_id = intval($_POST['Exam_id']);
        $r       = $conn->query("SELECT File_Path FROM tb_Exam WHERE Exam_id=$exam_id");
        if ($row = $r->fetch_assoc()) {
            if ($row['File_Path'] && file_exists(__DIR__ . "/" . $row['File_Path'])) {
                unlink(__DIR__ . "/" . $row['File_Path']);
            }

        }
        $stmt = $conn->prepare("DELETE FROM tb_Exam WHERE Exam_id=?");
        $stmt->bind_param("i", $exam_id);
        $stmt->execute() ? ($message = "Exam deleted.") && ($messageType = 'warning')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();

        // ── ENROLLMENT ──
    } elseif ($action === 'add_enrollment') {
        $student_id = intval($_POST['Student_id']);
        $lesson_id  = intval($_POST['Lesson_id']);
        $room_id    = ! empty($_POST['Room_id']) ? intval($_POST['Room_id']) : null;
        $stmt       = $conn->prepare("INSERT INTO tb_Enrollment (Student_id, Lesson_id, Room_id) VALUES (?,?,?)");
        $stmt->bind_param("iii", $student_id, $lesson_id, $room_id);
        $stmt->execute() ? ($message = "Enrollment created!") && ($messageType = 'success')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();

    } elseif ($action === 'edit_enrollment') {
        $enroll_id  = intval($_POST['Enroll_id']);
        $student_id = intval($_POST['Student_id']);
        $lesson_id  = intval($_POST['Lesson_id']);
        $room_id    = ! empty($_POST['Room_id']) ? intval($_POST['Room_id']) : null;
        $stmt       = $conn->prepare("UPDATE tb_Enrollment SET Student_id=?, Lesson_id=?, Room_id=? WHERE Enroll_id=?");
        $stmt->bind_param("iiii", $student_id, $lesson_id, $room_id, $enroll_id);
        $stmt->execute() ? ($message = "Enrollment updated!") && ($messageType = 'success')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();

    } elseif ($action === 'delete_enrollment') {
        $stmt = $conn->prepare("DELETE FROM tb_Enrollment WHERE Enroll_id=?");
        $stmt->bind_param("i", $_POST['Enroll_id']);
        $stmt->execute() ? ($message = "Enrollment deleted.") && ($messageType = 'warning')
            : ($message = $stmt->error) && ($messageType = 'danger');
        $stmt->close();
    }

    $tab = $_GET['tab'] ?? 'dashboard';
    $conn->close();
    header("Location: admin.php?tab=" . urlencode($tab) . "&msg=" . urlencode($message) . "&type=" . urlencode($messageType));
    exit;
    }

    $message     = $_GET['msg'] ?? '';
    $messageType = $_GET['type'] ?? '';

    // ── FETCH DATA ──
    $result   = $conn->query("SELECT Teacher_id, Teacher_fullname, Email FROM tb_Teacher ORDER BY Teacher_id DESC");
    $teachers = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    $result   = $conn->query("SELECT Student_id, Student_full_name, Email FROM tb_Student ORDER BY Student_id DESC");
    $students = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    $result = $conn->query("SELECT Room_id, Room_name FROM tb_Room ORDER BY Room_id DESC");
    $rooms  = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    $result       = $conn->query("SELECT L_Type_id, L_Type_name FROM tb_Lesson_Type ORDER BY L_Type_id DESC");
    $lesson_types = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    $result     = $conn->query("SELECT E_Type_id, E_Type_name FROM tb_Exam_Type ORDER BY E_Type_id DESC");
    $exam_types = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    $result  = $conn->query("SELECT l.Lesson_id, l.Lesson_name, l.File_Path, l.L_Type_id, l.Room_id, l.Teacher_id, lt.L_Type_name, r.Room_name, t.Teacher_fullname FROM tb_Lesson l LEFT JOIN tb_Lesson_Type lt ON l.L_Type_id = lt.L_Type_id LEFT JOIN tb_Room r ON l.Room_id = r.Room_id LEFT JOIN tb_Teacher t ON l.Teacher_id = t.Teacher_id ORDER BY l.Lesson_id DESC");
    $lessons = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    $result = $conn->query("SELECT e.Exam_id, e.Exam_name, e.File_Path, e.E_Type_id, e.Room_id, e.Lesson_id, e.start_at, e.end_at, et.E_Type_name, r.Room_name, l.Lesson_name FROM tb_Exam e LEFT JOIN tb_Exam_Type et ON e.E_Type_id = et.E_Type_id LEFT JOIN tb_Room r ON e.Room_id = r.Room_id LEFT JOIN tb_Lesson l ON e.Lesson_id = l.Lesson_id ORDER BY e.Exam_id DESC");
    $exams  = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    $result = $conn->query("SELECT en.Enroll_id, en.Student_id, en.Lesson_id, en.Room_id, s.Student_full_name, l.Lesson_name, r.Room_name FROM tb_Enrollment en LEFT JOIN tb_Student s ON en.Student_id = s.Student_id LEFT JOIN tb_Lesson l ON en.Lesson_id = l.Lesson_id LEFT JOIN tb_Room r ON en.Room_id = r.Room_id ORDER BY en.Enroll_id DESC");
    if (! $result) {
    die("Enrollment query error: " . $conn->error);
    }

    $enrollments = $result->fetch_all(MYSQLI_ASSOC);

    $activeTab = $_GET['tab'] ?? 'dashboard';
    $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel – Review & Retake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --blue-dark:   #1a73e8;
            --blue-main:   #1a3a8f;
            --blue-mid:    #2563eb;
            --blue-light:  #3b82f6;
            --blue-pale:   #eff6ff;
            --blue-border: #bfdbfe;
            --accent:      #60a5fa;
        }
        * { box-sizing: border-box; }
        body { background: #f0f5ff; font-family: 'Segoe UI', sans-serif; margin: 0; }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(160deg, var(--blue-dark) 0%, var(--blue-main) 100%);
            width: 255px; position: fixed; top: 0; left: 0;
            z-index: 100; overflow-y: auto;
            box-shadow: 4px 0 20px rgba(13,27,75,0.3);
        }
        .sidebar .brand { font-size: 1rem; font-weight: 700; padding: 22px 20px 18px; border-bottom: 1px solid rgba(255,255,255,0.12); display: flex; align-items: center; gap: 10px; color: #fff; }
        .sidebar .brand i { font-size: 1.3rem; color: var(--accent); }
        .sidebar .nav-section { font-size: 9px; text-transform: uppercase; letter-spacing: 1.5px; color: rgba(255,255,255,0.35); padding: 18px 20px 5px; }
        .sidebar .nav-link { color: rgba(255,255,255,0.72); padding: 10px 20px; display: flex; align-items: center; gap: 10px; font-size: 13.5px; transition: all .18s; text-decoration: none; }
        .sidebar .nav-link i { font-size: 15px; }
        .sidebar .nav-link:hover { background: rgba(255,255,255,0.1); color: #fff; padding-left: 26px; }
        .sidebar .nav-link.active { background: rgba(96,165,250,0.22); color: #fff; font-weight: 600; border-right: 3px solid var(--accent); }
        .main-content { margin-left: 255px; padding: 30px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 2px solid var(--blue-border); }
        .page-header h4 { font-weight: 700; color: var(--blue-dark); display: flex; align-items: center; gap: 10px; margin: 0; }
        .page-header h4 i { color: var(--blue-mid); }
        .stat-card { border-radius: 16px; padding: 22px 24px; color: #fff; position: relative; overflow: hidden; box-shadow: 0 6px 20px rgba(37,99,235,0.2); transition: transform .2s; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card::after { content: ''; position: absolute; width: 90px; height: 90px; border-radius: 50%; background: rgba(255,255,255,0.1); right: -20px; bottom: -20px; }
        .stat-card .stat-num { font-size: 2.4rem; font-weight: 800; line-height: 1; }
        .stat-card .stat-label { font-size: 13px; margin-top: 6px; opacity: .9; }
        .stat-card .stat-icon { position: absolute; right: 20px; top: 18px; font-size: 2rem; opacity: .25; }
        .table-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(37,99,235,0.08); overflow: hidden; border: 1px solid var(--blue-border); }
        .modern-table { margin: 0; width: 100%; border-collapse: separate; border-spacing: 0; }
        .modern-table thead tr { background: linear-gradient(90deg, var(--blue-dark), var(--blue-mid)); }
        .modern-table thead th { color: #fff; font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .8px; padding: 14px 16px; border: none; white-space: nowrap; }
        .modern-table tbody tr { border-bottom: 1px solid #e8f0fe; transition: background .15s; }
        .modern-table tbody tr:last-child { border-bottom: none; }
        .modern-table tbody tr:hover { background: var(--blue-pale); }
        .modern-table tbody td { padding: 13px 16px; vertical-align: middle; color: #1e293b; font-size: 14px; border: none; }
        .modern-table .empty-row td { text-align: center; color: #94a3b8; padding: 50px 16px; }
        .id-badge { background: var(--blue-pale); color: var(--blue-mid); border: 1px solid var(--blue-border); font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px; }
        .pdf-badge { background: #dc2626; color: #fff; font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 3px; }
        .pdf-badge:hover { background: #b91c1c; color: #fff; }
        .table-actions { display: flex; gap: 6px; align-items: center; flex-wrap: nowrap; }
        .table-actions form { margin: 0; }
        .btn-edit { background: var(--blue-pale); border: 1px solid var(--blue-border); color: var(--blue-mid); border-radius: 8px; padding: 5px 10px; font-size: 13px; cursor: pointer; transition: all .15s; }
        .btn-edit:hover { background: var(--blue-mid); color: #fff; border-color: var(--blue-mid); }
        .btn-del { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; border-radius: 8px; padding: 5px 10px; font-size: 13px; cursor: pointer; transition: all .15s; }
        .btn-del:hover { background: #dc2626; color: #fff; border-color: #dc2626; }
        .btn-add { background: linear-gradient(135deg, var(--blue-mid), var(--blue-light)); color: #fff; border: none; padding: 9px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 600; box-shadow: 0 4px 12px rgba(37,99,235,0.35); display: inline-flex; align-items: center; gap: 6px; transition: all .2s; cursor: pointer; text-decoration: none; }
        .btn-add:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(37,99,235,0.45); color: #fff; }
        .alert { border-radius: 12px; border: none; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .alert-success { background: #ecfdf5; color: #065f46; border-left: 4px solid #10b981; }
        .alert-warning { background: #fffbeb; color: #92400e; border-left: 4px solid #f59e0b; }
        .alert-danger  { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; }
        .modal-content { border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
        .modal-header { background: linear-gradient(90deg, var(--blue-main), var(--blue-mid)); color: #fff; border-radius: 16px 16px 0 0; padding: 18px 24px; border-bottom: none; }
        .modal-header .modal-title { font-weight: 700; font-size: 15px; display: flex; align-items: center; gap: 8px; }
        .modal-header .btn-close { filter: invert(1) brightness(2); }
        .modal-body { padding: 24px; }
        .modal-footer { border-top: 1px solid #e2e8f0; padding: 16px 24px; border-radius: 0 0 16px 16px; gap: 10px; }
        .form-label { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-control, .form-select { border: 1.5px solid #d1d5db; border-radius: 9px; font-size: 14px; padding: 9px 12px; transition: border-color .15s, box-shadow .15s; }
        .form-control:focus, .form-select:focus { border-color: var(--blue-light); box-shadow: 0 0 0 3px rgba(59,130,246,0.15); outline: none; }
        .btn-modal-add { background: linear-gradient(135deg, var(--blue-mid), var(--blue-light)); color: #fff; border: none; border-radius: 9px; padding: 9px 22px; font-weight: 600; font-size: 14px; transition: opacity .2s; }
        .btn-modal-add:hover { opacity: .9; color: #fff; }
        .btn-modal-cancel { background: #f1f5f9; color: #64748b; border: 1.5px solid #e2e8f0; border-radius: 9px; padding: 9px 18px; font-size: 14px; }
        .btn-modal-cancel:hover { background: #e2e8f0; color: #475569; }
         .btn-logout {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            border: 1.5px solid rgba(255, 255, 255, 0.35);
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.25s ease;
            cursor: pointer;
            backdrop-filter: blur(4px);
            letter-spacing: 0.3px;
        }
        .btn-logout:hover {
            background: rgba(255, 255, 255, 0.28);
            border-color: rgba(255, 255, 255, 0.6);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .btn-logout i {
            font-size: 15px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="brand"><i class="bi bi-shield-check"></i> Admin Panel</div>
    <nav class="nav flex-column mt-1">
        <span class="nav-section">Overview</span>
        <a href="?tab=dashboard"    class="nav-link <?php echo $activeTab === 'dashboard' ? 'active' : '' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <span class="nav-section">People</span>
        <a href="?tab=teachers"     class="nav-link <?php echo $activeTab === 'teachers' ? 'active' : '' ?>"><i class="bi bi-person-badge"></i> Teachers</a>
        <a href="?tab=students"     class="nav-link <?php echo $activeTab === 'students' ? 'active' : '' ?>"><i class="bi bi-people"></i> Students</a>
        <span class="nav-section">Rooms</span>
        <a href="?tab=rooms"        class="nav-link <?php echo $activeTab === 'rooms' ? 'active' : '' ?>"><i class="bi bi-building"></i> Rooms</a>
        <span class="nav-section">Content</span>
        <a href="?tab=lessons"      class="nav-link <?php echo $activeTab === 'lessons' ? 'active' : '' ?>"><i class="bi bi-book"></i> Lessons</a>
        <a href="?tab=lesson_types" class="nav-link <?php echo $activeTab === 'lesson_types' ? 'active' : '' ?>"><i class="bi bi-tags"></i> Lesson Types</a>
        <a href="?tab=exams"        class="nav-link <?php echo $activeTab === 'exams' ? 'active' : '' ?>"><i class="bi bi-file-earmark-text"></i> Exams</a>
        <a href="?tab=exam_types"   class="nav-link <?php echo $activeTab === 'exam_types' ? 'active' : '' ?>"><i class="bi bi-tags"></i> Exam Types</a>
        <a href="?tab=enrollments"  class="nav-link <?php echo $activeTab === 'enrollments' ? 'active' : '' ?>"><i class="bi bi-person-check"></i> Enrollments</a>
        <a href="admin_login.php" class="btn-logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>
</div>

<div class="main-content">

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle-fill' : ($messageType === 'warning' ? 'exclamation-triangle-fill' : 'x-circle-fill') ?>"></i>
        <span><?php echo htmlspecialchars($message) ?></span>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($activeTab === 'dashboard'): ?>
    <div class="page-header">
        <h4><i class="bi bi-speedometer2"></i> Dashboard</h4>
    </div>
    <div class="row g-3">
        <?php $stats = [
                ['label' => 'Teachers', 'count' => count($teachers), 'from' => '#1d4ed8', 'to' => '#60a5fa', 'icon' => 'person-badge'],
                ['label' => 'Students', 'count' => count($students), 'from' => '#065f46', 'to' => '#34d399', 'icon' => 'people'],
                ['label' => 'Rooms', 'count' => count($rooms), 'from' => '#92400e', 'to' => '#fbbf24', 'icon' => 'building'],
                ['label' => 'Lessons', 'count' => count($lessons), 'from' => '#1e3a8a', 'to' => '#38bdf8', 'icon' => 'book'],
                ['label' => 'Exams', 'count' => count($exams), 'from' => '#7f1d1d', 'to' => '#f87171', 'icon' => 'file-earmark-text'],
                ['label' => 'Lesson Types', 'count' => count($lesson_types), 'from' => '#4c1d95', 'to' => '#c084fc', 'icon' => 'tags'],
                ['label' => 'Exam Types', 'count' => count($exam_types), 'from' => '#0c4a6e', 'to' => '#22d3ee', 'icon' => 'patch-question'],
                ['label' => 'Enrollments', 'count' => count($enrollments), 'from' => '#134e4a', 'to' => '#2dd4bf', 'icon' => 'person-check'],
            ];
        foreach ($stats as $s): ?>
        <div class="col-xl-3 col-md-4 col-sm-6">
            <div class="stat-card" style="background:linear-gradient(135deg,<?php echo $s['from'] ?>,<?php echo $s['to'] ?>)">
                <i class="bi bi-<?php echo $s['icon'] ?> stat-icon"></i>
                <div class="stat-num"><?php echo $s['count'] ?></div>
                <div class="stat-label"><i class="bi bi-<?php echo $s['icon'] ?>"></i> <?php echo $s['label'] ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php elseif ($activeTab === 'teachers'): ?>
    <div class="page-header">
        <h4><i class="bi bi-person-badge"></i> Teachers</h4>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addTeacherModal"><i class="bi bi-plus-lg"></i> Add Teacher</button>
    </div>
    <div class="table-card">
        <table class="modern-table">
            <thead><tr><th>#</th><th>ID</th><th>Full Name</th><th>Email</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($teachers): foreach ($teachers as $i => $t): ?>
            <tr>
                <td><?php echo $i + 1 ?></td>
                <td><span class="id-badge">#<?php echo $t['Teacher_id'] ?></span></td>
                <td><strong><?php echo htmlspecialchars($t['Teacher_fullname']) ?></strong></td>
                <td><?php echo htmlspecialchars($t['Email'] ?? '—') ?></td>
                <td><div class="table-actions">
                    <button class="btn-edit" onclick="openEdit('editTeacherModal',{Teacher_id:'<?php echo $t['Teacher_id'] ?>',Teacher_fullname:'<?php echo addslashes(htmlspecialchars($t['Teacher_fullname'])) ?>',Teacher_email:'<?php echo addslashes(htmlspecialchars($t['Email'] ?? '')) ?>'})"><i class="bi bi-pencil"></i></button>
                    <form method="POST" onsubmit="return confirm('Delete this teacher?')">
                        <input type="hidden" name="action" value="delete_teacher">
                        <input type="hidden" name="Teacher_id" value="<?php echo $t['Teacher_id'] ?>">
                        <button class="btn-del"><i class="bi bi-trash"></i></button>
                    </form>
                </div></td>
            </tr>
            <?php endforeach;else: ?>
            <tr class="empty-row"><td colspan="5"><i class="bi bi-inbox" style="font-size:2.5rem;color:var(--blue-border);display:block;margin-bottom:8px"></i>No teachers found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="modal fade" id="addTeacherModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-badge"></i> Add Teacher</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="?tab=teachers"><input type="hidden" name="action" value="add_teacher">
        <div class="modal-body">
            <label class="form-label">Full Name *</label><input name="Teacher_fullname" class="form-control mb-3" required placeholder="Enter teacher full name">
            <label class="form-label">Email *</label><input name="Teacher_email" type="email" class="form-control mb-3" required placeholder="Enter email address">
            <label class="form-label">Password *</label><input name="Teacher_password" type="password" class="form-control" required placeholder="Enter password">
        </div>
        <div class="modal-footer"><button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button><button class="btn-modal-add">Add Teacher</button></div>
        </form>
    </div></div></div>
    <div class="modal fade" id="editTeacherModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Teacher</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="?tab=teachers"><input type="hidden" name="action" value="edit_teacher"><input type="hidden" name="Teacher_id" id="edit_Teacher_id">
        <div class="modal-body">
            <label class="form-label">Full Name *</label><input name="Teacher_fullname" id="edit_Teacher_fullname" class="form-control mb-3" required>
            <label class="form-label">Email *</label><input name="Teacher_email" id="edit_Teacher_email" type="email" class="form-control mb-3" required>
            <label class="form-label">New Password <small class="text-muted">(leave blank to keep)</small></label><input name="Teacher_password" type="password" class="form-control" placeholder="Enter new password">
        </div>
        <div class="modal-footer"><button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button><button class="btn-modal-add">Update Teacher</button></div>
        </form>
    </div></div></div>

    <?php elseif ($activeTab === 'students'): ?>
    <div class="page-header">
        <h4><i class="bi bi-people"></i> Students</h4>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="bi bi-plus-lg"></i> Add Student</button>
    </div>
    <div class="table-card">
        <table class="modern-table">
            <thead><tr><th>#</th><th>ID</th><th>Full Name</th><th>Email</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($students): foreach ($students as $i => $s): ?>
            <tr>
                <td><?php echo $i + 1 ?></td>
                <td><span class="id-badge">#<?php echo $s['Student_id'] ?></span></td>
                <td><strong><?php echo htmlspecialchars($s['Student_full_name']) ?></strong></td>
                <td><?php echo htmlspecialchars($s['Email'] ?? '—') ?></td>
                <td><div class="table-actions">
                    <button class="btn-edit" onclick="openEdit('editStudentModal',{Student_id:'<?php echo $s['Student_id'] ?>',Student_full_name:'<?php echo addslashes(htmlspecialchars($s['Student_full_name'])) ?>',Student_email:'<?php echo addslashes(htmlspecialchars($s['Email'] ?? '')) ?>'})"><i class="bi bi-pencil"></i></button>
                    <form method="POST" onsubmit="return confirm('Delete this student?')">
                        <input type="hidden" name="action" value="delete_student">
                        <input type="hidden" name="Student_id" value="<?php echo $s['Student_id'] ?>">
                        <button class="btn-del"><i class="bi bi-trash"></i></button>
                    </form>
                </div></td>
            </tr>
            <?php endforeach;else: ?>
            <tr class="empty-row"><td colspan="5"><i class="bi bi-inbox" style="font-size:2.5rem;color:var(--blue-border);display:block;margin-bottom:8px"></i>No students found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="modal fade" id="addStudentModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-people"></i> Add Student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="?tab=students"><input type="hidden" name="action" value="add_student">
        <div class="modal-body">
            <label class="form-label">Full Name *</label><input name="Student_full_name" class="form-control mb-3" required placeholder="Enter student full name">
            <label class="form-label">Email *</label><input name="Student_email" type="email" class="form-control mb-3" required placeholder="Enter email address">
            <label class="form-label">Password *</label><input name="Student_password" type="password" class="form-control" required placeholder="Enter password">
        </div>
        <div class="modal-footer"><button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button><button class="btn-modal-add">Add Student</button></div>
        </form>
    </div></div></div>
    <div class="modal fade" id="editStudentModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="?tab=students"><input type="hidden" name="action" value="edit_student"><input type="hidden" name="Student_id" id="edit_Student_id">
        <div class="modal-body">
            <label class="form-label">Full Name *</label><input name="Student_full_name" id="edit_Student_full_name" class="form-control mb-3" required>
            <label class="form-label">Email *</label><input name="Student_email" id="edit_Student_email" type="email" class="form-control mb-3" required>
            <label class="form-label">New Password <small class="text-muted">(leave blank to keep)</small></label><input name="Student_password" type="password" class="form-control" placeholder="Enter new password">
        </div>
        <div class="modal-footer"><button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button><button class="btn-modal-add">Update Student</button></div>
        </form>
    </div></div></div>

    <?php elseif ($activeTab === 'rooms'): ?>
    <div class="page-header">
        <h4><i class="bi bi-building"></i> Rooms</h4>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addRoomModal"><i class="bi bi-plus-lg"></i> Add Room</button>
    </div>
    <div class="table-card">
        <table class="modern-table">
            <thead><tr><th>#</th><th>ID</th><th>Room Name</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($rooms): foreach ($rooms as $i => $r): ?>
            <tr>
                <td><?php echo $i + 1 ?></td>
                <td><span class="id-badge">#<?php echo $r['Room_id'] ?></span></td>
                <td><strong><?php echo htmlspecialchars($r['Room_name']) ?></strong></td>
                <td><div class="table-actions">
                    <button class="btn-edit" onclick="openEdit('editRoomModal',{Room_id:'<?php echo $r['Room_id'] ?>',Room_name:'<?php echo addslashes(htmlspecialchars($r['Room_name'])) ?>'})"><i class="bi bi-pencil"></i></button>
                    <form method="POST" onsubmit="return confirm('Delete this room?')">
                        <input type="hidden" name="action" value="delete_room">
                        <input type="hidden" name="Room_id" value="<?php echo $r['Room_id'] ?>">
                        <button class="btn-del"><i class="bi bi-trash"></i></button>
                    </form>
                </div></td>
            </tr>
            <?php endforeach;else: ?>
            <tr class="empty-row"><td colspan="4"><i class="bi bi-inbox" style="font-size:2.5rem;color:var(--blue-border);display:block;margin-bottom:8px"></i>No rooms found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="modal fade" id="addRoomModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-building"></i> Add Room</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="?tab=rooms"><input type="hidden" name="action" value="add_room">
        <div class="modal-body"><label class="form-label">Room Name *</label><input name="Room_name" class="form-control" required placeholder="e.g. Room A"></div>
        <div class="modal-footer"><button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button><button class="btn-modal-add">Add Room</button></div>
        </form>
    </div></div></div>
    <div class="modal fade" id="editRoomModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Room</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="?tab=rooms"><input type="hidden" name="action" value="edit_room"><input type="hidden" name="Room_id" id="edit_Room_id">
        <div class="modal-body"><label class="form-label">Room Name *</label><input name="Room_name" id="edit_Room_name" class="form-control" required></div>
        <div class="modal-footer"><button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button><button class="btn-modal-add">Update Room</button></div>
        </form>
    </div></div></div>

    <?php elseif ($activeTab === 'lesson_types'): ?>
    <div class="page-header">
        <h4><i class="bi bi-tags"></i> Lesson Types</h4>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addLessonTypeModal"><i class="bi bi-plus-lg"></i> Add Type</button>
    </div>
    <div class="table-card">
        <table class="modern-table">
            <thead><tr><th>#</th><th>ID</th><th>Type Name</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($lesson_types): foreach ($lesson_types as $i => $lt): ?>
            <tr>
                <td><?php echo $i + 1 ?></td>
                <td><span class="id-badge">#<?php echo $lt['L_Type_id'] ?></span></td>
                <td><strong><?php echo htmlspecialchars($lt['L_Type_name']) ?></strong></td>
                <td><div class="table-actions">
                    <form method="POST" onsubmit="return confirm('Delete this lesson type?')">
                        <input type="hidden" name="action" value="delete_lesson_type">
                        <input type="hidden" name="L_Type_id" value="<?php echo $lt['L_Type_id'] ?>">
                        <button class="btn-del"><i class="bi bi-trash"></i></button>
                    </form>
                </div></td>
            </tr>
            <?php endforeach;else: ?>
            <tr class="empty-row"><td colspan="4"><i class="bi bi-inbox" style="font-size:2.5rem;color:var(--blue-border);display:block;margin-bottom:8px"></i>No lesson types found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="modal fade" id="addLessonTypeModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-tags"></i> Add Lesson Type</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="?tab=lesson_types"><input type="hidden" name="action" value="add_lesson_type">
        <div class="modal-body"><label class="form-label">Type Name *</label><input name="L_Type_name" class="form-control" required placeholder="e.g. Mathematics, Science"></div>
        <div class="modal-footer"><button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button><button class="btn-modal-add">Add Type</button></div>
        </form>
    </div></div></div>

    <?php elseif ($activeTab === 'lessons'): ?>
    <div class="page-header">
        <h4><i class="bi bi-book"></i> Lessons</h4>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addLessonModal"><i class="bi bi-plus-lg"></i> Add Lesson</button>
    </div>
    <div class="table-card">
        <table class="modern-table">
            <thead><tr><th>#</th><th>ID</th><th>Lesson Name</th><th>Type</th><th>Room</th><th>Teacher</th><th>PDF</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($lessons): foreach ($lessons as $i => $l): ?>
            <tr>
                <td><?php echo $i + 1 ?></td>
                <td><span class="id-badge">#<?php echo $l['Lesson_id'] ?></span></td>
                <td><strong><?php echo htmlspecialchars($l['Lesson_name']) ?></strong></td>
                <td><?php echo htmlspecialchars($l['L_Type_name'] ?? '—') ?></td>
                <td><?php echo htmlspecialchars($l['Room_name'] ?? '—') ?></td>
                <td><?php echo htmlspecialchars($l['Teacher_fullname'] ?? '—') ?></td>
                <td><?php if (! empty($l['File_Path'])): ?><a href="<?php echo htmlspecialchars($l['File_Path']) ?>" target="_blank" class="pdf-badge"><i class="bi bi-file-pdf"></i> PDF</a><?php else: ?><span style="color:#94a3b8">—</span><?php endif; ?></td>
                <td><div class="table-actions">
                    <button class="btn-edit" data-bs-toggle="modal" data-bs-target="#editLessonModal" onclick="fillEditLesson(<?php echo htmlspecialchars(json_encode($l)) ?>)"><i class="bi bi-pencil"></i></button>
                    <form method="POST" onsubmit="return confirm('Delete this lesson?')">
                        <input type="hidden" name="action" value="delete_lesson">
                        <input type="hidden" name="Lesson_id" value="<?php echo $l['Lesson_id'] ?>">
                        <button class="btn-del"><i class="bi bi-trash"></i></button>
                    </form>
                </div></td>
            </tr>
            <?php endforeach;else: ?>
            <tr class="empty-row"><td colspan="8"><i class="bi bi-inbox" style="font-size:2.5rem;color:var(--blue-border);display:block;margin-bottom:8px"></i>No lessons found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="modal fade" id="addLessonModal" tabindex="-1">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-book"></i> Add Lesson</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="?tab=lessons" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_lesson">
                <div class="modal-body row g-3">
                    <div class="col-12"><label class="form-label">Lesson Name *</label><input name="Lesson_name" class="form-control" required placeholder="Enter lesson name"></div>
                    <div class="col-md-6">
                        <label class="form-label">Lesson Type</label>
                        <select name="L_Type_id" class="form-select"><option value="">— None —</option>
                        <?php foreach ($lesson_types as $lt): ?><option value="<?php echo $lt['L_Type_id'] ?>"><?php echo htmlspecialchars($lt['L_Type_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Room</label>
                        <select name="Room_id" class="form-select"><option value="">— None —</option>
                        <?php foreach ($rooms as $r): ?><option value="<?php echo $r['Room_id'] ?>"><?php echo htmlspecialchars($r['Room_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Teacher</label>
                        <select name="Teacher_id" class="form-select"><option value="">— None —</option>
                        <?php foreach ($teachers as $t): ?><option value="<?php echo $t['Teacher_id'] ?>"><?php echo htmlspecialchars($t['Teacher_fullname']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12"><label class="form-label">Upload PDF</label><input type="file" name="lesson_pdf" accept="application/pdf" class="form-control"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button><button class="btn-modal-add">Add Lesson</button></div>
            </form>
        </div></div>
    </div>
    <div class="modal fade" id="editLessonModal" tabindex="-1">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Lesson</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="?tab=lessons" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_lesson">
                <input type="hidden" name="Lesson_id" id="el_Lesson_id">
                <input type="hidden" name="old_file_path" id="el_old_file_path">
                <div class="modal-body row g-3">
                    <div class="col-12"><label class="form-label">Lesson Name *</label><input name="Lesson_name" id="el_Lesson_name" class="form-control" required></div>
                    <div class="col-md-6">
                        <label class="form-label">Lesson Type</label>
                        <select name="L_Type_id" id="el_L_Type_id" class="form-select"><option value="">— None —</option>
                        <?php foreach ($lesson_types as $lt): ?><option value="<?php echo $lt['L_Type_id'] ?>"><?php echo htmlspecialchars($lt['L_Type_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Room</label>
                        <select name="Room_id" id="el_Room_id" class="form-select"><option value="">— None —</option>
                        <?php foreach ($rooms as $r): ?><option value="<?php echo $r['Room_id'] ?>"><?php echo htmlspecialchars($r['Room_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Teacher</label>
                        <select name="Teacher_id" id="el_Teacher_id" class="form-select"><option value="">— None —</option>
                        <?php foreach ($teachers as $t): ?><option value="<?php echo $t['Teacher_id'] ?>"><?php echo htmlspecialchars($t['Teacher_fullname']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Replace PDF <small class="text-muted">(leave empty to keep current)</small></label>
                        <input type="file" name="lesson_pdf" accept="application/pdf" class="form-control">
                        <small id="el_current_pdf" class="text-muted mt-1 d-block"></small>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button><button class="btn-modal-add">Update Lesson</button></div>
            </form>
        </div></div>
    </div>

    <?php elseif ($activeTab === 'exam_types'): ?>
    <div class="page-header">
        <h4><i class="bi bi-tags"></i> Exam Types</h4>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addExamTypeModal"><i class="bi bi-plus-lg"></i> Add Type</button>
    </div>
    <div class="table-card">
        <table class="modern-table">
            <thead><tr><th>#</th><th>ID</th><th>Type Name</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($exam_types): foreach ($exam_types as $i => $et): ?>
            <tr>
                <td><?php echo $i + 1 ?></td>
                <td><span class="id-badge">#<?php echo $et['E_Type_id'] ?></span></td>
                <td><strong><?php echo htmlspecialchars($et['E_Type_name']) ?></strong></td>
                <td><div class="table-actions">
                    <form method="POST" onsubmit="return confirm('Delete this exam type?')">
                        <input type="hidden" name="action" value="delete_exam_type">
                        <input type="hidden" name="E_Type_id" value="<?php echo $et['E_Type_id'] ?>">
                        <button class="btn-del"><i class="bi bi-trash"></i></button>
                    </form>
                </div></td>
            </tr>
            <?php endforeach;else: ?>
            <tr class="empty-row"><td colspan="4"><i class="bi bi-inbox" style="font-size:2.5rem;color:var(--blue-border);display:block;margin-bottom:8px"></i>No exam types found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="modal fade" id="addExamTypeModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-tags"></i> Add Exam Type</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="?tab=exam_types"><input type="hidden" name="action" value="add_exam_type">
        <div class="modal-body"><label class="form-label">Type Name *</label><input name="E_Type_name" class="form-control" required placeholder="e.g. Midterm, Final"></div>
        <div class="modal-footer"><button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button><button class="btn-modal-add">Add Type</button></div>
        </form>
    </div></div></div>

    <?php elseif ($activeTab === 'exams'): ?>
    <div class="page-header">
        <h4><i class="bi bi-file-earmark-text"></i> Exams</h4>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addExamModal"><i class="bi bi-plus-lg"></i> Add Exam</button>
    </div>
    <div class="table-card">
        <table class="modern-table">
            <thead><tr><th>#</th><th>ID</th><th>Exam Name</th><th>Type</th><th>Room</th><th>Lesson</th><th>Start</th><th>End</th><th>PDF</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($exams): foreach ($exams as $i => $e): ?>
            <tr>
                <td><?php echo $i + 1 ?></td>
                <td><span class="id-badge">#<?php echo $e['Exam_id'] ?></span></td>
                <td><strong><?php echo htmlspecialchars($e['Exam_name']) ?></strong></td>
                <td><?php echo htmlspecialchars($e['E_Type_name'] ?? '—') ?></td>
                <td><?php echo htmlspecialchars($e['Room_name'] ?? '—') ?></td>
                <td><?php echo htmlspecialchars($e['Lesson_name'] ?? '—') ?></td>
                <td><?php echo $e['start_at'] ? date('d M Y H:i', strtotime($e['start_at'])) : '—' ?></td>
                <td><?php echo $e['end_at'] ? date('d M Y H:i', strtotime($e['end_at'])) : '—' ?></td>
                <td><?php if (! empty($e['File_Path'])): ?><a href="<?php echo htmlspecialchars($e['File_Path']) ?>" target="_blank" class="pdf-badge"><i class="bi bi-file-pdf"></i> PDF</a><?php else: ?><span style="color:#94a3b8">—</span><?php endif; ?></td>
                <td><div class="table-actions">
                    <button class="btn-edit" data-bs-toggle="modal" data-bs-target="#editExamModal" onclick="fillEditExam(<?php echo htmlspecialchars(json_encode($e)) ?>)"><i class="bi bi-pencil"></i></button>
                    <form method="POST" onsubmit="return confirm('Delete this exam?')">
                        <input type="hidden" name="action" value="delete_exam">
                        <input type="hidden" name="Exam_id" value="<?php echo $e['Exam_id'] ?>">
                        <button class="btn-del"><i class="bi bi-trash"></i></button>
                    </form>
                </div></td>
            </tr>
            <?php endforeach;else: ?>
            <tr class="empty-row"><td colspan="10"><i class="bi bi-inbox" style="font-size:2.5rem;color:var(--blue-border);display:block;margin-bottom:8px"></i>No exams found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="modal fade" id="addExamModal" tabindex="-1">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-file-earmark-text"></i> Add Exam</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="?tab=exams" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_exam">
                <div class="modal-body row g-3">
                    <div class="col-12"><label class="form-label">Exam Name *</label><input name="Exam_name" class="form-control" required placeholder="Enter exam name"></div>
                    <div class="col-md-6">
                        <label class="form-label">Exam Type</label>
                        <select name="E_Type_id" class="form-select"><option value="">— None —</option>
                        <?php foreach ($exam_types as $et): ?><option value="<?php echo $et['E_Type_id'] ?>"><?php echo htmlspecialchars($et['E_Type_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Room</label>
                        <select name="Room_id" class="form-select"><option value="">— None —</option>
                        <?php foreach ($rooms as $r): ?><option value="<?php echo $r['Room_id'] ?>"><?php echo htmlspecialchars($r['Room_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Related Lesson</label>
                        <select name="Lesson_id" class="form-select"><option value="">— None —</option>
                        <?php foreach ($lessons as $l): ?><option value="<?php echo $l['Lesson_id'] ?>"><?php echo htmlspecialchars($l['Lesson_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Start Date & Time</label><input type="datetime-local" name="start_at" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">End Date & Time</label><input type="datetime-local" name="end_at" class="form-control"></div>
                    <div class="col-12"><label class="form-label">Upload PDF</label><input type="file" name="exam_pdf" accept="application/pdf" class="form-control"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button><button class="btn-modal-add">Add Exam</button></div>
            </form>
        </div></div>
    </div>
    <div class="modal fade" id="editExamModal" tabindex="-1">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Exam</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="?tab=exams" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_exam">
                <input type="hidden" name="Exam_id" id="ee_Exam_id">
                <input type="hidden" name="old_file_path" id="ee_old_file_path">
                <div class="modal-body row g-3">
                    <div class="col-12"><label class="form-label">Exam Name *</label><input name="Exam_name" id="ee_Exam_name" class="form-control" required></div>
                    <div class="col-md-6">
                        <label class="form-label">Exam Type</label>
                        <select name="E_Type_id" id="ee_E_Type_id" class="form-select"><option value="">— None —</option>
                        <?php foreach ($exam_types as $et): ?><option value="<?php echo $et['E_Type_id'] ?>"><?php echo htmlspecialchars($et['E_Type_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Room</label>
                        <select name="Room_id" id="ee_Room_id" class="form-select"><option value="">— None —</option>
                        <?php foreach ($rooms as $r): ?><option value="<?php echo $r['Room_id'] ?>"><?php echo htmlspecialchars($r['Room_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Related Lesson</label>
                        <select name="Lesson_id" id="ee_Lesson_id" class="form-select"><option value="">— None —</option>
                        <?php foreach ($lessons as $l): ?><option value="<?php echo $l['Lesson_id'] ?>"><?php echo htmlspecialchars($l['Lesson_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label">Start Date & Time</label><input type="datetime-local" name="start_at" id="ee_start_at" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">End Date & Time</label><input type="datetime-local" name="end_at" id="ee_end_at" class="form-control"></div>
                    <div class="col-12">
                        <label class="form-label">Replace PDF <small class="text-muted">(leave empty to keep current)</small></label>
                        <input type="file" name="exam_pdf" accept="application/pdf" class="form-control">
                        <small id="ee_current_pdf" class="text-muted mt-1 d-block"></small>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button><button class="btn-modal-add">Update Exam</button></div>
            </form>
        </div></div>
    </div>

    <?php elseif ($activeTab === 'enrollments'): ?>
    <div class="page-header">
        <h4><i class="bi bi-person-check"></i> Enrollments</h4>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addEnrollmentModal"><i class="bi bi-plus-lg"></i> Add Enrollment</button>
    </div>
    <div class="table-card">
        <table class="modern-table">
            <thead><tr><th>#</th><th>ID</th><th>Student</th><th>Lesson</th><th>Room</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ($enrollments): foreach ($enrollments as $i => $en): ?>
            <tr>
                <td><?php echo $i + 1 ?></td>
                <td><span class="id-badge">#<?php echo $en['Enroll_id'] ?></span></td>
                <td><strong><?php echo htmlspecialchars($en['Student_full_name'] ?? '—') ?></strong></td>
                <td><?php echo htmlspecialchars($en['Lesson_name'] ?? '—') ?></td>
                <td><?php echo htmlspecialchars($en['Room_name'] ?? '—') ?></td>
                <td><div class="table-actions">
                    <button class="btn-edit" onclick="fillEditEnrollment(<?php echo htmlspecialchars(json_encode($en)) ?>)"><i class="bi bi-pencil"></i></button>
                    <form method="POST" onsubmit="return confirm('Delete this enrollment?')">
                        <input type="hidden" name="action" value="delete_enrollment">
                        <input type="hidden" name="Enroll_id" value="<?php echo $en['Enroll_id'] ?>">
                        <button class="btn-del"><i class="bi bi-trash"></i></button>
                    </form>
                </div></td>
            </tr>
            <?php endforeach;else: ?>
            <tr class="empty-row"><td colspan="6"><i class="bi bi-inbox" style="font-size:2.5rem;color:var(--blue-border);display:block;margin-bottom:8px"></i>No enrollments found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="modal fade" id="addEnrollmentModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-check"></i> Add Enrollment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="?tab=enrollments"><input type="hidden" name="action" value="add_enrollment">
        <div class="modal-body">
            <label class="form-label">Student *</label>
            <select name="Student_id" class="form-select mb-3" required><option value="">— Select Student —</option>
            <?php foreach ($students as $s): ?><option value="<?php echo $s['Student_id'] ?>"><?php echo htmlspecialchars($s['Student_full_name']) ?></option><?php endforeach; ?>
            </select>
            <label class="form-label">Lesson *</label>
            <select name="Lesson_id" class="form-select mb-3" required><option value="">— Select Lesson —</option>
            <?php foreach ($lessons as $l): ?><option value="<?php echo $l['Lesson_id'] ?>"><?php echo htmlspecialchars($l['Lesson_name']) ?></option><?php endforeach; ?>
            </select>
            <label class="form-label">Room</label>
            <select name="Room_id" class="form-select"><option value="">— Select Room —</option>
            <?php foreach ($rooms as $r): ?><option value="<?php echo $r['Room_id'] ?>"><?php echo htmlspecialchars($r['Room_name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="modal-footer"><button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button><button class="btn-modal-add">Add Enrollment</button></div>
        </form>
    </div></div></div>
    <div class="modal fade" id="editEnrollmentModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Enrollment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form method="POST" action="?tab=enrollments">
            <input type="hidden" name="action" value="edit_enrollment">
            <input type="hidden" name="Enroll_id" id="en_Enroll_id">
        <div class="modal-body">
            <label class="form-label">Student *</label>
            <select name="Student_id" id="en_Student_id" class="form-select mb-3" required><option value="">— Select Student —</option>
            <?php foreach ($students as $s): ?><option value="<?php echo $s['Student_id'] ?>"><?php echo htmlspecialchars($s['Student_full_name']) ?></option><?php endforeach; ?>
            </select>
            <label class="form-label">Lesson *</label>
            <select name="Lesson_id" id="en_Lesson_id" class="form-select mb-3" required><option value="">— Select Lesson —</option>
            <?php foreach ($lessons as $l): ?><option value="<?php echo $l['Lesson_id'] ?>"><?php echo htmlspecialchars($l['Lesson_name']) ?></option><?php endforeach; ?>
            </select>
            <label class="form-label">Room</label>
            <select name="Room_id" id="en_Room_id" class="form-select"><option value="">— Select Room —</option>
            <?php foreach ($rooms as $r): ?><option value="<?php echo $r['Room_id'] ?>"><?php echo htmlspecialchars($r['Room_name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="modal-footer"><button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button><button class="btn-modal-add">Update Enrollment</button></div>
        </form>
    </div></div></div>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openEdit(modalId, data) {
    for (const [key, val] of Object.entries(data)) {
        const el = document.getElementById('edit_' + key);
        if (el) el.value = val;
    }
    new bootstrap.Modal(document.getElementById(modalId)).show();
}

function fillEditLesson(l) {
    document.getElementById('el_Lesson_id').value     = l.Lesson_id;
    document.getElementById('el_Lesson_name').value   = l.Lesson_name;
    document.getElementById('el_L_Type_id').value     = l.L_Type_id  ?? '';
    document.getElementById('el_Room_id').value       = l.Room_id    ?? '';
    document.getElementById('el_Teacher_id').value    = l.Teacher_id ?? '';
    document.getElementById('el_old_file_path').value = l.File_Path  ?? '';
    document.getElementById('el_current_pdf').textContent = l.File_Path ? '📎 Current: ' + l.File_Path : 'No file uploaded';
}

function fillEditExam(e) {
    document.getElementById('ee_Exam_id').value       = e.Exam_id;
    document.getElementById('ee_Exam_name').value     = e.Exam_name;
    document.getElementById('ee_E_Type_id').value     = e.E_Type_id  ?? '';
    document.getElementById('ee_Room_id').value       = e.Room_id    ?? '';
    document.getElementById('ee_Lesson_id').value     = e.Lesson_id  ?? '';
    document.getElementById('ee_old_file_path').value = e.File_Path  ?? '';
    document.getElementById('ee_current_pdf').textContent = e.File_Path ? '📎 Current: ' + e.File_Path : 'No file uploaded';
    document.getElementById('ee_start_at').value      = e.start_at ? e.start_at.slice(0, 16) : '';
    document.getElementById('ee_end_at').value        = e.end_at   ? e.end_at.slice(0, 16)   : '';
}

function fillEditEnrollment(en) {
    document.getElementById('en_Enroll_id').value  = en.Enroll_id;
    document.getElementById('en_Student_id').value = en.Student_id;
    document.getElementById('en_Lesson_id').value  = en.Lesson_id;
    document.getElementById('en_Room_id').value    = en.Room_id ?? '';
    new bootstrap.Modal(document.getElementById('editEnrollmentModal')).show();
}
</script>
</body>
</html>