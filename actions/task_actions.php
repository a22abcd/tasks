<?php
session_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// الدوال المساعدة
function calculateTaskStatus($task) {
    if (!isset($task['days_passed']) || !isset($task['duration'])) {
        return 'in_progress';
    }
    if ($task['status'] === 'completed') {
        return $task['days_passed'] > $task['duration'] ? 'delayed' : 'on_time';
    }
    return $task['days_passed'] > $task['duration'] ? 'overdue' : 'in_progress';
}

function sendEmailNotification($db, $task_id, $user_id) {
    try {
        // الحصول على معلومات المستخدم
        $stmt = $db->prepare("SELECT email, full_name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // الحصول على معلومات المهمة
        $stmt = $db->prepare("SELECT title FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $task) {
            $to = $user['email'];
            $subject = 'مهمة جديدة: ' . $task['title'];
            $message = "مرحباً {$user['full_name']},\n\n";
            $message .= "تم تعيين مهمة جديدة لك: {$task['title']}\n";
            $message .= "يرجى تسجيل الدخول إلى النظام لمراجعة تفاصيل المهمة.\n\n";
            $message .= "مع التحية،\nنظام إدارة المهام";

            $headers = 'From: noreply@company.com' . "\r\n" .
                'Reply-To: noreply@company.com' . "\r\n" .
                'X-Mailer: PHP/' . phpversion();

            mail($to, $subject, $message, $headers);
        }
    } catch (Exception $e) {
        error_log('Email notification error: ' . $e->getMessage());
    }
}

// إعداد قاعدة البيانات
try {
    $database = new Database();
    $db = $database->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    error_log('Database connection error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? '';
$response = ['success' => false];

try {
    switch ($action) {
        case 'list':
            $include_archived = isset($_GET['include_archived']) && $_GET['include_archived'] == '1';
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $status = isset($_GET['status']) ? trim($_GET['status']) : '';
            $employee = isset($_GET['employee']) ? intval($_GET['employee']) : 0;
            
            $query = "SELECT t.*, 
                     u.full_name as assigned_to_name,
                     DATEDIFF(CURRENT_TIMESTAMP, t.start_date) as days_passed
                     FROM tasks t 
                     LEFT JOIN users u ON t.assigned_to = u.id 
                     WHERE 1=1";
            
            $params = array();
            
            if ($_SESSION['role'] === 'admin') {
                if ($search) {
                    $query .= " AND (t.title LIKE :search OR t.description LIKE :search)";
                    $params[':search'] = "%{$search}%";
                }
                
                if ($status) {
                    $query .= " AND t.status = :status";
                    $params[':status'] = $status;
                }
                
                if ($employee) {
                    $query .= " AND t.assigned_to = :employee";
                    $params[':employee'] = $employee;
                }
            } else {
                $query .= " AND t.assigned_to = :user_id";
                $params[':user_id'] = $_SESSION['user_id'];
            }
            
            $query .= " AND t.is_archived = :is_archived";
            $params[':is_archived'] = $include_archived ? 1 : 0;
            
            $query .= " ORDER BY t.created_at DESC";
            
            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            if ($stmt->execute()) {
                $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($tasks as &$task) {
                    $task['completion_status'] = calculateTaskStatus($task);
                    
                    // عدد التعليقات
                    $commentStmt = $db->prepare("SELECT COUNT(*) FROM task_comments WHERE task_id = ?");
                    $commentStmt->execute([$task['id']]);
                    $task['comments_count'] = $commentStmt->fetchColumn();
                }
                
                $response['success'] = true;
                $response['tasks'] = $tasks;
            }
            break;

        case 'view':
            if (!isset($_GET['task_id'])) {
                throw new Exception('Task ID is required');
            }

            $task_id = intval($_GET['task_id']);
            
            $query = "SELECT t.*, 
                     u.full_name as assigned_to_name,
                     DATEDIFF(CURRENT_TIMESTAMP, t.start_date) as days_passed
                     FROM tasks t 
                     LEFT JOIN users u ON t.assigned_to = u.id 
                     WHERE t.id = :task_id";
            
            if ($_SESSION['role'] !== 'admin') {
                $query .= " AND t.assigned_to = :user_id";
            }
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':task_id', $task_id);
            
            if ($_SESSION['role'] !== 'admin') {
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
            }
            
            $stmt->execute();
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($task) {
                // Get comments
                $commentStmt = $db->prepare(
                    "SELECT c.*, u.full_name as user_name 
                     FROM task_comments c 
                     LEFT JOIN users u ON c.user_id = u.id 
                     WHERE c.task_id = ? 
                     ORDER BY c.created_at DESC"
                );
                $commentStmt->execute([$task_id]);
                $task['comments'] = $commentStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get ratings if user is admin
                if ($_SESSION['role'] === 'admin') {
                    $ratingStmt = $db->prepare(
                        "SELECT r.*, u.full_name as rated_by_name 
                         FROM task_ratings r 
                         LEFT JOIN users u ON r.rated_by = u.id 
                         WHERE r.task_id = ? 
                         ORDER BY r.rated_at DESC"
                    );
                    $ratingStmt->execute([$task_id]);
                    $task['ratings'] = $ratingStmt->fetchAll(PDO::FETCH_ASSOC);
                }
                
                $task['completion_status'] = calculateTaskStatus($task);
                
                $response['success'] = true;
                $response['task'] = $task;
            } else {
                throw new Exception('Task not found');
            }
            break;

        case 'create':
            if ($_SESSION['role'] !== 'admin') {
                throw new Exception('Unauthorized');
            }

            $stmt = $db->prepare(
                "INSERT INTO tasks (title, description, difficulty_level, start_date, 
                                  duration, created_by, assigned_to, status) 
                 VALUES (:title, :description, :difficulty_level, :start_date, 
                         :duration, :created_by, :assigned_to, 'pending')"
            );
            
            $stmt->bindParam(':title', $_POST['title']);
            $stmt->bindParam(':description', $_POST['description']);
            $stmt->bindParam(':difficulty_level', $_POST['difficulty_level']);
            $stmt->bindParam(':start_date', $_POST['start_date']);
            $stmt->bindParam(':duration', $_POST['duration']);
            $stmt->bindParam(':created_by', $_SESSION['user_id']);
            $stmt->bindParam(':assigned_to', $_POST['assigned_to']);
            
            if ($stmt->execute()) {
                $task_id = $db->lastInsertId();
                sendEmailNotification($db, $task_id, $_POST['assigned_to']);
                $response['success'] = true;
                $response['task_id'] = $task_id;
            }
            break;

        case 'update_status':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['task_id'], $data['status'], $data['comment'])) {
                throw new Exception('Missing required fields');
            }

            $db->beginTransaction();
            
            $stmt = $db->prepare(
                "UPDATE tasks 
                 SET status = :status 
                 WHERE id = :task_id AND assigned_to = :user_id"
            );
            
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':task_id', $data['task_id']);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $stmt = $db->prepare(
                    "INSERT INTO task_comments (task_id, user_id, comment, created_at) 
                     VALUES (:task_id, :user_id, :comment, NOW())"
                );
                
                $stmt->bindParam(':task_id', $data['task_id']);
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $stmt->bindParam(':comment', $data['comment']);
                
                if ($stmt->execute()) {
                    $db->commit();
                    $response['success'] = true;
                } else {
                    throw new Exception('Failed to add comment');
                }
            } else {
                throw new Exception('Failed to update status');
            }
            break;

        case 'archive_task':
            if ($_SESSION['role'] !== 'admin') {
                throw new Exception('Unauthorized');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $db->prepare("UPDATE tasks SET is_archived = 1 WHERE id = ?");
            if ($stmt->execute([$data['task_id']])) {
                $response['success'] = true;
            }
            break;

        case 'reassign_task':
            if ($_SESSION['role'] !== 'admin') {
                throw new Exception('Unauthorized');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['task_id'], $data['new_employee_id'])) {
                throw new Exception('Missing required fields');
            }

            $db->beginTransaction();
            
            $stmt = $db->prepare(
                "UPDATE tasks 
                 SET assigned_to = :new_employee_id,
                     status = 'pending',
                     updated_at = NOW()
                 WHERE id = :task_id"
            );
            
            $stmt->bindParam(':task_id', $data['task_id']);
            $stmt->bindParam(':new_employee_id', $data['new_employee_id']);
            
            if ($stmt->execute()) {
                // Add system comment
                $stmt = $db->prepare(
                    "INSERT INTO task_comments (task_id, user_id, comment, created_at)
                     VALUES (:task_id, :user_id, 'تم إعادة تعيين المهمة', NOW())"
                );
                
                $stmt->bindParam(':task_id', $data['task_id']);
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $stmt->execute();
                
                sendEmailNotification($db, $data['task_id'], $data['new_employee_id']);
                
                $db->commit();
                $response['success'] = true;
            }
            break;

        case 'submit_rating':
            if ($_SESSION['role'] !== 'admin') {
                throw new Exception('Unauthorized');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['task_id'], $data['rating'])) {
                throw new Exception('Missing required fields');
            }

            $stmt = $db->prepare("SELECT assigned_to FROM tasks WHERE id = ?");
            $stmt->execute([$data['task_id']]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$task) {
                throw new Exception('Task not found');
            }

            $db->beginTransaction();
            
            $stmt = $db->prepare(
                "INSERT INTO task_ratings (task_id, user_id, rating, rated_by, rated_at)
                 VALUES (:task_id, :user_id, :rating, :rated_by, NOW())"
            );
            
            $stmt->bindParam(':task_id', $data['task_id']);
            $stmt->bindParam(':user_id', $task['assigned_to']);
            $stmt->bindParam(':rating', $data['rating']);
            $stmt->bindParam(':rated_by', $_SESSION['user_id']);
            $stmt->execute();
            
            // Update daily ratings
            $stmt = $db->prepare(
                "INSERT INTO daily_ratings (user_id, rating_date, total_rating, tasks_count)
                 VALUES (:user_id, CURRENT_DATE, :rating, 1)
                 ON DUPLICATE KEY UPDATE 
                 total_rating = total_rating + :rating,
                 tasks_count = tasks_count + 1"
            );
            
            $stmt->bindParam(':user_id', $task['assigned_to']);
            $stmt->bindParam(':rating', $data['rating']);
            $stmt->execute();
            
            $db->commit();
            $response['success'] = true;
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Error in task_actions.php: ' . $e->getMessage());
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
exit;