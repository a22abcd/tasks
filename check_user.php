<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // First, let's check if the user exists
    $query = "SELECT * FROM users WHERE username = 'a22abcd'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "User found in database:<br>";
        echo "Username: " . $user['username'] . "<br>";
        echo "Role: " . $user['role'] . "<br>";
        echo "Created at: " . $user['created_at'] . "<br><br>";
    } else {
        echo "User not found. Creating new user...<br>";
        
        // Create new user
        $query = "INSERT INTO users (username, password, email, full_name, role, created_at) 
                  VALUES (:username, :password, :email, :full_name, :role, :created_at)";
        
        $stmt = $db->prepare($query);
        
        $username = 'a22abcd';
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $email = 'a22abcd@company.com';
        $full_name = 'مستخدم النظام';
        $role = 'admin';
        $created_at = '2025-04-14 03:23:10';
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':created_at', $created_at);
        
        if ($stmt->execute()) {
            echo "New user created successfully!<br>";
            echo "Username: a22abcd<br>";
            echo "Password: password123<br>";
        }
    }
    
    // Let's also verify the users table structure
    $query = "DESCRIBE users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    echo "<br>Table structure:<br>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>