<?php
// تضمين ملف الاتصال بقاعدة البيانات
include 'includes/config.php';

// التحقق من تسجيل الدخول
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] != true) {
    die(json_encode(['status' => 'error', 'message' => 'يجب تسجيل الدخول']));
}

// التأكد من وجود التاريخ والموظف
if (!isset($_GET['date']) || empty($_GET['date'])) {
    die(json_encode(['status' => 'error', 'message' => 'لم يتم تحديد التاريخ']));
}

// تنظيف المدخلات
$date = mysqli_real_escape_string($conn, $_GET['date']);
$user_id = $_SESSION['user_id'];

// استعلام للحصول على المهام المرتبطة بالموظف في التاريخ المحدد
$query = "SELECT t.id, t.title, t.description, t.due_date, t.priority, t.status 
          FROM tasks t 
          INNER JOIN task_assignments ta ON t.id = ta.task_id 
          WHERE ta.user_id = ? AND DATE(t.due_date) = ? 
          ORDER BY t.priority DESC, t.due_date ASC";

// استخدام prepared statement للأمان
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "is", $user_id, $date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$tasks = [];
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // تحويل حالة المهمة إلى نص عربي
        $status_text = '';
        switch ($row['status']) {
            case 'pending':
                $status_text = 'قيد الانتظار';
                break;
            case 'in_progress':
                $status_text = 'قيد التنفيذ';
                break;
            case 'completed':
                $status_text = 'مكتملة';
                break;
            case 'cancelled':
                $status_text = 'ملغاة';
                break;
        }
        
        // تحويل الأولوية إلى نص عربي
        $priority_text = '';
        switch ($row['priority']) {
            case 'high':
                $priority_text = 'عالية';
                break;
            case 'medium':
                $priority_text = 'متوسطة';
                break;
            case 'low':
                $priority_text = 'منخفضة';
                break;
        }
        
        $row['status_text'] = $status_text;
        $row['priority_text'] = $priority_text;
        $tasks[] = $row;
    }
}

// إرجاع النتائج كـ JSON
echo json_encode([
    'status' => 'success',
    'tasks' => $tasks,
    'count' => count($tasks)
]);