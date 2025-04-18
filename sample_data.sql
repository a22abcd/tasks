-- Insert sample users (password is 'password123' hashed with password_hash)
INSERT INTO users (username, password, email, full_name, role, created_at) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@company.com', 'مدير النظام', 'admin', '2025-04-14 02:45:46'),
('ahmed', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ahmed@company.com', 'أحمد محمد', 'employee', '2025-04-14 02:45:46'),
('sara', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sara@company.com', 'سارة أحمد', 'employee', '2025-04-14 02:45:46'),
('khalid', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'khalid@company.com', 'خالد عبدالله', 'employee', '2025-04-14 02:45:46'),
('nora', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nora@company.com', 'نورة سعد', 'employee', '2025-04-14 02:45:46');

-- Insert sample tasks
INSERT INTO tasks (title, description, difficulty_level, start_date, duration, status, created_by, assigned_to, created_at, updated_at) VALUES
('تطوير صفحة الرئيسية', 'تطوير وتحديث الصفحة الرئيسية للموقع مع إضافة التصميم الجديد', 2, '2025-04-15', 5, 'pending', 1, 2, '2025-04-14 02:45:46', '2025-04-14 02:45:46'),
('تحسين أداء قاعدة البيانات', 'تحسين استعلامات قاعدة البيانات وتحسين الأداء العام', 3, '2025-04-16', 7, 'in_progress', 1, 3, '2025-04-14 02:45:46', '2025-04-14 02:45:46'),
('إصلاح مشكلة تسجيل الدخول', 'معالجة مشكلة في نظام تسجيل الدخول للمستخدمين', 2, '2025-04-14', 3, 'completed', 1, 4, '2025-04-14 02:45:46', '2025-04-14 02:45:46'),
('إنشاء تقارير المبيعات', 'تطوير نظام تقارير المبيعات الشهرية', 2, '2025-04-17', 4, 'pending', 1, 2, '2025-04-14 02:45:46', '2025-04-14 02:45:46'),
('تحديث واجهة المستخدم', 'تحديث واجهة المستخدم لتتوافق مع المعايير الجديدة', 1, '2025-04-18', 6, 'in_progress', 1, 5, '2025-04-14 02:45:46', '2025-04-14 02:45:46');

-- Insert sample task evaluations
INSERT INTO task_evaluations (task_id, score, evaluated_by, evaluation_date) VALUES
(3, 95, 1, '2025-04-14 02:45:46'),
(2, 85, 1, '2025-04-14 02:45:46'),
(5, 90, 1, '2025-04-14 02:45:46');

-- Insert sample comments
INSERT INTO comments (task_id, user_id, comment, created_at) VALUES
(1, 2, 'بدأت العمل على تصميم الصفحة الرئيسية', '2025-04-14 02:45:46'),
(2, 3, 'تم تحسين أداء الاستعلامات بنسبة 40%', '2025-04-14 02:45:46'),
(3, 4, 'تم إصلاح المشكلة وإجراء الاختبارات اللازمة', '2025-04-14 02:45:46'),
(4, 2, 'جاري جمع متطلبات التقارير', '2025-04-14 02:45:46'),
(5, 5, 'تم الانتهاء من تصميم الواجهة الجديدة', '2025-04-14 02:45:46');

-- Insert sample daily reports
INSERT INTO daily_reports (user_id, report_date, report_content, created_at) VALUES
(2, '2025-04-14', 'تم العمل على تطوير الصفحة الرئيسية وإضافة التصميم الجديد. واجهت بعض التحديات في تنسيق العناصر ولكن تم حلها.', '2025-04-14 02:45:46'),
(3, '2025-04-14', 'عملت على تحسين أداء قاعدة البيانات وتحسين الاستعلامات. تم تحقيق تحسن ملحوظ في الأداء.', '2025-04-14 02:45:46'),
(4, '2025-04-14', 'أنهيت إصلاح مشكلة تسجيل الدخول وقمت بإجراء الاختبارات اللازمة للتأكد من حل المشكلة.', '2025-04-14 02:45:46'),
(5, '2025-04-14', 'بدأت العمل على تحديث واجهة المستخدم وفقاً للمعايير الجديدة. تم إنجاز 40% من المهمة.', '2025-04-14 02:45:46');

-- Insert sample task attachments
INSERT INTO task_attachments (task_id, file_name, file_path, uploaded_by, uploaded_at) VALUES
(1, 'تصميم_الصفحة_الرئيسية.pdf', '/uploads/2025/04/homepage_design.pdf', 2, '2025-04-14 02:45:46'),
(2, 'تقرير_تحسين_الأداء.xlsx', '/uploads/2025/04/performance_report.xlsx', 3, '2025-04-14 02:45:46'),
(3, 'تقرير_الإصلاح.docx', '/uploads/2025/04/bug_fix_report.docx', 4, '2025-04-14 02:45:46'),
(5, 'مخطط_واجهة_المستخدم.png', '/uploads/2025/04/ui_design.png', 5, '2025-04-14 02:45:46');