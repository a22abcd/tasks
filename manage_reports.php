<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// التحقق من صلاحيات المدير
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// الحصول على قائمة الموظفين
$stmt = $db->prepare("
    SELECT id, full_name 
    FROM users 
    WHERE role = 'employee' 
    ORDER BY full_name
");
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// تحديد الفترة الافتراضية (الشهر الحالي)
$default_start_date = date('Y-m-01'); // أول يوم في الشهر الحالي
$default_end_date = date('Y-m-t');    // آخر يوم في الشهر الحالي
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة التقارير اليومية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .filters-card {
            border-left: 4px solid #0d6efd;
        }
        .employee-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }
        .stats-card {
            border-right: 4px solid;
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .report-card {
            border-left: 4px solid #6c757d;
            transition: all 0.3s;
        }
        .report-card.pending {
            border-left-color: #ffc107;
        }
        .report-card.approved {
            border-left-color: #198754;
        }
        .report-card.rejected {
            border-left-color: #dc3545;
        }
        .work-conditions {
            font-size: 0.9em;
            color: #666;
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
                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <!-- إحصائيات سريعة -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card" style="border-right-color: #0d6efd;">
                    <div class="card-body">
                        <h6 class="card-title">إجمالي التقارير</h6>
                        <h3 id="totalReports">-</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card" style="border-right-color: #ffc107;">
                    <div class="card-body">
                        <h6 class="card-title">قيد المراجعة</h6>
                        <h3 id="pendingReports">-</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card" style="border-right-color: #198754;">
                    <div class="card-body">
                        <h6 class="card-title">تقارير معتمدة</h6>
                        <h3 id="approvedReports">-</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card" style="border-right-color: #dc3545;">
                    <div class="card-body">
                        <h6 class="card-title">تقارير مرفوضة</h6>
                        <h3 id="rejectedReports">-</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- فلاتر البحث -->
        <div class="card filters-card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-filter"></i>
                    فلترة التقارير
                </h5>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">الموظف</label>
                        <select class="form-select" id="employeeFilter">
                            <option value="">الكل</option>
                            <?php foreach($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo htmlspecialchars($employee['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الفترة</label>
                        <input type="text" class="form-control" id="dateRange" 
                               value="<?php echo $default_start_date . ' - ' . $default_end_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الحالة</label>
                        <select class="form-select" id="statusFilter">
                            <option value="">الكل</option>
                            <option value="pending">قيد المراجعة</option>
                            <option value="approved">معتمد</option>
                            <option value="rejected">مرفوض</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ظروف العمل</label>
                        <select class="form-select" id="conditionFilter">
                            <option value="">الكل</option>
                            <option value="overnight">يتطلب مبيت</option>
                            <option value="meal">بدل وجبة</option>
                            <option value="long_travel">سفر طويل</option>
                            <option value="long_distance">مسافة بعيدة</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- قائمة التقارير -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-file-alt"></i>
                    التقارير اليومية
                </h5>
                <div class="btn-group">
                    <button class="btn btn-outline-primary" onclick="exportToExcel()">
                        <i class="fas fa-file-excel"></i>
                        تصدير Excel
                    </button>
                    <button class="btn btn-outline-danger" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf"></i>
                        تصدير PDF
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="reportsList"></div>
            </div>
        </div>
    </div>

    <!-- Modal مراجعة التقرير -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">مراجعة التقرير</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="reportId">
                    <div class="mb-3">
                        <label class="form-label">محتوى التقرير</label>
                        <div id="modalReportContent" class="border rounded p-3 bg-light"></div>
                    </div>
                    <div class="mb-3" id="modalWorkConditions"></div>
                    <div class="mb-3">
                        <label class="form-label">التقييم</label>
                        <div class="rating">
                            <i class="far fa-star" data-rating="1"></i>
                            <i class="far fa-star" data-rating="2"></i>
                            <i class="far fa-star" data-rating="3"></i>
                            <i class="far fa-star" data-rating="4"></i>
                            <i class="far fa-star" data-rating="5"></i>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">التعليق</label>
                        <textarea class="form-control" id="adminComment" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الحالة</label>
                        <select class="form-select" id="reportStatus">
                            <option value="approved">اعتماد</option>
                            <option value="rejected">رفض</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" onclick="submitReview()">حفظ المراجعة</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.min.js"></script>
    <script>
    // تهيئة محدد التاريخ
    $('#dateRange').daterangepicker({
        locale: {
            format: 'YYYY-MM-DD'
        },
        startDate: '<?php echo $default_start_date; ?>',
        endDate: '<?php echo $default_end_date; ?>'
    });

    // تحميل التقارير عند تغيير الفلاتر
    ['employeeFilter', 'statusFilter', 'conditionFilter'].forEach(id => {
        document.getElementById(id).addEventListener('change', loadReports);
    });
    $('#dateRange').on('apply.daterangepicker', loadReports);

    // تحميل التقارير
    function loadReports() {
        const filters = {
            employee_id: document.getElementById('employeeFilter').value,
            status: document.getElementById('statusFilter').value,
            condition: document.getElementById('conditionFilter').value,
            date_range: document.getElementById('dateRange').value
        };

        fetch('actions/report_actions.php?action=get_filtered_reports', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(filters)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStats(data.stats);
                displayReports(data.reports);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // تحديث الإحصائيات
    function updateStats(stats) {
        document.getElementById('totalReports').textContent = stats.total;
        document.getElementById('pendingReports').textContent = stats.pending;
        document.getElementById('approvedReports').textContent = stats.approved;
        document.getElementById('rejectedReports').textContent = stats.rejected;
    }

    // عرض التقارير
    function displayReports(reports) {
        const reportsHtml = reports.map(report => `
            <div class="card report-card mb-3 ${report.status}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="card-subtitle mb-2">
                                <img src="${report.employee_avatar}" alt="" class="employee-avatar me-2">
                                ${report.employee_name}
                            </h6>
                            <p class="text-muted mb-2">
                                <i class="far fa-calendar-alt"></i>
                                ${formatDate(report.report_date)}
                            </p>
                        </div>
                        <div>
                            ${getStatusBadge(report.status)}
                        </div>
                    </div>
                    <p class="card-text">${report.content}</p>
                    ${getWorkConditionsHtml(report.work_conditions)}
                    ${report.admin_comment ? `
                        <div class="alert alert-info mt-2">
                            <strong>تعليق المدير:</strong> ${report.admin_comment}
                        </div>
                    ` : ''}
                    ${report.rating ? `
                        <div class="mt-2">
                            <strong>التقييم:</strong> ${getStarsHtml(report.rating)}
                        </div>
                    ` : ''}
                    ${report.status === 'pending' ? `
                        <button class="btn btn-primary btn-sm mt-2" 
                                onclick="showReviewModal('${report.id}')">
                            <i class="fas fa-check"></i>
                            مراجعة التقرير
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');

        document.getElementById('reportsList').innerHTML = 
            reportsHtml || '<p class="text-muted">لا توجد تقارير مطابقة للفلتر</p>';
    }

    // دالة عرض modal المراجعة
    function showReviewModal(reportId) {
        fetch(`actions/report_actions.php?action=get_report&id=${reportId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('reportId').value = reportId;
                    document.getElementById('modalReportContent').textContent = data.report.content;
                    document.getElementById('modalWorkConditions').innerHTML = 
                        getWorkConditionsHtml(data.report.work_conditions);
                    
                    // تهيئة التقييم
                    const stars = document.querySelectorAll('.rating i');
                    stars.forEach(star => {
                        star.className = 'far fa-star';
                        star.addEventListener('click', function() {
                            const rating = this.dataset.rating;
                            stars.forEach(s => {
                                s.className = s.dataset.rating <= rating ? 
                                    'fas fa-star text-warning' : 'far fa-star';
                            });
                        });
                    });

                    new bootstrap.Modal(document.getElementById('reviewModal')).show();
                }
            });
    }

    // دالة حفظ المراجعة
    function submitReview() {
        const rating = document.querySelectorAll('.rating .fas').length;
        const data = {
            report_id: document.getElementById('reportId').value,
            status: document.getElementById('reportStatus').value,
            rating: rating,
            comment: document.getElementById('adminComment').value
        };

        fetch('actions/report_actions.php?action=review_report', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
                loadReports();
                alert('تم حفظ المراجعة بنجاح');
            } else {
                alert(data.error || 'حدث خطأ أثناء حفظ المراجعة');
            }
        });
    }

// تعديل دوال التصدير
function exportToExcel() {
    const filters = {
        ...getExportFilters(),
        format: 'excel'  // تحديد التنسيق بشكل صريح
    };
    const queryString = new URLSearchParams(filters).toString();
    window.location.href = `actions/export_reports.php?${queryString}`;
}

function exportToPDF() {
    const filters = {
        ...getExportFilters(),
        format: 'pdf'  // تحديد التنسيق بشكل صريح
    };
    const queryString = new URLSearchParams(filters).toString();
    window.location.href = `actions/export_reports.php?${queryString}`;
}

function getExportFilters() {
    return {
        employee_id: document.getElementById('employeeFilter').value,
        status: document.getElementById('statusFilter').value,
        condition: document.getElementById('conditionFilter').value,
        date_range: document.getElementById('dateRange').value
    };
}

    // دوال مساعدة
    function getStatusBadge(status) {
        const badges = {
            'pending': '<span class="badge bg-warning">قيد المراجعة</span>',
            'approved': '<span class="badge bg-success">معتمد</span>',
            'rejected': '<span class="badge bg-danger">مرفوض</span>'
        };
        return badges[status] || status;
    }

    function getStarsHtml(rating) {
        return '⭐'.repeat(rating);
    }

    function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { 
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    return date.toLocaleDateString('ar', options); // استخدام 'ar' بدلاً من 'ar-SA'
}

    function getWorkConditionsHtml(conditions) {
        if (!conditions) return '';
        
        const items = [];
        if (conditions.overnight) items.push('يتطلب مبيت');
        if (conditions.meal) items.push('يستحق بدل وجبة');
        if (conditions.long_travel) items.push('سفر أكثر من 5 ساعات');
        if (conditions.long_distance) items.push('مسافة أكثر من 100 كم');

        if (items.length === 0) return '';

        return `
            <div class="work-conditions">
                <i class="fas fa-info-circle"></i>
                ${items.join(' • ')}
                ${conditions.overnight_location ? 
                    `<br><small>مكان المبيت: ${conditions.overnight_location}</small>` : ''}
                ${conditions.travel_distance ? 
                    `<br><small>مسافة السفر: ${conditions.travel_distance} كم</small>` : ''}
            </div>
        `;
    }

    // تحميل التقارير عند تحميل الصفحة
    loadReports();
    </script>
</body>
</html>