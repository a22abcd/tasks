<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/check_permissions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userRole = getUserRole($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #28a745;
            --background-color: #f5f5f5;
            --border-radius: 10px;
            --box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: var(--background-color);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 15px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
        }

        .main-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .section {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .section:hover {
            transform: translateY(-5px);
        }

        .section-title {
            color: var(--primary-color);
            margin: 0;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }

        .sub-menu {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 1000;
            min-width: 300px;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .menu-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .menu-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px;
            background: var(--background-color);
            border-radius: var(--border-radius);
            text-decoration: none;
            color: #333;
            transition: transform 0.3s;
        }

        .menu-item:hover {
            transform: translateY(-3px);
        }

        .menu-item i {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .administrative i { color: #0066cc; }
        .financial i { color: #28a745; }

        .coming-soon {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .coming-soon::after {
            content: '(قريباً)';
            display: block;
            font-size: 12px;
            color: #666;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            left: 10px;
            cursor: pointer;
            font-size: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>لوحة التحكم</h1>
            <div class="welcome">
                <?php 
                echo "مرحباً بك، ";
                switch($userRole) {
                    case 'admin':
                        echo "مدير النظام";
                        break;
                    case 'financial_manager':
                        echo "مدير الشؤون المالية";
                        break;
                    case 'administrative_manager':
                        echo "مدير الشؤون الإدارية";
                        break;
                    default:
                        echo "الموظف";
                }
                ?>
            </div>
        </div>

        <div class="main-sections">
            <?php if ($userRole == 'admin' || $userRole == 'administrative_manager'): ?>
            <div class="section administrative" onclick="toggleSubMenu('administrative-menu')">
                <h2 class="section-title">
                    <i class="fas fa-building"></i>
                    الشؤون الإدارية
                </h2>
            </div>
            <?php endif; ?>

            <?php if ($userRole == 'admin' || $userRole == 'financial_manager'): ?>
            <div class="section financial" onclick="toggleSubMenu('financial-menu')">
                <h2 class="section-title">
                    <i class="fas fa-coins"></i>
                    الشؤون المالية
                </h2>
            </div>
            <?php endif; ?>
        </div>
    </div>

<!-- إضافة هذا القسم في منطقة main-sections في ملف dashboard.php -->
<?php if ($userRole == 'admin'): ?>
<div class="section system-admin" onclick="toggleSubMenu('admin-menu')">
    <h2 class="section-title">
        <i class="fas fa-cogs"></i>
        إدارة النظام
    </h2>
</div>
<?php endif; ?>


    <!-- القائمة الفرعية للشؤون الإدارية -->
    <div id="administrative-menu" class="sub-menu">
        <div class="close-btn" onclick="closeSubMenu('administrative-menu')">×</div>
        <h2 class="section-title">
            <i class="fas fa-building"></i>
            الشؤون الإدارية
        </h2>
        <div class="menu-items">
            <a href="tasks.php" class="menu-item">
                <i class="fas fa-tasks"></i>
                <span>المهام اليومية</span>
            </a>
            <a href="daily_report.php" class="menu-item">
                <i class="fas fa-file-alt"></i>
                <span>التقارير</span>
            </a>
        </div>
    </div>

    <!-- القائمة الفرعية للشؤون المالية -->
    <div id="financial-menu" class="sub-menu">
        <div class="close-btn" onclick="closeSubMenu('financial-menu')">×</div>
        <h2 class="section-title">
            <i class="fas fa-coins"></i>
            الشؤون المالية
        </h2>
        <div class="menu-items">
            <a href="transportation.php" class="menu-item">
                <i class="fas fa-bus"></i>
                <span>مصاريف المواصلات</span>
            </a>
            <a href="attendance.php" class="menu-item">
                <i class="fas fa-calendar-check"></i>
                <span>الحضور والغياب</span>
            </a>
            <div class="menu-item coming-soon">
                <i class="fas fa-hand-holding-usd"></i>
                <span>العهد والسلف</span>
            </div>
            <div class="menu-item coming-soon">
                <i class="fas fa-money-bill-wave"></i>
                <span>الرواتب</span>
            </div>
        </div>
    </div>

    <div class="overlay" id="overlay"></div>


<!-- إضافة هذا القسم قبل نهاية body -->
<div id="admin-menu" class="sub-menu">
    <div class="close-btn" onclick="closeSubMenu('admin-menu')">×</div>
    <h2 class="section-title">
        <i class="fas fa-cogs"></i>
        إدارة النظام
    </h2>
    <div class="menu-items">
        <a href="users.php" class="menu-item">
            <i class="fas fa-users"></i>
            <span>إدارة المستخدمين</span>
        </a>
        <a href="permissions.php" class="menu-item">
            <i class="fas fa-user-shield"></i>
            <span>إدارة الصلاحيات</span>
        </a>
    </div>
</div>


    <script>
    function toggleSubMenu(menuId) {
        const menu = document.getElementById(menuId);
        const overlay = document.getElementById('overlay');
        menu.style.display = 'block';
        overlay.style.display = 'block';
    }

    function closeSubMenu(menuId) {
        const menu = document.getElementById(menuId);
        const overlay = document.getElementById('overlay');
        menu.style.display = 'none';
        overlay.style.display = 'none';
    }

    // إغلاق القائمة عند النقر على الخلفية
    document.getElementById('overlay').addEventListener('click', function() {
        document.querySelectorAll('.sub-menu').forEach(menu => {
            menu.style.display = 'none';
        });
        this.style.display = 'none';
    });
    </script>
</body>
</html>