<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // إنشاء جدول الملاحظات
    $query = "CREATE TABLE IF NOT EXISTS task_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        user_id INT NOT NULL,
        note TEXT NOT NULL,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (task_id) REFERENCES tasks(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $db->exec($query);
    echo "تم إنشاء جدول الملاحظات بنجاح<br>";

    // إنشاء جدول طلبات المساعدة
    $query = "CREATE TABLE IF NOT EXISTS help_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        user_id INT NOT NULL,
        reason TEXT NOT NULL,
        status ENUM('pending', 'processed', 'completed') DEFAULT 'pending',
        created_at DATETIME NOT NULL,
        FOREIGN KEY (task_id) REFERENCES tasks(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $db->exec($query);
    echo "تم إنشاء جدول طلبات المساعدة بنجاح<br>";

} catch(PDOException $e) {
    echo "خطأ: " . $e->getMessage();
}
?>