<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "products_db2");

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// الحصول على الاستعلام من النموذج
$query = $_POST['query'];

// جلب البيانات من قاعدة البيانات
$result = $conn->query($query);

if ($result->num_rows === 0) {
    die("لا توجد بيانات للتصدير.");
}

// إنشاء ملف Excel
require 'vendor/autoload.php'; // تأكد من تثبيت مكتبة PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// إضافة رأس الجدول
$header = [
    'ID', 'رقم الهاتف', 'طلب المطابقة', 'الحالة', 'تاريخ الإنشاء', 
    'تم الإنشاء بواسطة', 'المنصب', 'وقت الرد', 'تم الرد بواسطة', 
    'منصب المستجيب', 'تفاصيل الرد'
];
$sheet->fromArray($header, null, 'A1');

// إضافة البيانات
$rowNumber = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->fromArray([
        $row['id'], $row['phone_number'], $row['matching_request'], 
        $row['matching_status'], $row['created_at'], $row['created_by_user'], 
        $row['created_by_position'], $row['response_time'], 
        $row['responded_by_user'], $row['responded_by_position'], 
        $row['response_details']
    ], null, 'A' . $rowNumber);
    $rowNumber++;
}

// حفظ الملف
$writer = new Xlsx($spreadsheet);
$filename = 'matching_report_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
?>