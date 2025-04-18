<?php
session_start();
require_once '../config/database.php';

// تفعيل عرض الأخطاء للتطوير
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ضبط المنطقة الزمنية للقاهرة (مصر)
date_default_timezone_set('Africa/Cairo');

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? '';
$response = ['success' => false];

try {
    switch ($action) {
        case 'submit_report':
            $data = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('خطأ في تنسيق البيانات: ' . json_last_error_msg());
            }

            // التحقق من البيانات المطلوبة
            if (empty($data['report_date']) || empty($data['content'])) {
                throw new Exception('جميع الحقول المطلوبة يجب ملؤها');
            }

            // التحقق من وجود تقرير سابق
            $stmt = $db->prepare("SELECT id FROM daily_reports WHERE employee_id = ? AND report_date = ?");
            $stmt->execute([$_SESSION['user_id'], $data['report_date']]);
            if ($stmt->fetch()) {
                throw new Exception('يوجد تقرير مسجل لهذا اليوم بالفعل');
            }

            $db->beginTransaction();

            // الحصول على التوقيت الحالي
            $currentDateTime = date('Y-m-d H:i:s');

            // إضافة التقرير مع ظروف العمل
            $stmt = $db->prepare("
                INSERT INTO daily_reports (
                    employee_id, report_date, edit_date, submit_date,
                    content, work_conditions, status, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, 'pending', ?
                )
            ");
            
            $success = $stmt->execute([
                $_SESSION['user_id'],
                $data['report_date'],
                $currentDateTime,
                $currentDateTime,
                $data['content'],
                json_encode($data['work_conditions'] ?? null),
                $currentDateTime
            ]);

            if (!$success) {
                throw new Exception('فشل في حفظ التقرير: ' . implode(", ", $stmt->errorInfo()));
            }

            $reportId = $db->lastInsertId();

            // حساب وتسجيل الإنذارات إذا كان التقرير متأخراً
            $reportDate = new DateTime($data['report_date']);
            $currentDate = new DateTime($currentDateTime);
            $diff = $currentDate->diff($reportDate);
            $daysLate = $diff->days;

            if ($daysLate > 1) { // WARNING_THRESHOLD = 1
                $stmt = $db->prepare("
                    INSERT INTO warnings (
                        daily_report_id, warning_date, warning_type,
                        description, issued_by
                    ) VALUES (
                        ?, ?, 'delay', ?, ?
                    )
                ");
                
                if (!$stmt->execute([
                    $reportId,
                    $currentDateTime,
                    "تأخير في تسليم التقرير لمدة {$daysLate} يوم",
                    $_SESSION['user_id']
                ])) {
                    throw new Exception('فشل في تسجيل الإنذارات');
                }
            }

            $db->commit();
            $response['success'] = true;
            $response['message'] = 'تم حفظ التقرير بنجاح';
            break;

        case 'get_tasks_by_date':
            if (!isset($_GET['date'])) {
                throw new Exception('التاريخ مطلوب');
            }

            $stmt = $db->prepare("
                SELECT 
                    title,
                    description,
                    status,
                    priority,
                    due_date,
                    created_at
                FROM tasks 
                WHERE assigned_to = ? 
                AND (
                    DATE(due_date) = ? 
                    OR DATE(created_at) = ?
                    OR (
                        status IN ('pending', 'in_progress') 
                        AND due_date <= ?
                    )
                )
                ORDER BY priority DESC, created_at DESC
            ");

            $stmt->execute([
                $_SESSION['user_id'], 
                $_GET['date'],
                $_GET['date'],
                $_GET['date']
            ]);
            
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // تحويل حالات المهام إلى العربية
            foreach ($tasks as &$task) {
                switch($task['status']) {
                    case 'pending': $task['status'] = 'قيد الانتظار'; break;
                    case 'in_progress': $task['status'] = 'قيد التنفيذ'; break;
                    case 'completed': $task['status'] = 'مكتملة'; break;
                    case 'archived': $task['status'] = 'مؤرشفة'; break;
                }
                
                // تنسيق التواريخ
                $task['due_date'] = date('Y-m-d', strtotime($task['due_date']));
                $task['created_at'] = date('Y-m-d', strtotime($task['created_at']));
            }

            $response['success'] = true;
            $response['tasks'] = $tasks;
            break;

        case 'get_report':
            if (isset($_GET['id'])) {
                $stmt = $db->prepare("
                    SELECT r.*, u.full_name as employee_name 
                    FROM daily_reports r 
                    JOIN users u ON r.employee_id = u.id 
                    WHERE r.id = ?
                ");
                $stmt->execute([$_GET['id']]);
            } elseif (isset($_GET['date'])) {
                $stmt = $db->prepare("
                    SELECT * FROM daily_reports 
                    WHERE employee_id = ? AND report_date = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $_GET['date']]);
            } else {
                throw new Exception('يجب تحديد معرف التقرير أو التاريخ');
            }

            $report = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($report) {
                if (is_string($report['work_conditions'])) {
                    $report['work_conditions'] = json_decode($report['work_conditions'], true);
                }
                $response['success'] = true;
                $response['report'] = $report;
            } else {
                throw new Exception('التقرير غير موجود');
            }
            break;

        case 'get_user_reports':
            $stmt = $db->prepare("
                SELECT r.*, 
                       COUNT(w.id) as warnings_count
                FROM daily_reports r 
                LEFT JOIN warnings w ON w.daily_report_id = r.id
                WHERE r.employee_id = ? 
                GROUP BY r.id
                ORDER BY r.report_date DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($reports as &$report) {
                if (is_string($report['work_conditions'])) {
                    $report['work_conditions'] = json_decode($report['work_conditions'], true);
                }
            }

            $response['success'] = true;
            $response['reports'] = $reports;
            break;

        case 'get_filtered_reports':
            if ($_SESSION['role'] !== 'admin') {
                throw new Exception('غير مصرح لك بهذا الإجراء');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            $query = "
                SELECT r.*, 
                       u.full_name as employee_name,
                       COUNT(w.id) as warnings_count
                FROM daily_reports r 
                JOIN users u ON r.employee_id = u.id 
                LEFT JOIN warnings w ON w.daily_report_id = r.id
                WHERE 1=1
            ";
            $params = [];

            if (!empty($data['employee_id'])) {
                $query .= " AND r.employee_id = ?";
                $params[] = $data['employee_id'];
            }

            if (!empty($data['date_range'])) {
                $dates = explode(' - ', $data['date_range']);
                $query .= " AND r.report_date BETWEEN ? AND ?";
                $params[] = trim($dates[0]);
                $params[] = trim($dates[1]);
            }

            if (!empty($data['status'])) {
                $query .= " AND r.status = ?";
                $params[] = $data['status'];
            }

            if (!empty($data['condition'])) {
                $query .= " AND r.work_conditions->>'$.{$data['condition']}' = 'true'";
            }

            $query .= " GROUP BY r.id";
            $query .= " ORDER BY r.report_date DESC, r.created_at DESC";

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($reports as &$report) {
                if (is_string($report['work_conditions'])) {
                    $report['work_conditions'] = json_decode($report['work_conditions'], true);
                }
            }

            $stats = [
                'total' => count($reports),
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0
            ];

            foreach ($reports as $report) {
                $stats[$report['status']]++;
            }

            $response['success'] = true;
            $response['reports'] = $reports;
            $response['stats'] = $stats;
            break;

        case 'review_report':
            if ($_SESSION['role'] !== 'admin') {
                throw new Exception('غير مصرح لك بهذا الإجراء');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $db->prepare("
                UPDATE daily_reports 
                SET status = ?,
                    rating = ?,
                    admin_comment = ?,
                    reviewed_by = ?,
                    review_date = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            if (!$stmt->execute([
                $data['status'],
                $data['rating'],
                $data['comment'],
                $_SESSION['user_id'],
                $data['report_id']
            ])) {
                throw new Exception('فشل في تحديث التقرير');
            }

            $response['success'] = true;
            $response['message'] = 'تم تحديث التقرير بنجاح';
            break;

        default:
            throw new Exception('إجراء غير معروف');
    }
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    $response['error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_UNICODE);