<?php
$servername = "sql11b.byadjuster.com";  // تغيير اسم الخادم
$username = "38205864";                // اسم المستخدم الجديد
$password = "Mtn733122268";    // كلمة المرور الجديدة
$dbname = "products_db2";                  // اسم قاعدة البيانات الجديد

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>