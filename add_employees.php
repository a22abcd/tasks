<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Array of employees to add
    $employees = [
        [
            'username' => 'ahmed',
            'password' => 'password123',
            'email' => 'ahmed@company.com',
            'full_name' => 'أحمد محمد',
            'role' => 'employee',
            'created_at' => '2025-04-14 06:53:01'
        ],
        [
            'username' => 'sara',
            'password' => 'password123',
            'email' => 'sara@company.com',
            'full_name' => 'سارة أحمد',
            'role' => 'employee',
            'created_at' => '2025-04-14 06:53:01'
        ],
        [
            'username' => 'khalid',
            'password' => 'password123',
            'email' => 'khalid@company.com',
            'full_name' => 'خالد عبدالله',
            'role' => 'employee',
            'created_at' => '2025-04-14 06:53:01'
        ]
    ];
    
    // First, check if each employee exists
    foreach ($employees as $employee) {
        $check_query = "SELECT id FROM users WHERE username = :username";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':username', $employee['username']);
        $check_stmt->execute();
        
        if (!$check_stmt->fetch()) {
            // Employee doesn't exist, add them
            $query = "INSERT INTO users (username, password, email, full_name, role, created_at) 
                      VALUES (:username, :password, :email, :full_name, :role, :created_at)";
            
            $stmt = $db->prepare($query);
            
            $hashed_password = password_hash($employee['password'], PASSWORD_DEFAULT);
            
            $stmt->bindParam(':username', $employee['username']);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':email', $employee['email']);
            $stmt->bindParam(':full_name', $employee['full_name']);
            $stmt->bindParam(':role', $employee['role']);
            $stmt->bindParam(':created_at', $employee['created_at']);
            
            $stmt->execute();
            echo "تم إضافة الموظف: " . $employee['full_name'] . "<br>";
        } else {
            echo "الموظف موجود مسبقاً: " . $employee['full_name'] . "<br>";
        }
    }
    
    // Now let's update the user_actions.php file to properly return the employee list
    echo "<br>تم إضافة الموظفين بنجاح!";
    
} catch(PDOException $e) {
    echo "خطأ: " . $e->getMessage();
}
?>