<?php
session_start();
require_once 'config.php';
require_once 'check_permissions.php';

// تفعيل عرض الأخطاء للتطوير
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// تحديث الصلاحيات عند تسجيل الدخول
if (!isset($_SESSION['permissions'])) {
    $_SESSION['permissions'] = getUserPermissions($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            text-align: center;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .main-menu {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 30px;
        }

        .menu-item {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            cursor: pointer;
            width: 200px;
        }

        .menu-item:hover {
            transform: translateY(-5px);
            transition: transform 0.3s;
        }

        .sub-menu {
            display: none;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .sub-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            width: 150px;
            text-align: center;
        }

        .icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .administrative .icon {
            color: #007bff;
        }

        .financial .icon {
            color: #28a745;
        }

        h3 {
            margin: 10px 0;
            color: #333;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

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
    </style>
</head>
<body>
    <div class="container">
        <div class="main-menu">
            <?php if (hasPermission('view_administrative')): ?>
            <div class="menu-item administrative" onclick="toggleSubMenu('administrative-sub')">
                <i class="icon fas fa-building"></i>
                <h3>الشؤون الإدارية</h3>
            </div>
            <?php endif; ?>

            <?php if (hasPermission('view_financial')): ?>
            <div class="menu-item financial" onclick="toggleSubMenu('financial-sub')">
                <i class="icon fas fa-coins"></i>
                <h3>الشؤون المالية</h3>
            </div>
            <?php endif; ?>
        </div>

        <?php if (hasPermission('view_administrative')): ?>
        <div id="administrative-sub" class="sub-menu">
            <?php if (hasPermission('view_tasks')): ?>
            <a href="tasks.php" class="sub-item">
                <i class="icon fas fa-tasks"></i>
                <h3>المهام اليومية</h3>
            </a>
            <?php endif; ?>
            
            <?php if (hasPermission('view_reports')): ?>
            <a href="daily_report.php" class="sub-item">
                <i class="icon fas fa-file-alt"></i>
                <h3>التقارير</h3>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (hasPermission('view_financial')): ?>
        <div id="financial-sub" class="sub-menu">
            <?php if (hasPermission('view_transportation')): ?>
            <a href="transportation.php" class="sub-item">
                <i class="icon fas fa-bus"></i>
                <h3>مصاريف المواصلات</h3>
            </a>
            <?php endif; ?>
            
            <?php if (hasPermission('view_attendance')): ?>
            <a href="attendance.php" class="sub-item">
                <i class="icon fas fa-calendar-check"></i>
                <h3>الحضور والغياب</h3>
            </a>
            <?php endif; ?>

            <?php if (hasPermission('view_loans')): ?>
            <div class="sub-item coming-soon">
                <i class="icon fas fa-hand-holding-usd"></i>
                <h3>العهد والسلف</h3>
            </div>
            <?php endif; ?>

            <?php if (hasPermission('view_salaries')): ?>
            <div class="sub-item coming-soon">
                <i class="icon fas fa-money-bill-wave"></i>
                <h3>الرواتب</h3>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleSubMenu(id) {
            const subMenus = document.querySelectorAll('.sub-menu');
            subMenus.forEach(menu => {
                if (menu.id === id) {
                    menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
                } else {
                    menu.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
