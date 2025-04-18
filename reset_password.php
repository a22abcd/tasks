<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // User credentials
    $username = 'a22abcd';
    $new_password = 'password123';
    $current_time = '2025-04-14 06:42:49';
    
    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update user's password
    $query = "UPDATE users SET 
              password = :password,
              updated_at = :updated_at 
              WHERE username = :username";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':updated_at', $current_time);
    $stmt->bindParam(':username', $username);
    
    // Execute the query
    if ($stmt->execute()) {
        echo "تم تحديث كلمة المرور بنجاح!<br>";
        echo "اسم المستخدم: " . $username . "<br>";
        echo "كلمة المرور الجديدة: password123<br>";
        
        // Verify the update
        $verify_query = "SELECT * FROM users WHERE username = :username";
        $verify_stmt = $db->prepare($verify_query);
        $verify_stmt->bindParam(':username', $username);
        $verify_stmt->execute();
        
        if ($user = $verify_stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<br>تم التحقق من وجود المستخدم:<br>";
            echo "الاسم: " . $user['full_name'] . "<br>";
            echo "الدور: " . $user['role'] . "<br>";
            echo "آخر تحديث: " . $user['updated_at'] . "<br>";
        }
    } else {
        echo "حدث خطأ أثناء تحديث كلمة المرور.";
    }
    
} catch(PDOException $e) {
    echo "خطأ: " . $e->getMessage();
}
?>