<?php
<?php
// في بداية الملف
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';
require_once 'check_permissions.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// تحديث وعرض معلومات الصلاحيات
$userPermissions = checkAndLogPermissions();
echo "<!--";
var_dump($_SESSION);
echo "-->";
?>
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<!-- ... باقي الكود HTML كما هو ... -->

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

    <!-- القوائم الفرعية -->
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

<!-- ... باقي الكود JavaScript كما هو ... -->

    <script>
        // دالة لإظهار/إخفاء القوائم الفرعية
        function toggleSubMenu(id) {
            // إخفاء جميع القوائم الفرعية
            document.querySelectorAll('.sub-menu').forEach(menu => {
                menu.style.display = 'none';
            });
            
            // إظهار القائمة الفرعية المطلوبة
            const subMenu = document.getElementById(id);
            subMenu.style.display = subMenu.style.display === 'flex' ? 'none' : 'flex';
        }

        // إضافة معالج الأخطاء
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            console.log('Error: ' + msg + '\nURL: ' + url + '\nLine: ' + lineNo + '\nColumn: ' + columnNo + '\nError object: ' + JSON.stringify(error));
            return false;
        };
    </script>
</body>
</html>
