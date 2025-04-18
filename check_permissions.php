<?php
function getUserPermissions($userId) {
    global $conn;
    
    try {
        $permissions = [];
        
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
        
        return $permissions;
    } catch (Exception $e) {
        error_log("Error in getUserPermissions: " . $e->getMessage());
        return [];
    }
}

function hasPermission($permission) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    if (!isset($_SESSION['permissions'])) {
        $_SESSION['permissions'] = getUserPermissions($_SESSION['user_id']);
    }
    
    // المدير لديه جميع الصلاحيات
    if (in_array('admin', $_SESSION['role_names'] ?? [])) {
        return true;
    }
    
    return in_array($permission, $_SESSION['permissions']);
}