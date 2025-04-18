<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// تحديد الحد الأقصى للتأخير (بالأيام) قبل احتساب إنذار
define('WARNING_THRESHOLD', 1);

// الحصول على التاريخ والوقت الحاليين - مع تحديد التوقيت المحدد
date_default_timezone_set('Africa/Cairo');
$utc_datetime = '2025-04-17 21:39:51'; // التوقيت UTC المحدد
$current_datetime = date('Y-m-d H:i:s', strtotime($utc_datetime));
$current_date = date('Y-m-d', strtotime($current_datetime));
$current_time = date('H:i:s', strtotime($current_datetime));

// بيانات المستخدم الحالي
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username']; // a22abcd

try {
    // الحصول على التواريخ التي لها تقارير
    $stmt = $db->prepare("
        SELECT report_date 
        FROM daily_reports 
        WHERE employee_id = ? 
        ORDER BY report_date DESC
    ");
    $stmt->execute([$user_id]);
    $submittedDates = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'report_date');

    // الحصول على المهام الحالية للمستخدم (بما فيها المؤرشفة)
    $stmt = $db->prepare("
        SELECT t.id, t.title, t.description, t.start_date, t.status,
               t.difficulty_level, t.duration, t.is_archived,
               t.completed_at
        FROM tasks t
        WHERE t.assigned_to = ?
        ORDER BY t.start_date ASC, t.is_archived ASC
    ");
    $stmt->execute([$user_id]);
    $userTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // الحصول على عدد الإنذارات الحالية للمستخدم
    $stmt = $db->prepare("
        SELECT COUNT(*) as warning_count 
        FROM warnings w 
        JOIN daily_reports d ON w.daily_report_id = d.id 
        WHERE d.employee_id = ? 
        AND w.warning_type = 'delay'
    ");
    $stmt->execute([$user_id]);
    $activeWarnings = $stmt->fetch(PDO::FETCH_ASSOC)['warning_count'];

} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $submittedDates = [];
    $activeWarnings = 0;
    $userTasks = [];
}

// إضافة المهام للصفحة كمتغير JavaScript
$tasksJson = json_encode($userTasks);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقرير اليومي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .report-status {
            position: absolute;
            top: 10px;
            left: 10px;
        }
        .time-display {
            font-size: 1.2em;
            color: #666;
            margin-bottom: 20px;
        }
        .warning-badge {
            background-color: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .work-conditions-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 20px;
        }
        .allowances-summary {
            background-color: #f8f9fa;
            border-left: 4px solid #28a745;
        }
        .task-item {
            border: 1px solid #e9ecef;
            padding: 15px;
            border-radius: 8px;
            background-color: #fff;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .task-item:hover {
            background-color: #f8f9fa;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .archived-task {
            background-color: #f8f9fa;
            border-color: #dee2e6;
            opacity: 0.8;
        }
        .archived-task:hover {
            opacity: 1;
        }
        .task-title {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.1em;
        }
        .task-description {
            color: #6c757d;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .task-meta {
            font-size: 0.85em;
        }
        .task-badge {
            font-size: 0.8em;
            padding: 6px 12px;
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-tasks"></i>
                نظام إدارة المهام والتقارير
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-item nav-link" href="dashboard.php">
                    <i class="fas fa-home"></i>
                    الرئيسية
                </a>
                <span class="nav-item nav-link">
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($username); ?>
                    <?php if($activeWarnings > 0): ?>
                        <span class="warning-badge">
                            <?php echo $activeWarnings; ?> إنذار
                        </span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <!-- بطاقة التقرير الرئيسية -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-file-alt"></i>
                            التقرير اليومي
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- عرض التاريخ والوقت -->
                        <div class="time-display">
                            <i class="far fa-calendar-alt"></i>
                            التاريخ والوقت الحالي: <?php echo $current_datetime; ?>
                        </div>

                        <!-- اختيار التاريخ -->
                        <div class="mb-4">
                            <label class="form-label">اختر تاريخ التقرير</label>
                            <input type="date" class="form-control" id="reportDate" 
                                   max="<?php echo $current_date; ?>" 
                                   value="<?php echo $current_date; ?>"
                                   required>
                            <div id="warningAlert" class="alert alert-warning mt-2" style="display: none;"></div>
                        </div>

                        <!-- قسم المهام -->
                        <div class="mb-4">
                            <div id="tasksForDay" style="display: none;">
                                <div class="alert alert-info">
                                    <h6 class="alert-heading mb-2">
                                        <i class="fas fa-tasks"></i>
                                        مهامك لهذا اليوم:
                                    </h6>
                                    <div id="tasksList" class="mt-2"></div>
                                </div>
                            </div>
                        </div>

                        <!-- نموذج التقرير -->
                        <div id="reportForm">
                            <form id="dailyReportForm">
                                <!-- ظروف العمل -->
                                <div class="card work-conditions-card">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-cog"></i>
                                            ظروف العمل
                                        </h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="requiresOvernight" name="work_conditions[overnight]">
                                                    <label class="form-check-label" for="requiresOvernight">
                                                        <i class="fas fa-moon text-primary"></i>
                                                        تتطلب ظروف العمل المبيت
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="requiresMeal" name="work_conditions[meal]">
                                                    <label class="form-check-label" for="requiresMeal">
                                                        <i class="fas fa-utensils text-success"></i>
                                                        تستحق صرف بدل وجبة
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="longTravel" name="work_conditions[long_travel]">
                                                    <label class="form-check-label" for="longTravel">
                                                        <i class="fas fa-car text-info"></i>
                                                        يتطلب سفر يزيد عن 5 ساعات
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="longDistance" name="work_conditions[long_distance]">
                                                    <label class="form-check-label" for="longDistance">
                                                        <i class="fas fa-road text-warning"></i>
                                                        يبعد أكثر من 100 كيلومتر
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- التفاصيل الإضافية -->
                                        <div class="mt-3" id="additionalDetails" style="display: none;">
                                            <hr>
                                            <div class="row g-3">
                                                <div class="col-md-6" id="overnightDetails" style="display: none;">
                                                    <div class="form-group">
                                                        <label class="form-label">مكان المبيت</label>
                                                        <input type="text" class="form-control" 
                                                               name="overnight_location" 
                                                               placeholder="اسم الفندق أو مكان المبيت">
                                                    </div>
                                                </div>
                                                <div class="col-md-6" id="travelDetails" style="display: none;">
                                                    <div class="form-group">
                                                        <label class="form-label">مسافة السفر (كم)</label>
                                                        <input type="number" class="form-control" 
                                                               name="travel_distance" 
                                                               placeholder="المسافة بالكيلومترات">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- محتوى التقرير -->
                                <div class="mb-3">
                                    <label class="form-label">محتوى التقرير</label>
                                    <textarea class="form-control" id="reportContent" 
                                              rows="10" required
                                              placeholder="اكتب تقريرك اليومي هنا..."></textarea>
                                </div>

                                <!-- ملخص البدلات -->
                                <div class="alert alert-info allowances-summary" 
                                     id="allowancesSummary" style="display: none;">
                                    <h6 class="alert-heading">
                                        <i class="fas fa-info-circle"></i>
                                        ملخص البدلات المستحقة:
                                    </h6>
                                    <ul class="list-unstyled mb-0" id="allowancesList"></ul>
                                </div>

                                <!-- زر الإرسال -->
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                    إرسال التقرير
                                </button>
                            </form>
                        </div>

                        <!-- عرض التقرير الموجود -->
                        <div id="existingReport" style="display: none;">
                            <div class="alert alert-info">
                                <h5>تقرير اليوم المحدد</h5>
                                <p id="existingReportContent"></p>
                                <div class="mt-2" id="existingReportStatus"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- التقارير السابقة -->
                <div class="card mt-4 shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history"></i>
                            التقارير السابقة
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="previousReports"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // تعريف المتغيرات العامة
    const submittedDates = <?php echo json_encode($submittedDates); ?>;
    const WARNING_THRESHOLD = <?php echo WARNING_THRESHOLD; ?>;
    const userTasks = <?php echo $tasksJson; ?>;
    const currentDate = '<?php echo $current_date; ?>';

    // دوال المساعدة
    function getStatusArabic(status) {
        const translations = {
            'pending': 'قيد الانتظار',
            'in_progress': 'قيد التنفيذ',
            'completed': 'مكتملة',
            'delayed': 'متأخرة'
        };
        return translations[status] || status;
    }

    function getStatusBadgeClass(status) {
        const classes = {
            'pending': 'bg-warning',
            'in_progress': 'bg-info',
            'completed': 'bg-success',
            'delayed': 'bg-danger'
        };
        return classes[status] || 'bg-secondary';
    }

    function getStatusBadge(status) {
        const badges = {
            'pending': '<span class="badge bg-warning">قيد المراجعة</span>',
            'approved': '<span class="badge bg-success">معتمد</span>',
            'rejected': '<span class="badge bg-danger">مرفوض</span>'
        };
        return badges[status] || status;
    }

    function formatDate(dateString) {
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        return new Date(dateString).toLocaleDateString('ar-SA', options);
    }

    function formatDateTime(datetime) {
        if (!datetime) return '';
        const options = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return new Date(datetime).toLocaleDateString('ar-SA', options);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function getStarsHtml(rating) {
        return '⭐'.repeat(rating);
    }

    function getWorkConditionsHtml(conditions) {
        if (!conditions) return '';
        
        try {
            const workConditions = typeof conditions === 'string' ? 
                JSON.parse(conditions) : conditions;
            
            const items = [];
            if (workConditions.overnight) items.push('يتطلب مبيت');
            if (workConditions.meal) items.push('يستحق بدل وجبة');
            if (workConditions.long_travel) items.push('سفر أكثر من 5 ساعات');
            if (workConditions.long_distance) items.push('مسافة أكثر من 100 كم');

            if (items.length === 0) return '';

            return `
                <div class="alert alert-secondary mt-2">
                    <strong>ظروف العمل:</strong>
                    <ul class="mb-0">
                        ${items.map(item => `<li>${item}</li>`).join('')}
                    </ul>
                    ${workConditions.overnight_location ? 
                        `<small>مكان المبيت: ${workConditions.overnight_location}</small><br>` : ''}
                    ${workConditions.travel_distance ? 
                        `<small>مسافة السفر: ${workConditions.travel_distance} كم</small>` : ''}
                </div>
            `;
        } catch (e) {
            console.error('Error parsing work conditions:', e);
            return '';
        }
    }

    // دالة تحميل التقارير السابقة
    function loadPreviousReports() {
        fetch('actions/report_actions.php?action=get_user_reports')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.reports) {
                    const reportsHtml = data.reports
                        .filter(report => report.report_date !== currentDate)
                        .map(report => `
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="report-status">
                                        ${getStatusBadge(report.status)}
                                    </div>
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        ${formatDate(report.report_date)}
                                    </h6>
                                    <p class="card-text">${report.content}</p>
                                    ${report.work_conditions ? getWorkConditionsHtml(report.work_conditions) : ''}
                                    ${report.admin_comment ? 
                                        `<div class="alert alert-info mt-2">
                                            <strong>تعليق المدير:</strong> ${report.admin_comment}
                                        </div>` : ''
                                    }
                                    ${report.rating ? 
                                        `<div class="mt-2">
                                            <strong>التقييم:</strong> ${getStarsHtml(report.rating)}
                                        </div>` : ''
                                    }
                                </div>
                            </div>
                        `).join('');
                    
                    document.getElementById('previousReports').innerHTML = 
                        reportsHtml || '<p class="text-muted text-center py-3">لا توجد تقارير سابقة</p>';
                } else {
                    throw new Error('لم يتم استلام بيانات من الخادم');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('previousReports').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        حدث خطأ في تحميل التقارير السابقة
                        <br>
                        <small class="d-block mt-2">
                            ${error.message === 'Network response was not ok' ? 
                                'خطأ في الاتصال بالخادم' : error.message}
                        </small>
                    </div>`;
            });
    }


// تحديث دالة عرض المهام حسب التاريخ
function showTasksForDate(selectedDate) {
    const tasksDiv = document.getElementById('tasksForDay');
    const tasksList = document.getElementById('tasksList');
    
    // تصفية المهام حسب التاريخ المحدد فقط
    const tasksForDate = userTasks.filter(task => {
        const taskStartDate = task.start_date;
        return taskStartDate === selectedDate;
    });
    
    if (tasksForDate.length > 0) {
        const tasksHtml = tasksForDate.map(task => `
            <div class="task-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="task-title">
                            <i class="fas fa-tasks text-primary me-2"></i>
                            ${escapeHtml(task.title)}
                        </div>
                        ${task.description ? 
                            `<div class="task-description mt-1">${escapeHtml(task.description)}</div>` : 
                            ''}
                        <div class="task-meta mt-2">
                            <small class="text-muted">
                                <i class="fas fa-signal me-1"></i>
                                مستوى الصعوبة: ${task.difficulty_level}
                            </small>
                            <small class="text-muted ms-3">
                                <i class="fas fa-clock me-1"></i>
                                المدة: ${task.duration} يوم
                            </small>
                            ${task.completed_at ? 
                                `<small class="text-success ms-3">
                                    <i class="fas fa-check-circle me-1"></i>
                                    تم الإنجاز: ${formatDateTime(task.completed_at)}
                                </small>` : 
                                ''}
                        </div>
                    </div>
                    <span class="badge task-badge ${getStatusBadgeClass(task.status)}">
                        ${getStatusArabic(task.status)}
                    </span>
                </div>
            </div>
        `).join('');
        
        tasksList.innerHTML = tasksHtml;
        tasksDiv.style.display = 'block';
    } else {
        tasksList.innerHTML = `
            <div class="text-muted text-center py-3">
                <i class="fas fa-info-circle me-2"></i>
                لا توجد مهام مسندة لهذا اليوم
            </div>`;
        tasksDiv.style.display = 'block';
    }
}

    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.form-check-input');
        const additionalDetails = document.getElementById('additionalDetails');
        const overnightDetails = document.getElementById('overnightDetails');
        const travelDetails = document.getElementById('travelDetails');
        const allowancesSummary = document.getElementById('allowancesSummary');
        const allowancesList = document.getElementById('allowancesList');

        // دالة تحديث التفاصيل الإضافية
        function updateAdditionalDetails() {
            const hasOvernight = document.getElementById('requiresOvernight').checked;
            const hasLongTravel = document.getElementById('longTravel').checked;
            const hasLongDistance = document.getElementById('longDistance').checked;

            if (hasOvernight || hasLongTravel || hasLongDistance) {
                additionalDetails.style.display = 'block';
                overnightDetails.style.display = hasOvernight ? 'block' : 'none';
                travelDetails.style.display = (hasLongTravel || hasLongDistance) ? 'block' : 'none';
            } else {
                additionalDetails.style.display = 'none';
            }
        }

        // دالة تحديث ملخص البدلات
        function updateAllowancesSummary() {
            const conditions = {
                overnight: document.getElementById('requiresOvernight').checked,
                meal: document.getElementById('requiresMeal').checked,
                long_travel: document.getElementById('longTravel').checked,
                long_distance: document.getElementById('longDistance').checked
            };

            const allowances = [];
            if (conditions.overnight) allowances.push('بدل مبيت');
            if (conditions.meal) allowances.push('بدل وجبة');
            if (conditions.long_travel) allowances.push('بدل سفر');
            if (conditions.long_distance) allowances.push('بدل مسافة');

            if (allowances.length > 0) {
                allowancesList.innerHTML = allowances
                    .map(a => `<li><i class="fas fa-check text-success"></i> ${a}</li>`)
                    .join('');
                allowancesSummary.style.display = 'block';
            } else {
                allowancesSummary.style.display = 'none';
            }
        }

        // إضافة مستمعي الأحداث للخيارات
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                updateAdditionalDetails();
                updateAllowancesSummary();
            });
        });

        // معالج تغيير التاريخ
        document.getElementById('reportDate').addEventListener('change', function() {
            const selectedDate = this.value;
            const warningAlert = document.getElementById('warningAlert');
            const reportForm = document.getElementById('reportForm');
            const existingReport = document.getElementById('existingReport');
            
            // عرض المهام للتاريخ المحدد
            showTasksForDate(selectedDate);

            // التحقق من وجود تقرير سابق
            if (submittedDates.includes(selectedDate)) {
                fetch(`actions/report_actions.php?action=get_report&date=${selectedDate}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('existingReportContent').textContent = data.report.content;
                            document.getElementById('existingReportStatus').innerHTML = 
                                getStatusBadge(data.report.status);
                            reportForm.style.display = 'none';
                            existingReport.style.display = 'block';
                            warningAlert.style.display = 'none';
                        }
                    });
                return;
            }

            // معالجة التقرير الجديد
            existingReport.style.display = 'none';
            reportForm.style.display = 'block';

            const currentDate = new Date();
            const reportDate = new Date(selectedDate);
            const diffTime = Math.abs(currentDate - reportDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays > WARNING_THRESHOLD) {
                const warningCount = Math.ceil((diffDays - WARNING_THRESHOLD));
                warningAlert.innerHTML = `
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>تنبيه:</strong> هذا التقرير متأخر بـ ${diffDays} يوم.
                    <br>
                    سيتم تسجيل ${warningCount} إنذار${warningCount > 1 ? 'ات' : ''} على هذا التأخير.
                `;
                warningAlert.style.display = 'block';
            } else {
                warningAlert.style.display = 'none';
            }
        });

        // معالج إرسال التقرير
        document.getElementById('dailyReportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                report_date: document.getElementById('reportDate').value,
                content: document.getElementById('reportContent').value,
                work_conditions: {
                    overnight: document.getElementById('requiresOvernight').checked,
                    meal: document.getElementById('requiresMeal').checked,
                    long_travel: document.getElementById('longTravel').checked,
                    long_distance: document.getElementById('longDistance').checked,
                    overnight_location: document.querySelector('[name="overnight_location"]')?.value || '',
                    travel_distance: document.querySelector('[name="travel_distance"]')?.value || ''
                }
            };

            if (!confirm('هل أنت متأكد من إرسال التقرير؟')) {
                return;
            }

            fetch('actions/report_actions.php?action=submit_report', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('تم إرسال التقرير بنجاح');
                    location.reload();
                } else {
                    alert(data.error || 'حدث خطأ أثناء إرسال التقرير');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال بالخادم: ' + error.message);
            });
        });

        // تحميل التقارير السابقة
        loadPreviousReports();
        
        // عرض المهام لليوم الحالي
        showTasksForDate(currentDate);
    });
    </script>
</body>
</html>