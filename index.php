<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$taskManager = new TaskManager($db);

// Get employees list
$query = "SELECT id, full_name FROM users WHERE role = 'employee' ORDER BY full_name";
$stmt = $db->prepare($query);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>






<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة المهام</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .btn-group .btn {
            margin-right: 2px;
        }
        .task-status {
            font-weight: bold;
        }
    </style>
</head>
<body>
     <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">نظام إدارة المهام</a>
            <div class="navbar-nav ms-auto">
                <span class="nav-item nav-link"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a class="nav-item nav-link" href="logout.php">تسجيل الخروج</a>
            </div>
        </div>
    </nav>



<div class="container mt-4">
    <?php if($auth->isAdmin()): ?>
    <!-- Admin Controls -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTaskModal">
                <i class="fas fa-plus"></i> مهمة جديدة
            </button>
            <button class="btn btn-secondary ms-2" onclick="toggleArchivedTasks()" data-show-archived="false">
                <i class="fas fa-archive"></i> عرض المهام المؤرشفة
            </button>
        </div>
    </div>

    <!-- Filter and Statistics Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="searchInput" placeholder="بحث في المهام...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="statusFilter">
                                <option value="">كل الحالات</option>
                                <option value="pending">قيد الانتظار</option>
                                <option value="in_progress">قيد التنفيذ</option>
                                <option value="completed">مكتملة</option>
                                <option value="archived">مؤرشفة</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="employeeFilter">
                                <option value="">كل الموظفين</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo htmlspecialchars($employee['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h6 class="card-title">إجمالي المهام</h6>
                                    <h3 class="card-text" id="totalTasks">0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h6 class="card-title">المهام المكتملة</h6>
                                    <h3 class="card-text" id="completedTasks">0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <h6 class="card-title">قيد التنفيذ</h6>
                                    <h3 class="card-text" id="inProgressTasks">0</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h6 class="card-title">متأخرة</h6>
                                    <h3 class="card-text" id="overdueTasks">0</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Task List -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">المهام</h5>
                </div>
                <div class="card-body">
                    <div id="taskList"></div>
                </div>
            </div>
        </div>
    </div>
</div>
    
    
    
    

    <!-- New Task Modal -->
    <div class="modal fade" id="newTaskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة مهمة جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="newTaskForm">
                        <div class="mb-3">
                            <label class="form-label">عنوان المهمة</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea class="form-control" name="description" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">درجة الصعوبة</label>
                            <select class="form-control" name="difficulty_level" required>
                                <option value="1">سهلة</option>
                                <option value="2">متوسطة</option>
                                <option value="3">صعبة</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">تاريخ البدء</label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">المدة (بالأيام)</label>
                            <input type="number" class="form-control" name="duration" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">تعيين إلى</label>
                            <select class="form-control" name="assigned_to" required>
                                <option value="">اختر موظفاً...</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo $employee['id']; ?>">
                                        <?php echo htmlspecialchars($employee['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" id="saveTask">حفظ</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Details Modal -->
<div class="modal fade" id="taskDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تفاصيل المهمة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="taskDetailsContent">
                    <!-- المحتوى سيتم تحديثه ديناميكياً -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

    <!-- Comment Modal -->
<div class="modal fade" id="commentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة تعليق</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="taskIdInput">
                <input type="hidden" id="statusInput">
                <div class="mb-3">
                    <label class="form-label">التعليق <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="commentInput" rows="3" required></textarea>
                    <div class="form-text text-danger">* التعليق مطلوب</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" onclick="saveTaskUpdate()">حفظ</button>
            </div>
        </div>
    </div>
</div>

    <!-- Rating Modal -->
    <div class="modal fade" id="ratingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تقييم أداء الموظف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ratingTaskId">
                    <input type="hidden" id="ratingAction">
                    <input type="hidden" id="ratingValue">
                    <div class="mb-3 text-center">
                        <label class="form-label d-block mb-3">تقييم الأداء</label>
                        <div class="rating-stars mb-2"></div>
                        <div class="rating-value">
                            التقييم المحدد: <span id="selectedRating">0</span> نجوم
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" onclick="submitRatingAndAction()">حفظ</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reassign Modal -->
<div class="modal fade" id="reassignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إعادة تعيين المهمة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="reassignTaskId">
                <div class="mb-3">
                    <label class="form-label">تعيين إلى</label>
                    <select class="form-control" id="reassignEmployee" required>
                        <option value="">اختر موظفاً...</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['id']; ?>">
                                <?php echo htmlspecialchars($employee['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" onclick="submitReassign()">حفظ</button>
            </div>
        </div>
    </div>
</div>
    
    
    
    
    
    
    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    

    
    document.addEventListener('DOMContentLoaded', function() {
        // Set default date to today
        const today = new Date().toISOString().split('T')[0];
        document.querySelector('input[name="start_date"]').value = today;

        // Handle form submission
        document.getElementById('saveTask').addEventListener('click', function() {
            const form = document.getElementById('newTaskForm');
            const formData = new FormData(form);

            fetch('actions/task_actions.php?action=create', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم إضافة المهمة بنجاح');
                    location.reload();
                } else {
                    alert('حدث خطأ أثناء إضافة المهمة');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء إضافة المهمة');
            });
        });

        // Load tasks
        loadTasks();
    });

    function loadTasks() {
        fetch('actions/task_actions.php?action=list')
        .then(response => response.json())
        .then(data => {
            const taskList = document.getElementById('taskList');
            if (data.success && data.tasks) {
                taskList.innerHTML = createTasksTable(data.tasks);
            } else {
                taskList.innerHTML = '<p>لا توجد مهام</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('taskList').innerHTML = '<p>حدث خطأ أثناء تحميل المهام</p>';
        });
    }

function createTasksTable(tasks) {
    if (tasks.length === 0) return '<p>لا توجد مهام</p>';

    let html = `
    <table class="table">
        <thead>
            <tr>
                <th>العنوان</th>
                <th>الموظف</th>
                <th>تاريخ البدء</th>
                <th>المدة</th>
                <th>الحالة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>`;

    const isAdmin = <?php echo $_SESSION['role'] === 'admin' ? 'true' : 'false'; ?>;
    
    tasks.forEach(task => {
        const isAssignedToCurrentUser = task.assigned_to == <?php echo $_SESSION['user_id']; ?>;
        const isArchived = task.is_archived == 1;
        
        html += `
        <tr>
            <td>${task.title}</td>
            <td>${task.assigned_to_name}</td>
            <td>${task.start_date}</td>
            <td>${task.duration} يوم</td>
            <td>${getStatusBadge(task.status)}</td>
            <td>
                <div class="btn-group" role="group">`;

        if (!isAdmin && isAssignedToCurrentUser && task.status !== 'completed' && !isArchived) {
            // أزرار الموظف - تظهر فقط للمهام غير المؤرشفة
            html += `
                <button class="btn btn-sm btn-primary" onclick="employeeUpdateStatus(${task.id}, 'completed')" title="تم التنفيذ">
                    <i class="fas fa-check"></i> تم التنفيذ
                </button>
                <button class="btn btn-sm btn-warning" onclick="employeeUpdateStatus(${task.id}, 'pending')" title="لم يتم التنفيذ">
                    <i class="fas fa-times"></i> لم يتم التنفيذ
                </button>`;
        } else if (isAdmin && !isArchived) {
            // أزرار المدير - تظهر فقط للمهام غير المؤرشفة
            html += `
                <button class="btn btn-sm btn-secondary" onclick="archiveTask(${task.id})" title="أرشفة المهمة">
                    <i class="fas fa-archive"></i> أرشفة
                </button>
                <button class="btn btn-sm btn-info" onclick="reassignTask(${task.id})" title="إعادة تعيين المهمة">
                    <i class="fas fa-redo"></i> إعادة
                </button>`;
        }

        // إضافة زر عرض التفاصيل لجميع المهام (اختياري)
        html += `
                    <button class="btn btn-sm btn-outline-primary" onclick="viewTask(${task.id})" title="عرض التفاصيل">
                        <i class="fas fa-eye"></i>
                    </button>`;

        html += `
                </div>
            </td>
        </tr>`;
    });

    html += '</tbody></table>';
    return html;
}



function employeeUpdateStatus(taskId, status) {
    const modal = new bootstrap.Modal(document.getElementById('commentModal'));
    document.getElementById('taskIdInput').value = taskId;
    document.getElementById('statusInput').value = status;
    
    // إضافة عنوان مناسب للنافذة
    const modalTitle = document.querySelector('#commentModal .modal-title');
    modalTitle.textContent = status === 'completed' ? 'تأكيد إتمام المهمة' : 'سبب عدم إتمام المهمة';
    
    // تحديث نص الزر
    const submitButton = document.querySelector('#commentModal .btn-primary');
    submitButton.textContent = 'حفظ';
    
    modal.show();
}

// تحديث دالة حفظ التعليق
function saveTaskUpdate() {
    const taskId = document.getElementById('taskIdInput').value;
    const status = document.getElementById('statusInput').value;
    const comment = document.getElementById('commentInput').value;

    if (!comment.trim()) {
        alert('يرجى إدخال تعليق');
        return;
    }

    fetch('actions/task_actions.php?action=update_status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            task_id: parseInt(taskId),
            status: status,
            comment: comment
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const commentModal = bootstrap.Modal.getInstance(document.getElementById('commentModal'));
            commentModal.hide();
            document.getElementById('commentInput').value = '';
            loadTasks();
            alert(status === 'completed' ? 'تم تحديث حالة المهمة إلى مكتملة' : 'تم تحديث حالة المهمة');
        } else {
            alert(data.error || 'حدث خطأ أثناء تحديث حالة المهمة');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ في الاتصال بالخادم');
    });
}



function toggleArchivedTasks() {
    const button = event.target.closest('button'); // للتأكد من الحصول على عنصر الزر نفسه
    const showArchived = button.getAttribute('data-show-archived') !== 'true';
    button.setAttribute('data-show-archived', showArchived);
    
    // تحديث نص الزر
    button.innerHTML = showArchived ? 
        '<i class="fas fa-list"></i> عرض المهام النشطة' : 
        '<i class="fas fa-archive"></i> عرض المهام المؤرشفة';
    
    // تحديث القائمة
    fetch(`actions/task_actions.php?action=list${showArchived ? '&include_archived=1' : ''}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            const taskList = document.getElementById('taskList');
            if (data.success && data.tasks) {
                taskList.innerHTML = createTasksTable(data.tasks);
            } else {
                taskList.innerHTML = '<p>لا توجد مهام</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء تحميل المهام');
        });
}



// دوال جديدة للمدير
// تعديل دالة archiveTask
function archiveTask(taskId) {
    if (confirm('هل تريد تقييم المهمة قبل الأرشفة؟')) {
        showRatingModal(taskId, 'archive');
    } else {
        executeArchive(taskId);
    }
}


function reassignTask(taskId) {
    if (confirm('هل تريد تقييم المهمة قبل إعادة التعيين؟')) {
        showRatingModal(taskId, 'reassign');
    } else {
        // إضافة نافذة اختيار موظف جديد
        const modal = new bootstrap.Modal(document.getElementById('reassignModal'));
        document.getElementById('reassignTaskId').value = taskId;
        modal.show();
    }
}



function submitRatingAndAction() {
    const rating = document.getElementById('ratingValue').value;
    const taskId = document.getElementById('ratingTaskId').value;
    const action = document.getElementById('ratingAction').value;
    
    if (!rating || rating === '0') {
        alert('الرجاء اختيار تقييم');
        return;
    }

    fetch('actions/task_actions.php?action=submit_rating', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            task_id: parseInt(taskId),
            rating: parseInt(rating),
            action: action
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const ratingModal = bootstrap.Modal.getInstance(document.getElementById('ratingModal'));
            ratingModal.hide();
            
            if (action === 'archive') {
                executeArchive(taskId);
            } else if (action === 'reassign') {
                // إظهار نافذة إعادة التعيين بعد التقييم
                const reassignModal = new bootstrap.Modal(document.getElementById('reassignModal'));
                document.getElementById('reassignTaskId').value = taskId;
                reassignModal.show();
            }
        } else {
            alert(data.error || 'حدث خطأ أثناء حفظ التقييم');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ في الاتصال بالخادم');
    });
}


function executeReassign(taskId) {
    fetch('actions/task_actions.php?action=reassign_task', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            task_id: parseInt(taskId)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('تم إعادة تعيين المهمة بنجاح');
            loadTasks();
        } else {
            alert(data.error || 'حدث خطأ أثناء إعادة تعيين المهمة');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ في الاتصال بالخادم');
    });
}



// تحديث دالة executeArchive
function executeArchive(taskId) {
    fetch('actions/task_actions.php?action=archive_task', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            task_id: parseInt(taskId)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadTasks();
        } else {
            alert(data.error || 'حدث خطأ أثناء أرشفة المهمة');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ في الاتصال بالخادم');
    });
}





// إضافة دالة جديدة للتحديث مع التعليق
function updateTaskWithComment(taskId, status) {
    const commentModal = new bootstrap.Modal(document.getElementById('commentModal'));
    document.getElementById('taskIdInput').value = taskId;
    document.getElementById('statusInput').value = status;
    commentModal.show();
}

// إضافة دالة لحفظ التحديث مع التعليق
function saveTaskUpdate() {
    const taskId = document.getElementById('taskIdInput').value;
    const status = document.getElementById('statusInput').value;
    const comment = document.getElementById('commentInput').value;

    if (!comment.trim()) {
        alert('يرجى إدخال تعليق');
        return;
    }

    fetch('actions/task_actions.php?action=update_status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            task_id: taskId,
            status: status,
            comment: comment
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const commentModal = bootstrap.Modal.getInstance(document.getElementById('commentModal'));
            commentModal.hide();
            document.getElementById('commentInput').value = '';
            loadTasks();
        } else {
            alert('حدث خطأ أثناء تحديث حالة المهمة');
        }
    });
}

   function getStatusBadge(status) {
    const statusMap = {
        'pending': '<span class="badge bg-warning">قيد الانتظار</span>',
        'in_progress': '<span class="badge bg-primary">قيد التنفيذ</span>',
        'completed': '<span class="badge bg-success">تم التنفيذ</span>',
        'archived': '<span class="badge bg-secondary">مؤرشف</span>'
    };
    return statusMap[status] || status;
}

    function requestHelp(taskId) {
        const reason = prompt('يرجى توضيح سبب طلب المساعدة:');
        if (reason) {
            fetch('actions/task_actions.php?action=request_help', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    task_id: taskId,
                    reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم إرسال طلب المساعدة بنجاح');
                } else {
                    alert('حدث خطأ أثناء إرسال طلب المساعدة');
                }
            });
        }
    }

    function updateTaskStatus(taskId, newStatus, successMessage) {
        fetch('actions/task_actions.php?action=update_status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                task_id: taskId,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(successMessage);
                loadTasks();
            } else {
                alert('حدث خطأ أثناء تحديث حالة المهمة');
            }
        });
    }
    
    
    
    function submitReassign() {
    const taskId = document.getElementById('reassignTaskId').value;
    const newEmployeeId = document.getElementById('reassignEmployee').value;

    if (!newEmployeeId) {
        alert('الرجاء اختيار موظف');
        return;
    }

    fetch('actions/task_actions.php?action=reassign_task', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            task_id: parseInt(taskId),
            new_employee_id: parseInt(newEmployeeId)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('reassignModal'));
            modal.hide();
            alert('تم إعادة تعيين المهمة بنجاح');
            loadTasks();
        } else {
            alert(data.error || 'حدث خطأ أثناء إعادة تعيين المهمة');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ في الاتصال بالخادم');
    });
}
    
    
    
function showRatingModal(taskId, action) {
    const modal = new bootstrap.Modal(document.getElementById('ratingModal'));
    document.getElementById('ratingTaskId').value = taskId;
    document.getElementById('ratingAction').value = action;
    document.getElementById('ratingValue').value = ''; // تصفير القيمة
    document.getElementById('selectedRating').textContent = '0';
    
    // تحديث عنوان النافذة حسب نوع الإجراء
    const modalTitle = document.querySelector('#ratingModal .modal-title');
    modalTitle.textContent = action === 'archive' ? 'تقييم المهمة قبل الأرشفة' : 'تقييم المهمة قبل إعادة التعيين';
    
    // تهيئة النجوم
    const starsContainer = document.querySelector('.rating-stars');
    starsContainer.innerHTML = '';
    
    // إضافة النجوم من 5 إلى 1
    for (let i = 5; i >= 1; i--) {
        const star = document.createElement('i');
        star.className = 'fas fa-star';
        star.style.cursor = 'pointer';
        star.setAttribute('data-value', i);
        
        star.addEventListener('mouseenter', function() {
            // إضاءة النجوم حتى النجمة الحالية
            const stars = starsContainer.querySelectorAll('i');
            const value = this.getAttribute('data-value');
            stars.forEach(s => {
                if (s.getAttribute('data-value') <= value) {
                    s.classList.add('hover');
                }
            });
        });
        
        star.addEventListener('mouseleave', function() {
            // إزالة تأثير التحويم
            const stars = starsContainer.querySelectorAll('i');
            stars.forEach(s => s.classList.remove('hover'));
        });
        
        star.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            document.getElementById('ratingValue').value = value;
            document.getElementById('selectedRating').textContent = value;
            
            // تحديث مظهر النجوم
            const stars = starsContainer.querySelectorAll('i');
            stars.forEach(s => {
                if (s.getAttribute('data-value') <= value) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });
        
        starsContainer.appendChild(star);
    }
    
    modal.show();
}
    

function viewTask(taskId) {
    console.log('Fetching task details for ID:', taskId); // للتصحيح

    fetch(`actions/task_actions.php?action=view&task_id=${taskId}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Response status:', response.status); // للتصحيح
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data); // للتصحيح
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to load task data');
        }

        const task = data.task;
        if (!task) {
            throw new Error('No task data received');
        }

        const modalContent = document.getElementById('taskDetailsContent');
        modalContent.innerHTML = `
            <div class="task-details">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">معلومات المهمة</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>العنوان</h6>
                                <p>${task.title || 'غير محدد'}</p>
                                
                                <h6>الوصف</h6>
                                <p>${task.description || 'لا يوجد وصف'}</p>
                                
                                <h6>الموظف المسؤول</h6>
                                <p>${task.assigned_to_name || 'غير محدد'}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>درجة الصعوبة</h6>
                                <p>${getDifficultyBadge(task.difficulty_level)}</p>
                                
                                <h6>تاريخ البدء</h6>
                                <p>${task.start_date || 'غير محدد'}</p>
                                
                                <h6>المدة المحددة</h6>
                                <p>${task.duration || '0'} يوم</p>
                                
                                <h6>الحالة</h6>
                                <p>${getStatusBadge(task.status)}</p>
                            </div>
                        </div>
                    </div>
                </div>

                ${task.comments && task.comments.length > 0 ? `
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">التعليقات</h5>
                        </div>
                        <div class="card-body">
                            ${task.comments.map(comment => `
                                <div class="comment-item">
                                    <p>${comment.comment}</p>
                                    <small class="text-muted">
                                        ${comment.user_name} - ${new Date(comment.created_at).toLocaleString('ar-SA')}
                                    </small>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}

                ${task.ratings && task.ratings.length > 0 ? `
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">التقييمات</h5>
                        </div>
                        <div class="card-body">
                            ${task.ratings.map(rating => `
                                <div class="rating-item">
                                    <div class="stars">
                                        ${createStarsHtml(rating.rating)}
                                    </div>
                                    <div class="rating-info">
                                        <small class="text-muted">
                                            تم التقييم بواسطة ${rating.rated_by_name}
                                            في ${new Date(rating.rated_at).toLocaleString('ar-SA')}
                                        </small>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
        
        const modal = new bootstrap.Modal(document.getElementById('taskDetailsModal'));
        modal.show();
    })
    .catch(error => {
        console.error('Error fetching task details:', error);
        alert('حدث خطأ أثناء تحميل تفاصيل المهمة: ' + error.message);
    });
}

function generateTaskDetailsHTML(task) {
    return `
        <div class="task-details">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">معلومات المهمة</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="fw-bold">العنوان</h6>
                                <p>${task.title || 'غير محدد'}</p>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold">الوصف</h6>
                                <p>${task.description || 'لا يوجد وصف'}</p>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold">الموظف المسؤول</h6>
                                <p>${task.assigned_to_name || 'غير محدد'}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="fw-bold">درجة الصعوبة</h6>
                                <p>${getDifficultyBadge(task.difficulty_level)}</p>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold">تاريخ البدء</h6>
                                <p>${formatDate(task.start_date) || 'غير محدد'}</p>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold">المدة المحددة</h6>
                                <p>${task.duration || '0'} يوم</p>
                            </div>
                            <div class="mb-3">
                                <h6 class="fw-bold">الحالة</h6>
                                <p>${getStatusBadge(task.status)}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            ${task.comments && task.comments.length > 0 ? `
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">التعليقات</h5>
                    </div>
                    <div class="card-body">
                        ${task.comments.map(comment => `
                            <div class="comment-item">
                                <p class="mb-1">${comment.comment}</p>
                                <small class="text-muted">
                                    ${comment.user_name} - ${formatDate(comment.created_at)}
                                </small>
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}

            ${task.ratings && task.ratings.length > 0 ? `
                <div class="card mb-3">
                    <div class="card-header bg-warning">
                        <h5 class="card-title mb-0">التقييمات</h5>
                    </div>
                    <div class="card-body">
                        ${task.ratings.map(rating => `
                            <div class="rating-item">
                                <div class="stars">
                                    ${createStarsHtml(rating.rating)}
                                </div>
                                <div class="rating-info">
                                    <small class="text-muted">
                                        تم التقييم بواسطة ${rating.rated_by_name}
                                        في ${formatDate(rating.rated_at)}
                                    </small>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}
        </div>
    `;
}

function formatDate(dateString) {
    if (!dateString) return 'غير محدد';
    try {
        return new Date(dateString).toLocaleDateString('ar-SA', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return dateString;
    }
}

function createStarsHtml(rating) {
    return Array(5).fill(0)
        .map((_, index) => `<i class="fas fa-star ${index < rating ? 'text-warning' : 'text-muted'}"></i>`)
        .join('');
}





// دوال مساعدة
function getCompletionStatusBadge(status) {
    const badges = {
        'delayed': '<span class="badge bg-danger">متأخر</span>',
        'on_time': '<span class="badge bg-success">في الوقت المحدد</span>',
        'overdue': '<span class="badge bg-warning">تجاوز المدة</span>',
        'in_progress': '<span class="badge bg-primary">قيد التنفيذ</span>'
    };
    return badges[status] || '';
}

function getDifficultyBadge(level) {
    const badges = {
        '1': '<span class="badge bg-success">سهلة</span>',
        '2': '<span class="badge bg-warning">متوسطة</span>',
        '3': '<span class="badge bg-danger">صعبة</span>'
    };
    return badges[level] || '';
}

function createStatusTimeline(history) {
    if (!history || history.length === 0) return '<p>لا يوجد تاريخ للتغييرات</p>';
    
    return history.map(item => `
        <div class="timeline-item">
            <div class="timeline-date">${formatDate(item.created_at)}</div>
            <div class="timeline-content">
                <p>${item.comment}</p>
                <small class="text-muted">بواسطة: ${item.user_name}</small>
            </div>
        </div>
    `).join('');
}

function createRatingsList(ratings) {
    return ratings.map(rating => `
        <div class="rating-item">
            <div class="stars">
                ${createStarsHtml(rating.rating)}
            </div>
            <div class="rating-info">
                <small class="text-muted">
                    تم التقييم بواسطة ${rating.rated_by_name} 
                    في ${formatDate(rating.rated_at)}
                </small>
            </div>
        </div>
    `).join('');
}

function createCommentsList(comments) {
    return comments.map(comment => `
        <div class="comment-item">
            <p>${comment.comment}</p>
            <small class="text-muted">
                ${comment.user_name} - ${formatDate(comment.created_at)}
            </small>
        </div>
    `).join('');
}





function calculateProgressPercentage(startDate, duration, completedAt) {
    const start = new Date(startDate);
    const end = completedAt ? new Date(completedAt) : new Date();
    const daysElapsed = Math.floor((end - start) / (1000 * 60 * 60 * 24));
    return Math.min(Math.round((daysElapsed / duration) * 100), 100);
}

function getProgressBarClass(status) {
    const classes = {
        'delayed': 'bg-danger',
        'on_time': 'bg-success',
        'overdue': 'bg-warning',
        'in_progress': 'bg-primary'
    };
    return classes[status] || 'bg-primary';
}


function getDifficultyText(level) {
    const difficulties = {
        '1': 'سهلة',
        '2': 'متوسطة',
        '3': 'صعبة'
    };
    return difficulties[level] || level;
}

    function getHelpRequestStatus(status) {
        const statusMap = {
            'pending': 'قيد الانتظار',
            'processed': 'تمت المعالجة',
            'completed': 'مكتمل'
        };
        return statusMap[status] || status;
    }
    
    // تحديث دالة loadTasks لتدعم البحث والتصفية
function loadTasks(filters = {}) {
    let url = 'actions/task_actions.php?action=list';
    
    const isAdmin = <?php echo $auth->isAdmin() ? 'true' : 'false'; ?>;
    
    if (isAdmin) {
        if (filters.search) url += `&search=${encodeURIComponent(filters.search)}`;
        if (filters.status) url += `&status=${encodeURIComponent(filters.status)}`;
        if (filters.employee) url += `&employee=${encodeURIComponent(filters.employee)}`;
        if (filters.showArchived) url += '&include_archived=1';
    }

    console.log('Fetching tasks from:', url); // للتصحيح

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Received data:', data); // للتصحيح
            if (data.success && data.tasks) {
                if (isAdmin) {
                    updateTaskStatistics(data.tasks);
                }
                document.getElementById('taskList').innerHTML = createTasksTable(data.tasks);
            } else {
                throw new Error(data.error || 'No tasks data received');
            }
        })
        .catch(error => {
            console.error('Error loading tasks:', error);
            document.getElementById('taskList').innerHTML = '<div class="alert alert-danger">حدث خطأ أثناء تحميل المهام: ' + error.message + '</div>';
        });
}



// دالة لتحديث إحصائيات المهام
function updateTaskStatistics(tasks) {
    const stats = {
        total: tasks.length,
        completed: tasks.filter(t => t.status === 'completed').length,
        inProgress: tasks.filter(t => t.status === 'in_progress').length,
        overdue: tasks.filter(t => {
            const deadline = new Date(t.start_date);
            deadline.setDate(deadline.getDate() + parseInt(t.duration));
            return new Date() > deadline && t.status !== 'completed';
        }).length
    };

    document.getElementById('totalTasks').textContent = stats.total;
    document.getElementById('completedTasks').textContent = stats.completed;
    document.getElementById('inProgressTasks').textContent = stats.inProgress;
    document.getElementById('overdueTasks').textContent = stats.overdue;
}

// إضافة مستمعي الأحداث للبحث والتصفية
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const employeeFilter = document.getElementById('employeeFilter');

    let searchTimeout;

    function applyFilters() {
        const filters = {
            search: searchInput.value,
            status: statusFilter.value,
            employee: employeeFilter.value,
            showArchived: document.querySelector('[data-show-archived]')?.getAttribute('data-show-archived') === 'true'
        };
        loadTasks(filters);
    }

    // البحث مع تأخير
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 300);
    });

    // تصفية فورية عند تغيير الحالة أو الموظف
    statusFilter.addEventListener('change', applyFilters);
    employeeFilter.addEventListener('change', applyFilters);
});
    
    
    </script>
</body>
</html>