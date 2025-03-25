<?php
session_start();

// التحقق من تسجيل الدخول وصلاحية الأدمن
if (!isset($_SESSION['user_id']) || strcasecmp($_SESSION['access_rights'], 'Admin') !== 0) {
    header("Location: login.php");
    exit();
}

// الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "products_db2");
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// معالجة تغيير كلمة المرور
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $username = $_POST['username'];
    $oldPassword = $_POST['old_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        echo "<script>alert('كلمة المرور الجديدة غير متطابقة.');</script>";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($storedPassword);
            $stmt->fetch();

            if ($storedPassword === $oldPassword) {
                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
                $updateStmt->bind_param("ss", $newPassword, $username);
                if ($updateStmt->execute()) {
                    echo "<script>alert('تم تغيير كلمة المرور بنجاح.');</script>";
                } else {
                    echo "<script>alert('فشل تغيير كلمة المرور.');</script>";
                }
            } else {
                echo "<script>alert('كلمة المرور القديمة غير صحيحة.');</script>";
            }
        } else {
            echo "<script>alert('اسم المستخدم غير موجود.');</script>";
        }
    }
}

// إضافة إعدادات الإشعارات واللغة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $enableNotifications = isset($_POST['enable_notifications']) ? 1 : 0;
    $selectedLanguage = $_POST['language'];

    // حفظ الإعدادات في الجلسة (يمكنك حفظها في قاعدة البيانات إذا لزم الأمر)
    $_SESSION['enable_notifications'] = $enableNotifications;
    $_SESSION['language'] = $selectedLanguage;

    echo "<script>alert('تم تحديث الإعدادات بنجاح.');</script>";
}

// دالة لحساب حجم قاعدة البيانات
function getDatabaseSize($conn) {
    $query = "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS db_size_mb 
              FROM information_schema.TABLES 
              WHERE table_schema = 'products_db2'";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        return $row['db_size_mb'] . " MB";
    }
    return "غير متوفر";
}

// دالة لجلب المستخدمين النشطين
function getActiveUsers($conn) {
    $query = "SELECT username FROM user_logs WHERE logout_time IS NULL";
    $result = $conn->query($query);
    $activeUsers = [];
    while ($row = $result->fetch_assoc()) {
        $activeUsers[] = $row['username'];
    }
    return $activeUsers;
}

// جلب معلومات النظام
$activeUsers = getActiveUsers($conn);
$dbSize = getDatabaseSize($conn);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعدادات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">الإعدادات</h1>

        <!-- نموذج تغيير كلمة المرور -->
        <form method="POST" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <input type="text" name="username" class="form-control" placeholder="اسم المستخدم" required>
                </div>
                <div class="col-md-3">
                    <input type="password" name="old_password" class="form-control" placeholder="كلمة المرور القديمة" required>
                </div>
                <div class="col-md-3">
                    <input type="password" name="new_password" class="form-control" placeholder="كلمة المرور الجديدة" required>
                </div>
                <div class="col-md-3">
                    <input type="password" name="confirm_password" class="form-control" placeholder="تأكيد كلمة المرور" required>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <button type="submit" name="change_password" class="btn btn-primary w-100">تغيير كلمة المرور</button>
                </div>
            </div>
        </form>

        <!-- نموذج إعدادات إضافية -->
        <form method="POST" class="mb-4">
            <div class="row">
                <div class="col-md-6">
                    <label for="enable_notifications" class="form-label">تفعيل الإشعارات:</label>
                    <input type="checkbox" name="enable_notifications" id="enable_notifications" 
                           <?php echo isset($_SESSION['enable_notifications']) && $_SESSION['enable_notifications'] ? 'checked' : ''; ?>>
                </div>
                <div class="col-md-6">
                    <label for="language" class="form-label">اللغة:</label>
                    <select name="language" id="language" class="form-select">
                        <option value="English" <?php echo (isset($_SESSION['language']) && $_SESSION['language'] === 'English') ? 'selected' : ''; ?>>English</option>
                        <option value="Arabic" <?php echo (isset($_SESSION['language']) && $_SESSION['language'] === 'Arabic') ? 'selected' : ''; ?>>Arabic</option>
                        <option value="French" <?php echo (isset($_SESSION['language']) && $_SESSION['language'] === 'French') ? 'selected' : ''; ?>>French</option>
                        <option value="Spanish" <?php echo (isset($_SESSION['language']) && $_SESSION['language'] === 'Spanish') ? 'selected' : ''; ?>>Spanish</option>
                    </select>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <button type="submit" name="update_settings" class="btn btn-secondary w-100">تحديث الإعدادات</button>
                </div>
            </div>
        </form>

        <!-- عرض معلومات النظام -->
        <div class="border p-3">
            <h3>معلومات النظام</h3>
            <p>المستخدمون النشطون: <?php echo count($activeUsers); ?> 
                <?php if (!empty($activeUsers)) echo '(' . implode(', ', $activeUsers) . ')'; ?></p>
            <p>حجم قاعدة البيانات: <?php echo $dbSize; ?></p>
            <p>الأخطاء: <?php echo isset($conn->error) && $conn->error ? $conn->error : 'لا توجد أخطاء'; ?></p>
        </div>
    </div>
</body>
</html>