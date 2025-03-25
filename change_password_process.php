<?php
session_start();

// الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "products_db2");

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// الحصول على البيانات من النموذج
$username = $_POST['username'];
$old_password = $_POST['old_password'];
$new_password = $_POST['new_password'];
$confirm_new_password = $_POST['confirm_new_password'];

// التحقق من تطابق كلمتي المرور الجديدتين
if ($new_password !== $confirm_new_password) {
    echo "<script>alert('كلمة المرور الجديدة غير متطابقة.'); window.location.href = 'change_password.php';</script>";
    exit();
}

// التحقق من صحة كلمة المرور القديمة
$sql = "SELECT * FROM users WHERE username = ? AND password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $old_password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // تحديث كلمة المرور
    $update_sql = "UPDATE users SET password = ? WHERE username = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ss", $new_password, $username);
    $update_stmt->execute();

    echo "<script>alert('تم تغيير كلمة المرور بنجاح.'); window.location.href = 'login.php';</script>";
} else {
    echo "<script>alert('اسم المستخدم أو كلمة المرور القديمة غير صحيحة.'); window.location.href = 'change_password.php';</script>";
}

$stmt->close();
$conn->close();
?>