// وظائف معالجة الإجراءات على المهام
function startTask(taskId) {
    updateTaskStatus(taskId, 'in_progress', 'تم بدء العمل على المهمة بنجاح');
}

function completeTask(taskId) {
    updateTaskStatus(taskId, 'completed', 'تم إكمال المهمة بنجاح');
}

function addNote(taskId) {
    const note = prompt('أدخل ملاحظتك حول المهمة:');
    if (note) {
        fetch('actions/task_actions.php?action=add_note', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                task_id: taskId,
                note: note
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('تم إضافة الملاحظة بنجاح');
                loadTasks();
            } else {
                alert('حدث خطأ أثناء إضافة الملاحظة');
            }
        });
    }
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