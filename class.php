<?php
    session_start();

    if (! isset($_SESSION["Teacher_fullname"]) && ! isset($_SESSION["Student_full_name"])) {
    header("Location: index.php");
    exit();
    }

    $host     = "localhost";
    $dbname   = "reviewAndRetake";
    $username = "root";
    $password = "";

    try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
    die("ການເຊື່ອມຕໍ່ລົ້ມເຫລວ: " . $e->getMessage());
    }

    try {
    $stmt    = $pdo->query("SELECT * FROM tb_Room");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
    die("ການສອບຖາມລົ້ມເຫລວ: " . $e->getMessage());
    }

    $isTeacher   = isset($_SESSION["Teacher_fullname"]);
    $displayName = $isTeacher ? $_SESSION["Teacher_fullname"] : $_SESSION["Student_full_name"];
    $userRole    = $isTeacher ? "ອາຈານ" : "ນັກຮຽນ";
    $columns     = ! empty($classes) ? array_keys($classes[0]) : [];
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ຫ້ອງຮຽນຂອງຂ້ອຍ</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --primary-light: #818cf8;
            --accent: #06b6d4;
            --bg: #f1f5f9;
            --card-bg: #ffffff;
            --text: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
            --shadow: 0 4px 24px rgba(79,70,229,0.10);
            --shadow-hover: 0 12px 40px rgba(79,70,229,0.18);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Noto Sans Lao', 'Segoe UI', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            color: var(--text);
        }

        /* NAVBAR */
        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 0 40px;
            height: 68px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 16px rgba(79,70,229,0.25);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .brand-dot {
            width: 10px; height: 10px;
            background: var(--accent);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.3); }
        }
        .nav-right { display: flex; align-items: center; gap: 16px; }
        .user-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 50px;
            color: #e0e7ff;
            font-size: 14px;
        }
        .user-avatar {
            width: 30px; height: 30px;
            background: var(--accent);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
            font-weight: 700;
            color: #fff;
        }
        .role-badge {
            font-size: 10px;
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 50px;
            opacity: 0.85;
        }
        .btn-logout {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.15);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 18px;
            border-radius: 50px;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.25s;
            cursor: pointer;
        }
        .btn-logout:hover {
            background: rgba(255,255,255,0.28);
            border-color: rgba(255,255,255,0.5);
        }

        /* HERO BANNER */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            padding: 48px 40px 60px;
            text-align: center;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 220px; height: 220px;
            background: rgba(255,255,255,0.07);
            border-radius: 50%;
        }
        .hero::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -40px;
            width: 280px; height: 280px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .hero h1 { font-size: 32px; font-weight: 700; margin-bottom: 8px; }
        .hero p { font-size: 15px; opacity: 0.85; }
        .hero-wave {
            position: absolute;
            bottom: 0; left: 0; right: 0;
        }

        /* MAIN */
        .main { max-width: 1140px; margin: -30px auto 60px; padding: 0 24px; position: relative; z-index: 1; }

        /* STATS BAR */
        .stats-bar {
            display: flex;
            gap: 16px;
            margin-bottom: 32px;
            flex-wrap: wrap;
        }
        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 18px 28px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: var(--shadow);
            flex: 1;
            min-width: 180px;
        }
        .stat-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
        }
        .stat-icon.blue { background: #ede9fe; }
        .stat-icon.cyan { background: #cffafe; }
        .stat-label { font-size: 12px; color: var(--text-muted); margin-bottom: 2px; }
        .stat-value { font-size: 22px; font-weight: 700; color: var(--text); }

        /* SECTION HEADER */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .section-title { font-size: 18px; font-weight: 700; color: var(--text); }
        .section-title span { color: var(--primary); }

        /* CARDS GRID */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }
        .card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform 0.25s, box-shadow 0.25s;
            border: 1px solid var(--border);
        }
        .card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-hover);
        }
        .card-top {
            padding: 28px 24px 20px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            position: relative;
            overflow: hidden;
        }
        .card-top::after {
            content: '';
            position: absolute;
            bottom: -30px; right: -30px;
            width: 100px; height: 100px;
            background: rgba(255,255,255,0.08);
            border-radius: 50%;
        }
        .card-icon-wrap {
            width: 54px; height: 54px;
            background: rgba(255,255,255,0.18);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px;
            margin-bottom: 14px;
        }
        .card-top h3 { color: #fff; font-size: 17px; font-weight: 700; line-height: 1.3; }
        .card-num {
            position: absolute;
            top: 16px; right: 16px;
            background: rgba(255,255,255,0.22);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 50px;
            letter-spacing: 0.5px;
        }
        .card-body { padding: 18px 24px 4px; }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
            font-size: 13.5px;
        }
        .info-row:last-child { border-bottom: none; }
        .info-key { color: var(--text-muted); font-size: 12px; }
        .info-val { font-weight: 600; color: var(--text); font-size: 13px; }
        .card-footer {
            padding: 16px 24px 20px;
        }
        .btn-enter {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: opacity 0.25s, transform 0.2s;
            letter-spacing: 0.3px;
        }
        .btn-enter:hover { opacity: 0.9; transform: scale(1.02); }
        .btn-enter svg { width: 16px; height: 16px; }

        /* EMPTY */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-muted);
            grid-column: 1 / -1;
            background: #fff;
            border-radius: 20px;
            border: 2px dashed var(--border);
        }
        .empty-state .big-icon { font-size: 64px; margin-bottom: 16px; }
        .empty-state p { font-size: 16px; }

        /* FOOTER */
        .footer {
            text-align: center;
            padding: 24px;
            color: var(--text-muted);
            font-size: 13px;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="brand">
        <span>📚</span> Review and Retake Exams Room
        <div class="brand-dot"></div>
    </div>
    <div class="nav-right">
        <div class="user-chip">
            <div class="user-avatar"><?php echo mb_substr($displayName, 0, 1, 'UTF-8'); ?></div>
            <span class="role-badge"><?php echo $userRole; ?></span>
            <?php echo htmlspecialchars($displayName); ?>
        </div>
        <a href="logout.php" class="btn-logout">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M18 15l3-3m0 0l-3-3m3 3H9"/>
            </svg>
            ອອກຈາກລະບົບ
        </a>
    </div>
</nav>

<!-- HERO -->
<div class="hero">
    <h1>ຍິນດີຕ້ອນຮັບ, <?php echo htmlspecialchars($displayName); ?> 👋</h1>
    <p>ເລືອກຫ້ອງຮຽນຂອງທ່ານເພື່ອເລີ່ມຕົ້ນການຮຽນ , ສອບເສັງ ແລະ ສອບເສັງຄືນ</p>
    <svg class="hero-wave" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 40">
        <path fill="#f1f5f9" d="M0,32L1440,0L1440,40L0,40Z"/>
    </svg>
</div>

<div class="main">

    <!-- STATS -->
    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-icon blue">📖</div>
            <div>
                <div class="stat-label">ຫ້ອງຮຽນທັງໝົດ</div>
                <div class="stat-value"><?php echo count($classes); ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon cyan">📅</div>
            <div>
                <div class="stat-label">ວັນທີປັດຈຸບັນ</div>
                <div class="stat-value" style="font-size:15px;"><?php echo date('d/m/Y'); ?></div>
            </div>
        </div>
    </div>

    <!-- SECTION HEADER -->
    <div class="section-header">
        <div class="section-title">ຫ້ອງຮຽນ <span>ທັງໝົດ</span></div>
    </div>

    <!-- CARDS -->
    <div class="cards-grid">
        <?php if (empty($classes)): ?>
            <div class="empty-state">
                <div class="big-icon">🗂️</div>
                <p>ຍັງບໍ່ມີຫ້ອງຮຽນໃນລະບົບ</p>
            </div>
        <?php else: ?>
            <?php
                $icons = ['📘', '📗', '📙', '📕', '📓'];
                foreach ($classes as $index => $class):
                    $icon      = $icons[$index % count($icons)];
                    $classId   = $class[$columns[0]] ?? $index;
                    $className = $class[$columns[1]] ?? 'ຫ້ອງຮຽນ ' . ($index + 1);
            ?>
            <div class="card">
                <div class="card-top">
                    <div class="card-icon-wrap"><?php echo $icon; ?></div>
                    <h3><?php echo htmlspecialchars($className); ?></h3>
                    <span class="card-num"># <?php echo $index + 1; ?></span>
                </div>
                <div class="card-body">
                    <?php foreach ($class as $key => $value): ?>
                    <div class="info-row">
                        <span class="info-key"><?php echo htmlspecialchars($key); ?></span>
                        <span class="info-val"><?php echo htmlspecialchars($value ?? 'ບໍ່ມີຂໍ້ມູນ'); ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div class="info-row">
                        <span class="info-key">📅 ວັນທີ</span>
                        <span class="info-val"><?php echo date('d/m/Y'); ?></span>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="home.php?class_id=<?php echo htmlspecialchars($class[$columns[0]]); ?>" class="btn-enter">
                        ເຂົ້າຫ້ອງຮຽນ
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="footer">© <?php echo date('Y'); ?> Review and Retake Exams · ລະບົບຈັດການຫ້ອງຮຽນ</div>

</body>
</html>