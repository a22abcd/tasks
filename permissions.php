<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/check_permissions.php';

// التحقق من أن المستخدم مدير
if (!isset($_SESSION['user_id']) || getUserRole($_SESSION['user_id']) !== 'admin') {
    header('Location: index.php');
    exit();
}

// معالجة تحديث الصلاحيات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_permissions'])) {
    $userId = $_POST['user_id'];
    $newRoleId = $_POST['role_id'];
    
    try {
        // تحديث دور المستخدم
        $stmt = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $newRoleId, $userId);
        $stmt->execute();
        
        $successMessage = "تم تحديث الصلاحيات بنجاح";
    } catch (Exception $e) {
        $errorMessage = "حدث خطأ أثناء تحديث الصلاحيات";
    }
}

// جلب قائمة المستخدمين مع أدوارهم
$users = [];
$query = "
    SELECT u.id, u.username, u.email, u.created_at, r.id as role_id, r.name as role_name
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    ORDER BY u.username
";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// جلب قائمة الأدوار
$roles = [];
$rolesQuery = "SELECT id, name, description FROM roles ORDER BY name";
$rolesResult = $conn->query($rolesQuery);
if ($rolesResult) {
    while ($row = $rolesResult->fetch_assoc()) {
        $roles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة صلاحيات المستخدمين</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --border-radius: 8px;
            --box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 15px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-link {
            text-decoration: none;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .users-table {
            width: 100%;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border-collapse: collapse;
            margin-top: 20px;
        }

        .users-table th,
        .users-table td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #eee;
        }

        .users-table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .role-select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            width: 100%;
        }

        .save-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        .save-btn:hover {
            opacity: 0.9;
        }

        .message {
            padding: 10px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>إدارة صلاحيات المستخدمين</h1>
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-right"></i>
                عودة للوحة التحكم
            </a>
        </div>

        <?php if (isset($successMessage)): ?>
            <div class="message success"><?php echo $successMessage; ?></div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="message error"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <table class="users-table">
            <thead>
                <tr>
                    <th>اسم المستخدم</th>
                    <th>البريد الإلكتروني</th>
                    <th>الدور الحالي</th>
                    <th>تاريخ الإنشاء</th>
                    <th>تغيير الدور</th>
                    <th>حفظ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <select name="role_id" class="role-select">
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>" 
                                            <?php echo ($role['id'] == $user['role_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($role['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                    </td>
                    <td>
                            <button type="submit" name="update_permissions" class="save-btn">
                                حفظ
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>