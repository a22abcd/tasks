 <?php
// معلومات الاتصال بقاعدة البيانات
$host = 'localhost';
$username = 'mecaiwbu_user2';
$password = '4hLPF]8J622D'; // استبدل بكلمة المرور الصحيحة
$database = 'mecaiwbu_tasks';
 
 
 
try {
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("عذراً، حدث خطأ في الاتصال بقاعدة البيانات");
}
?>