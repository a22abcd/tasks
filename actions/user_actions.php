<?php
session_start();
require_once '../config/database.php';

// إضافة سجلات التصحيح
error_log("=== User Actions Debug ===");
error_log("Time: 2025-04-14 07:12:33");
error_log("User: " . ($_SESSION['username'] ?? 'Not logged in'));

if (!isset($_SESSION['user_id'])) {
    error_log("Error: Unauthorized access attempt");
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? '';
error_log("Requested action: " . $action);

$response = ['success' => false];

switch ($action) {
    case 'list':
        try {
            $query = "SELECT id, full_name, email FROM users WHERE role = 'employee' ORDER BY full_name";
            error_log("Executing query: " . $query);
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Found " . count($users) . " employees");
            
            $response = [
                'success' => true,
                'users' => $users,
                'timestamp' => '2025-04-14 07:12:33'
            ];
            
            error_log("Response: " . json_encode($response));
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $response['error'] = $e->getMessage();
        }
        break;
}

header('Content-Type: application/json');
echo json_encode($response);