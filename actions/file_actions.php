<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? '';
$response = ['success' => false];

switch ($action) {
    case 'create':
        try {
            $query = "INSERT INTO tasks (title, description, difficulty_level, start_date, duration, status, created_by, assigned_to, created_at) 
                     VALUES (:title, :description, :difficulty_level, :start_date, :duration, 'pending', :created_by, :assigned_to, :created_at)";
            
            $stmt = $db->prepare($query);
            
            $created_at = '2025-04-14 07:23:14'; // Current timestamp
            
            $stmt->bindParam(':title', $_POST['title']);
            $stmt->bindParam(':description', $_POST['description']);
            $stmt->bindParam(':difficulty_level', $_POST['difficulty_level']);
            $stmt->bindParam(':start_date', $_POST['start_date']);
            $stmt->bindParam(':duration', $_POST['duration']);
            $stmt->bindParam(':created_by', $_SESSION['user_id']);
            $stmt->bindParam(':assigned_to', $_POST['assigned_to']);
            $stmt->bindParam(':created_at', $created_at);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['task_id'] = $db->lastInsertId();
                
                // Log the successful creation
                error_log("New task created - ID: " . $response['task_id'] . " by user: " . $_SESSION['username']);
            }
        } catch (PDOException $e) {
            error_log("Error creating task: " . $e->getMessage());
            $response['error'] = $e->getMessage();
        }
        break;
}

header('Content-Type: application/json');
echo json_encode($response);