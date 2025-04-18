<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/check_permissions.php';

// تفعيل عرض الأخطاء للتطوير
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// تحديث الصلاحيات
$_SESSION['permissions'] = getUserPermissions($_SESSION['user_id']);

// إضافة معلومات المستخدم للتصحيح
$userInfo = "<!-- User ID: {$_SESSION['user_id']} -->\n";
$userInfo .= "<!-- Permissions: " . implode(', ', $_SESSION['permissions']) . " -->\n";
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ... الأنماط السابقة ... */
        
        /* إضافة أنماط جديدة */
        .user-info {
            text-align: left;
            padding: 10px;
            background: #f8f9fa;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .menu-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        
        .section {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 300px;
        }
    </style>
</head>
<body>
    <?php echo $userInfo; // معلومات التصحيح ?>
    
    <div class="container">
        <div class="user-info">
            <p>مرحباً بك في لوحة التحكم</p>
        </div>
        
        <div class="menu-container">
            <?php if (hasPermission('view_administrative')): ?>
            <div class="section">
                <h2>الشؤون الإدارية</h2>
                <div class="menu-items">
                    <?php if (hasPermission('view_tasks')): ?>
                    <a href="tasks.php" class="menu-item">
                        <i class="fas fa-tasks"></i>
                        <span>المهام اليومية</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('view_reports')): ?>
                    <a href="daily_report.php" class="menu-item">
                        <i class="fas fa-file-alt"></i>
                        <span>التقارير</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (hasPermission('view_financial')): ?>
            <div class="section">
                <h2>الشؤون المالية</h2>
                <div class="menu-items">
                    <?php if (hasPermission('view_transportation')): ?>
                    <a href="transportation.php" class="menu-item">
                        <i class="fas fa-bus"></i>
                        <span>مصاريف المواصلات</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('view_attendance')): ?>
                    <a href="attendance.php" class="menu-item">
                        <i class="fas fa-calendar-check"></i>
                        <span>الحضور والغياب</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('view_loans')): ?>
                    <div class="menu-item coming-soon">
                        <i class="fas fa-hand-holding-usd"></i>
                        <span>العهد والسلف</span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('view_salaries')): ?>
                    <div class="menu-item coming-soon">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>الرواتب</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // يمكن إضافة أي سلوك JavaScript إضافي هنا
        console.log('Dashboard loaded successfully');
    });
    </script>
</body>
</html>