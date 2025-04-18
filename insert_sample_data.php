<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // قراءة ملف SQL
    $sql = file_get_contents('sample_data.sql');
    
    // تقسيم الملف إلى عبارات SQL منفصلة
    $queries = explode(';', $sql);
    
    // تنفيذ كل استعلام
    foreach ($queries as $query) {
        if (trim($query) != '') {
            $db->exec($query);
        }
    }
    
    echo "تم إدخال البيانات التجريبية بنجاح!";
    
} catch(PDOException $e) {
    echo "خطأ: " . $e->getMessage();
}
?>