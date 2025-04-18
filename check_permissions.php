<?php
function getUserPermissions($userId) {
    global $conn;
    
    try {
        // تحقق من وجود اتصال قاعدة البيانات
        if (!$conn) {
            error_log("Database connection is missing");
            return [];
        }

        $permissions = [];
        
        // استعلام مباشر للحصول على الصلاحيات
        $query = "
            SELECT DISTINCT p.name 
            FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            JOIN users u ON u.role_id = rp.role_id
            WHERE u.id = ?
        ";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Error preparing statement: " . $conn->error);
            return [];
        }
        
        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            error_log("Error executing statement: " . $stmt->error);
            return [];
        }
        
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row['name'];
        }
        
        // حفظ الصلاحيات في السيشن
        $_SESSION['permissions'] = $permissions;
        
        return $permissions;
    } catch (Exception $e) {
        error_log("Error in getUserPermissions: " . $e->getMessage());
        return [];
    }
}

function hasPermission($permission) {
    // تحديث الصلاحيات إذا لم تكن موجودة
    if (!isset($_SESSION['permissions']) || empty($_SESSION['permissions'])) {
        $_SESSION['permissions'] = getUserPermissions($_SESSION['user_id']);
    }
    
    return in_array($permission, $_SESSION['permissions']);
}

// دالة للتحقق من الصلاحيات وعرض معلومات التصحيح
function checkAndLogPermissions() {
    if (isset($_SESSION['user_id'])) {
        $permissions = getUserPermissions($_SESSION['user_id']);
        error_log("User ID: " . $_SESSION['user_id']);
        error_log("Permissions: " . implode(", ", $permissions));
        return $permissions;
    }
    return [];
}
