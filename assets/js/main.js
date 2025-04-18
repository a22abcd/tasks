// Add these functions to the existing main.js file

// File upload handling
function handleFileUpload(taskId, fileInput) {
    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    formData.append('task_id', taskId);
    
    fetch('actions/file_actions.php?action=upload', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            loadTaskAttachments(taskId);
            fileInput.value = '';
        } else {
            alert('حدث خطأ أثناء رفع الملف');
        }
    });
}




// Add or update this function in your main.js file
function loadEmployeesList() {
    fetch('actions/user_actions.php?action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.users) {
                const select = document.querySelector('select[name="assigned_to"]');
                if (select) {
                    // Clear existing options
                    select.innerHTML = '<option value="">اختر موظفاً...</option>';
                    
                    // Add employees to select
                    data.users.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.id;
                        option.textContent = user.full_name;
                        select.appendChild(option);
                    });
                }
            } else {
                console.error('Failed to load employees list');
            }
        })
        .catch(error => console.error('Error:', error));
}

// تحديث وظيفة تحميل قائمة الموظفين
function loadEmployeesList() {
    console.log('Loading employees list...');
    
    fetch('actions/user_actions.php?action=list')
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Received data:', data);
            
            if (data.success && data.users && Array.isArray(data.users)) {
                const select = document.querySelector('select[name="assigned_to"]');
                if (select) {
                    console.log('Found select element, updating options...');
                    
                    // مسح الخيارات الحالية
                    select.innerHTML = '<option value="">اختر موظفاً...</option>';
                    
                    // إضافة الموظفين
                    data.users.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.id;
                        option.textContent = user.full_name;
                        select.appendChild(option);
                        console.log('Added employee:', user.full_name);
                    });
                } else {
                    console.error('Select element not found');
                }
            } else {
                console.error('Invalid data format:', data);
            }
        })
        .catch(error => {
            console.error('Error loading employees:', error);
        });
}

// تحديث مستمعات الأحداث
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, setting up event listeners...');
    
    // تحميل قائمة الموظفين عند فتح نافذة المهمة الجديدة
    const newTaskModal = document.getElementById('newTaskModal');
    if (newTaskModal) {
        console.log('Found new task modal');
        
        newTaskModal.addEventListener('show.bs.modal', function() {
            console.log('Modal shown, loading employees...');
            loadEmployeesList();
        });
    } else {
        console.error('New task modal not found');
    }
});

// إضافة وظيفة للتحقق من حالة تحميل الموظفين
function checkEmployeesList() {
    const select = document.querySelector('select[name="assigned_to"]');
    if (select) {
        console.log('Current employees in select:', select.options.length - 1); // -1 for default option
        Array.from(select.options).forEach(option => {
            console.log('Option:', option.text, 'Value:', option.value);
        });
    }
}

// Make sure this is called when the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Your existing event listeners...
    
    // Load employees list when new task modal is shown
    const newTaskModal = document.getElementById('newTaskModal');
    if (newTaskModal) {
        newTaskModal.addEventListener('show.bs.modal', function() {
            loadEmployeesList();
        });
    }
});


// Load task attachments
function loadTaskAttachments(taskId) {
    fetch(`actions/file_actions.php?action=list&task_id=${taskId}`)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                const attachmentsList = document.querySelector(`#task-${taskId} .attachments-list`);
                attachmentsList.innerHTML = '';
                
                data.files.forEach(file => {
                    const fileLink = document.createElement('a');
                    fileLink.href = file.file_path;
                    fileLink.textContent = file.file_name;
                    fileLink.className = 'attachment-link';
                    fileLink.target = '_blank';
                    
                    attachmentsList.appendChild(fileLink);
                });
            }
        });
}

// Submit daily report
function submitDailyReport(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    fetch('actions/user_actions.php?action=daily_report', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('تم حفظ التقرير اليومي بنجاح');
            form.reset();
        } else {
            alert('حدث خطأ أثناء حفظ التقرير');
        }
    });
}

// Export tasks to Excel
function exportTasksToExcel() {
    fetch('actions/task_actions.php?action=export')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                window.location.href = data.file;
            } else {
                alert('حدث خطأ أثناء تصدير البيانات');
            }
        });
}

// Add event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Existing event listeners...
    
    const dailyReportForm = document.getElementById('dailyReportForm');
    if(dailyReportForm) {
        dailyReportForm.addEventListener('submit', submitDailyReport);
    }
    
    const exportButton = document.getElementById('exportTasks');
    if(exportButton) {
        exportButton.addEventListener('click', exportTasksToExcel);
    }
});