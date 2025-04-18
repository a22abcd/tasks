<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== التحقق من قائمة الموظفين ===<br><br>";
    
    // 1. التحقق من وجود الموظفين في قاعدة البيانات
    $query = "SELECT id, username, full_name, role FROM users WHERE role = 'employee'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "الموظفون في قاعدة البيانات:<br>";
    foreach ($employees as $emp) {
        echo "ID: " . $emp['id'] . " | الاسم: " . $emp['full_name'] . " | الدور: " . $emp['role'] . "<br>";
    }
    
    // 2. التحقق من استجابة API
    echo "<br>=== اختبار استجابة API ===<br>";
    $test_query = "SELECT id, full_name, email FROM users WHERE role = 'employee' ORDER BY full_name";
    $test_stmt = $db->prepare($test_query);
    $test_stmt->execute();
    $api_response = [
        'success' => true,
        'users' => $test_stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
    
    echo "<pre>";
    print_r($api_response);
    echo "</pre>";
    
} catch(PDOException $e) {
    echo "خطأ في قاعدة البيانات: " . $e->getMessage();
}
?>