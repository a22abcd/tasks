<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // الموظفين وكلمات المرور الجديدة
    $employees = [
        [
            'username' => 'ahmed',
            'password' => 'password123',
            'full_name' => 'أحمد محمد'
        ],
        [
            'username' => 'sara',
            'password' => 'password123',
            'full_name' => 'سارة أحمد'
        ],
        [
            'username' => 'khalid',
            'password' => 'password123',
            'full_name' => 'خالد عبدالله'
        ]
    ];
    
    foreach ($employees as $employee) {
        // تحديث كلمة المرور
        $query = "UPDATE users SET password = :password WHERE username = :username AND role = 'employee'";
        $stmt = $db->prepare($query);
        
        $hashed_password = password_hash($employee['password'], PASSWORD_DEFAULT);
        
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':username', $employee['username']);
        
        if ($stmt->execute()) {
            echo "تم تحديث كلمة المرور للموظف: " . $employee['full_name'] . "<br>";
            echo "اسم المستخدم: " . $employee['username'] . "<br>";
            echo "كلمة المرور: password123<br><br>";
        }
    }
    
    // التحقق من الموظفين
    echo "<br>قائمة الموظفين في النظام:<br>";
    $query = "SELECT username, full_name, role FROM users WHERE role = 'employee'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "الاسم: " . $row['full_name'] . " | اسم المستخدم: " . $row['username'] . " | الدور: " . $row['role'] . "<br>";
    }
    
} catch(PDOException $e) {
    echo "خطأ: " . $e->getMessage();
}
?>