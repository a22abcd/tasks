<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check user details
    $query = "SELECT id, username, password, email, full_name, role, created_at, updated_at 
              FROM users 
              WHERE username = :username";
    
    $stmt = $db->prepare($query);
    $username = 'a22abcd';
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "تفاصيل المستخدم:<br>";
        echo "ID: " . $user['id'] . "<br>";
        echo "اسم المستخدم: " . $user['username'] . "<br>";
        echo "البريد الإلكتروني: " . $user['email'] . "<br>";
        echo "الاسم الكامل: " . $user['full_name'] . "<br>";
        echo "الدور: " . $user['role'] . "<br>";
        echo "تاريخ الإنشاء: " . $user['created_at'] . "<br>";
        echo "آخر تحديث: " . $user['updated_at'] . "<br>";
        
        // Test password verification
        $test_password = 'password123';
        if (password_verify($test_password, $user['password'])) {
            echo "<br>كلمة المرور صحيحة!";
        } else {
            echo "<br>كلمة المرور غير صحيحة!";
        }
    } else {
        echo "المستخدم غير موجود!";
    }
    
} catch(PDOException $e) {
    echo "خطأ: " . $e->getMessage();
}
?>