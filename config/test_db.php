<?php
// 1. استدعاء ملف اتصال قاعدة البيانات
require_once __DIR__ . '/config/db.php';

// 2. اختبار اتصال بسيط
echo "<h2>اختبار اتصال قاعدة البيانات</h2>";

// 3. استعلام اختباري
$test_sql = "SHOW TABLES";
$result = $conn->query($test_sql);

if (!$result) {
    die("<p style='color:red;'>فشل في جلب الجداول: " . $conn->error . "</p>");
}

// 4. عرض النتائج
echo "<p style='color:green;'>✓ اتصال ناجح مع قاعدة البيانات</p>";
echo "<h3>الجداول المتوفرة:</h3>";

echo "<ul>";
while ($row = $result->fetch_row()) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

// 5. اختبار جدول المستخدمين (اختياري)
if (in_array('users', array_map('strtolower', $result->fetch_all()))) {
    $users_sql = "SELECT COUNT(*) as total_users FROM users";
    $users_result = $conn->query($users_sql);
    $users_count = $users_result->fetch_assoc();
    echo "<p>عدد المستخدمين المسجلين: " . $users_count['total_users'] . "</p>";
}

$conn->close();
?>