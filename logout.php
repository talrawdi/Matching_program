<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'لم يتم تسجيل الدخول.']);
    exit();
}

// الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "products_db2");

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// تحديث وقت الخروج في قاعدة البيانات
$user_id = $_SESSION['user_id'];
$sql = "UPDATE user_logs SET logout_time = NOW() WHERE user_id = ? AND logout_time IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

// إغلاق الاتصال
$stmt->close();
$conn->close();

// إنهاء الجلسة
session_destroy();

// إرجاع رسالة نجاح
echo json_encode(['success' => true, 'message' => 'تم تسجيل الخروج بنجاح.']);
?>