<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$auth = new Auth($db);
$isAdmin = $auth->isAdmin();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #0d6efd;
        }
        .stats-card {
            border-right: 4px solid #0d6efd;
        }
        .welcome-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0099ff 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
        }
        .user-info {
            text-align: left;
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
                <span class="nav-item nav-link">
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                </span>
                <a class="nav-item nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    تسجيل الخروج
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h2>مرحباً <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
            <p>
                <?php echo date('l') . ' ' . date('Y/m/d'); ?>
                <br>
                <?php echo $isAdmin ? 'مدير النظام' : 'موظف'; ?>
            </p>
        </div>

        <?php if ($isAdmin): ?>
        <!-- Admin Dashboard -->
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card dashboard-card h-100" onclick="window.location.href='index.php'">
                    <div class="card-body text-center">
                        <div class="card-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h5 class="card-title">إدارة المهام</h5>
                        <p class="card-text">إسناد ومراجعة مهام الموظفين</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card dashboard-card h-100" onclick="window.location.href='manage_reports.php'">
                    <div class="card-body text-center">
                        <div class="card-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h5 class="card-title">التقارير اليومية</h5>
                        <p class="card-text">مراجعة واعتماد تقارير الموظفين</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card dashboard-card h-100" onclick="window.location.href='employee_performance.php'">
                    <div class="card-body text-center">
                        <div class="card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h5 class="card-title">أداء الموظفين</h5>
                        <p class="card-text">متابعة وتقييم أداء الموظفين</p>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Employee Dashboard -->
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card dashboard-card h-100" onclick="window.location.href='index.php'">
                    <div class="card-body text-center">
                        <div class="card-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h5 class="card-title">المهام</h5>
                        <p class="card-text">إدارة المهام المسندة إليك</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card h-100" onclick="window.location.href='daily_report.php'">
                    <div class="card-body text-center">
                        <div class="card-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h5 class="card-title">التقرير اليومي</h5>
                        <p class="card-text">كتابة وإرسال التقرير اليومي</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card h-100" onclick="window.location.href='transportation.php'">
                    <div class="card-body text-center">
                        <div class="card-icon">
                            <i class="fas fa-car"></i>
                        </div>
                        <h5 class="card-title">المواصلات</h5>
                        <p class="card-text">تسجيل مصروفات المواصلات</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card h-100" onclick="window.location.href='attendance.php'">
                    <div class="card-body text-center">
                        <div class="card-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h5 class="card-title">الحضور والانصراف</h5>
                        <p class="card-text">تسجيل الحضور والانصراف</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Stats Section -->
        <div class="row mt-4">
            <div class="col-md-12">
                <h4 class="mb-3">إحصائيات سريعة</h4>
            </div>
            <?php if ($isAdmin): ?>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6>إجمالي الموظفين</h6>
                        <h3>25</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6>المهام النشطة</h6>
                        <h3>12</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6>التقارير اليوم</h6>
                        <h3>8</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6>الحضور اليوم</h6>
                        <h3>20</h3>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6>المهام النشطة</h6>
                        <h3>3</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6>المهام المكتملة</h6>
                        <h3>15</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6>التقارير المقدمة</h6>
                        <h3>25</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6>متوسط التقييم</h6>
                        <h3>4.5</h3>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>