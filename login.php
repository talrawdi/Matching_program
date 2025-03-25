<?php
session_start();

// 1. استدعاء ملف اتصال قاعدة البيانات من المسار الجديد
require_once __DIR__ . '/../config/db.php';


// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// معالجة تسجيل الدخول إذا تم إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // التحقق من صحة بيانات الدخول
    $sql = "SELECT user_id, username, password, access_rights FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($password === $row['password']) { // مقارنة كلمة المرور مباشرة
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['access_rights'] = trim($row['access_rights']); // إزالة المسافات الزائدة

            // تسجيل وقت الدخول
            $log_sql = "INSERT INTO user_logs (user_id, username, login_time) VALUES (?, ?, NOW())";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("is", $row['user_id'], $row['username']);
            $log_stmt->execute();

            // توجيه المستخدم بناءً على صلاحياته
            if (strcasecmp($_SESSION['access_rights'], 'Admin') === 0) { // مقارنة بدون حساسية لحالة الأحرف
                header("Location: admin_control.php"); // توجيه الأدمن إلى صفحة الأدمن
            } else {
                header("Location: main_user.php"); // توجيه المستخدم العادي إلى صفحة المستخدم
            }
            exit(); // تأكد من إيقاف تنفيذ النص بعد التوجيه
        } else {
            $error_message = "فشل تسجيل الدخول. اسم المستخدم أو كلمة المرور غير صحيحة.";
        }
    } else {
        $error_message = "فشل تسجيل الدخول. اسم المستخدم أو كلمة المرور غير صحيحة.";
    }

    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Tajawal', sans-serif;
        }
        .header {
            background: linear-gradient(90deg, #007bff, #0056b3);
            color: #fff;
            padding: 20px;
            text-align: center;
            border-bottom: 5px solid #0056b3;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .header img {
            position: absolute;
            top: 50%;
            left: 20px;
            transform: translateY(-50%);
            max-height: 80px; 
        }
        .header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 1rem;
            font-style: italic;
            color: #d1e7ff;
        }
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
        }
        .login-container img {
            max-width: 200px; /* Increased from 100px to 160px (60% larger) */
            margin-bottom: 20px;
        }
        .alert {
            margin-top: 20px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 0.9rem;
            color: #555;
        }
        .footer a {
            color: #007bff;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>تسجيل الدخول</h1>
        <p>مرحباً بك في نظام المطابقة</p>
    </div>
    <div class="login-container">
        <!-- إضافة الصورة داخل مربع تسجيل الدخول -->
        <img src="images/mathchinglogo.JPG" alt="Matching Program Logo">
        <!-- عرض رسالة الخطأ إذا كانت موجودة -->
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- نموذج تسجيل الدخول -->
        <form method="POST" action="login.php">
            <div class="mb-3">
                <label for="username" class="form-label">اسم المستخدم</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">كلمة المرور</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">تسجيل الدخول</button>
        </form>

        <!-- رابط تغيير كلمة المرور -->
        <div class="mt-3 text-center">
            <a href="change_password.php" class="text-decoration-none">تغيير كلمة المرور</a>
        </div>
    </div>

    <!-- معلومات المطور -->
    <div class="footer">
        <p>Developed and Designed by: <strong>Tariq AL-Rawdi</strong></p>
        <p>Email: <a href="mailto:talrawdi@you.com.ye?subject=Support%20Request&body=Dear%20Tariq%20AL-Rawdi,%0A%0A" target="_self">talrawdi@you.com.ye</a></p>
        <p>Extension: <strong>1193</strong></p>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>