<?php
$servername = "sql11b.byadjuster.com";
$username = "if0_38505564";
$password = "Mtn733122268"; 
$dbname = "if0_38505564_products_db2";

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// تعيين الترميز للغة العربية
$conn->set_charset("utf8mb4");
?>