<?php
session_start();
require_once '../config/database.php';



// ضبط المنطقة الزمنية للقاهرة
date_default_timezone_set('Africa/Cairo');

// استخدام المتغيرات الديناميكية من الجلسة
$current_datetime = date('Y-m-d H:i:s');
$current_user = $_SESSION['username']; // استخدام اسم المستخدم من الجلسة

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized');
}



$database = new Database();
$db = $database->getConnection();

// استرجاع التقارير من قاعدة البيانات
try {
   $query = "
    SELECT 
        r.id,
        r.employee_id,
        u.full_name as employee_name,
        r.report_date,
        r.edit_date,
        r.content,
        r.status,
        r.rating,
        r.work_conditions,
        COALESCE(w.warning_count, 0) as warnings_count
    FROM 
        daily_reports r
    LEFT JOIN 
        users u ON r.employee_id = u.id
    LEFT JOIN (
        SELECT daily_report_id, COUNT(*) as warning_count 
        FROM warnings 
        GROUP BY daily_report_id
    ) w ON w.daily_report_id = r.id
";

    // إضافة فلترة حسب التاريخ
    if (isset($_GET['date_range'])) {
        $dates = explode(' - ', $_GET['date_range']);
        if (count($dates) == 2) {
            $query .= " WHERE r.report_date BETWEEN :start_date AND :end_date";
        }
    }

    $query .= " ORDER BY r.report_date DESC";

    $stmt = $db->prepare($query);

    // ربط معاملات التاريخ
    if (isset($dates) && count($dates) == 2) {
        $stmt->bindParam(':start_date', $dates[0]);
        $stmt->bindParam(':end_date', $dates[1]);
    }

    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// التحقق من نوع التصدير
$allowed_formats = ['excel', 'pdf', 'html'];
$format = in_array($_GET['format'] ?? 'html', $allowed_formats) ? $_GET['format'] : 'html';

if ($format === 'excel') {
    // تصدير Excel
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="تقرير_المتابعة_اليومية_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    ?>
    <!DOCTYPE html>
    <html dir="rtl" lang="ar">
    <head>
        <meta charset="UTF-8">
        <style>
            table {
                border-collapse: collapse;
                width: 100%;
            }
            td, th {
                text-align: center;
                vertical-align: middle;
                border: 1px solid #000;
                mso-number-format: "\@";
                padding: 4px;
            }
            .header {
                background-color: #f5f5f5;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <table>
            <tr class="header">
                <th colspan="10">ميكاتو شركة مساهمة مصرية</th>
            </tr>
            <tr class="header">
                <th colspan="10">تقرير المتابعة اليومية للموظفين</th>
            </tr>
            <tr>
                <th colspan="10">
                    الفترة: <?php echo $_GET['date_range'] ?? 'جميع الفترات'; ?> | 
                    التاريخ: <?php echo $current_datetime; ?> | 
                    المستخدم: <?php echo htmlspecialchars($current_user); ?>
                </th>
            </tr>
            <tr>
                <th>#</th>
                <th>الموظف</th>
                <th>تاريخ التقرير</th>
                <th>تاريخ التحرير</th>
                <th>أيام التأخير</th>
                <th>الإنذارات</th>
                <th>المحتوى</th>
                <th>الحالة</th>
                <th>التقييم</th>
                <th>ظروف العمل</th>
            </tr>
            <?php 
            $counter = 1;
            foreach ($reports as $report): 
                $report_date = new DateTime($report['report_date']);
                $edit_date = new DateTime($report['edit_date']);
                $delay_days = $report_date->diff($edit_date)->days;
                
                $work_conditions = json_decode($report['work_conditions'], true);
                $conditions = [];
                if ($work_conditions) {
                    if ($work_conditions['overnight']) $conditions[] = 'م';
                    if ($work_conditions['meal']) $conditions[] = 'و';
                    if ($work_conditions['long_travel']) $conditions[] = 'س';
                    if ($work_conditions['long_distance']) $conditions[] = 'ب';
                }
            ?>
                <tr>
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo htmlspecialchars($report['employee_name']); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($report['report_date'])); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($report['edit_date'])); ?></td>
                    <td><?php echo $delay_days > 0 ? $delay_days : '-'; ?></td>
                    <td><?php echo $report['warnings_count'] > 0 ? $report['warnings_count'] : '-'; ?></td>
                    <td><?php echo htmlspecialchars($report['content']); ?></td>
                    <td><?php echo getStatusInArabic($report['status']); ?></td>
                    <td><?php echo $report['rating'] ? str_repeat('*', $report['rating']) : ''; ?></td>
                    <td><?php echo implode('، ', $conditions); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </body>
    </html>
    <?php
    exit;
} else {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html dir="rtl" lang="ar">
    <head>
        <meta charset="UTF-8">
        <title>تقارير الموظفين - ميكاتو</title>
        <style>
            @page {
                margin: 10mm 10mm;
            }
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 10px;
                direction: rtl;
                position: relative;
                font-size: 12px;
            }
            .header {
                text-align: center;
                margin-bottom: 10px;
                border-bottom: 1px solid #0066cc;
                padding-bottom: 5px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .company-logo {
                width: 60px;
                height: auto;
                object-fit: contain;
            }
            .header-content {
                flex: 1;
            }
            .company-name {
                font-size: 16px;
                color: #0066cc;
                margin: 0;
            }
            .report-title {
                font-size: 14px;
                color: #333;
                margin: 2px 0;
            }
            .report-meta {
                display: flex;
                justify-content: space-between;
                font-size: 11px;
                color: #666;
                margin: 2px 0;
            }
            .report-meta p {
                margin: 0;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 5px 0;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 4px 6px;
                text-align: right;
                font-size: 11px;
            }
            th {
                background-color: #f5f5f5;
            }
            .text-danger {
                color: #dc3545;
                font-weight: bold;
            }
            td:nth-child(5), 
            td:nth-child(6) {
                text-align: center;
            }
            .footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                padding: 3px;
                border-top: 1px solid #ddd;
                font-size: 9px;
                color: #666;
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: white;
            }
            .footer span {
                margin: 0 5px;
            }
            .confidential {
                color: #ff0000;
                font-size: 9px;
            }
            @media print {
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <img src="<?php echo htmlspecialchars($logo_path); ?>" 
                 alt="ميكاتو" 
                 class="company-logo"
                 onerror="this.style.display='none'">
            <div class="header-content">
                <h1 class="company-name">ميكاتو شركة مساهمة مصرية</h1>
                <h2 class="report-title">تقرير المتابعة اليومية للموظفين</h2>
                <div class="report-meta">
                    <p>الفترة: <?php echo $_GET['date_range'] ?? 'جميع الفترات'; ?></p>
                    <p>التاريخ: <?php echo $current_datetime; ?></p>
                    <p>المستخدم: <?php echo htmlspecialchars($current_user); ?></p>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>الموظف</th>
                    <th>تاريخ التقرير</th>
                    <th>تاريخ التحرير</th>
                    <th>أيام التأخير</th>
                    <th>الإنذارات</th>
                    <th>المحتوى</th>
                    <th>الحالة</th>
                    <th>التقييم</th>
                    <th>ظروف العمل</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $counter = 1;
                foreach ($reports as $report): 
                    $report_date = new DateTime($report['report_date']);
                    $edit_date = new DateTime($report['edit_date']);
                    $delay_days = $report_date->diff($edit_date)->days;
                    
                    $work_conditions = json_decode($report['work_conditions'], true);
                    $conditions = [];
                    if ($work_conditions) {
                        if ($work_conditions['overnight']) $conditions[] = 'مبيت';
                        if ($work_conditions['meal']) $conditions[] = 'وجبة';
                        if ($work_conditions['long_travel']) $conditions[] = 'سفر';
                        if ($work_conditions['long_distance']) $conditions[] = 'مسافة';
                    }
                ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo htmlspecialchars($report['employee_name']); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($report['report_date'])); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($report['edit_date'])); ?></td>
                        <td><?php 
                            if ($delay_days > 0) {
                                echo "<span class='text-danger'>$delay_days يوم</span>";
                            } else {
                                echo "-";
                            }
                        ?></td>
                        <td><?php 
                            if ($report['warnings_count'] > 0) {
                                echo "<span class='text-danger'>{$report['warnings_count']}</span>";
                            } else {
                                echo "-";
                            }
                        ?></td>
                        <td><?php echo htmlspecialchars($report['content']); ?></td>
                        <td><?php echo getStatusInArabic($report['status']); ?></td>
                        <td><?php echo $report['rating'] ? str_repeat('★', $report['rating']) : ''; ?></td>
                        <td><?php echo implode('، ', $conditions); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="footer">
            <span class="confidential">وثيقة داخلية - سرية</span>
            <span>mecatoeg@gmail.com</span>
            <span>01005702582</span>
            <span>عقار18 ناصية شارع صلاح الدين – بنها</span>
            <span class="page-number"></span>
        </div>

        <script>
        window.onload = function() {
            var pages = document.querySelectorAll('.page-number');
            for(var i = 0; i < pages.length; i++) {
                pages[i].textContent = 'صفحة ' + (i + 1);
            }
            window.print();
        }
        </script>
    </body>
    </html>
    <?php
}

function getStatusInArabic($status) {
    $statuses = [
        'pending' => 'قيد المراجعة',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض'
    ];
    return $statuses[$status] ?? $status;
}