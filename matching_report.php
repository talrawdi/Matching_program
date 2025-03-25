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

// جلب بيانات البحث (إذا كانت موجودة)
$phoneNumber = isset($_GET['phoneNumber']) ? $_GET['phoneNumber'] : '';
$fromDate = isset($_GET['fromDate']) ? $_GET['fromDate'] : '';
$toDate = isset($_GET['toDate']) ? $_GET['toDate'] : '';

// طباعة التواريخ للتصحيح
echo "<pre>fromDate: $fromDate, toDate: $toDate</pre>";

// بناء الاستعلام
$query = "SELECT * FROM records WHERE 1=1";
if (!empty($phoneNumber)) {
    $query .= " AND phone_number LIKE '%$phoneNumber%'";
}
if (!empty($fromDate)) {
    $query .= " AND DATE(created_at) >= '$fromDate'"; // استخدام DATE() لاستخراج التاريخ فقط
}
if (!empty($toDate)) {
    $query .= " AND DATE(created_at) <= '$toDate'"; // استخدام DATE() لاستخراج التاريخ فقط
}

// طباعة الاستعلام للتصحيح
echo "<pre>Query: $query</pre>";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير المطابقة</title>
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
            max-height: 90px; /* Increased size to 90px */
            border: 3px solid #0056b3; /* Add a solid border */
            border-radius: 10px; /* Rounded corners */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* 3D shadow effect */
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
        .main-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .table thead th {
            background-color: #007bff;
            color: white;
        }
        .table tbody tr:nth-child(odd) {
            background-color: #f2f2f2;
        }
        .table tbody tr:hover {
            background-color: #ddd;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="images/mathchinglogo.JPG" alt="Matching Program Logo">
        <h1>تقرير المطابقة</h1>
        <p>عرض وتحليل تقارير المطابقة</p>
    </div>
    <div class="main-container">
        <h1 class="text-center mb-4">تقرير المطابقة</h1>

        <!-- نموذج البحث -->
        <form method="GET" action="matching_report.php" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <label for="phoneNumber" class="form-label">رقم الهاتف:</label>
                    <input type="text" id="phoneNumber" name="phoneNumber" class="form-control" value="<?php echo $phoneNumber; ?>">
                </div>
                <div class="col-md-3">
                    <label for="fromDate" class="form-label">من تاريخ:</label>
                    <input type="date" id="fromDate" name="fromDate" class="form-control" value="<?php echo $fromDate; ?>">
                </div>
                <div class="col-md-3">
                    <label for="toDate" class="form-label">إلى تاريخ:</label>
                    <input type="date" id="toDate" name="toDate" class="form-control" value="<?php echo $toDate; ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">بحث</button>
                </div>
            </div>
        </form>

        <!-- جدول النتائج -->
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>رقم الهاتف</th>
                    <th>طلب المطابقة</th>
                    <th>الحالة</th>
                    <th>تاريخ الإنشاء</th>
                    <th>تم الإنشاء بواسطة</th>
                    <th>المنصب</th>
                    <th>وقت الرد</th>
                    <th>تم الرد بواسطة</th>
                    <th>منصب المستجيب</th>
                    <th>تفاصيل الرد</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>{$row['id']}</td>";
                        echo "<td>{$row['phone_number']}</td>";
                        echo "<td>{$row['matching_request']}</td>";
                        echo "<td>{$row['matching_status']}</td>";
                        echo "<td>{$row['created_at']}</td>";
                        echo "<td>{$row['created_by_user']}</td>";
                        echo "<td>{$row['created_by_position']}</td>";
                        echo "<td>{$row['response_time']}</td>";
                        echo "<td>{$row['responded_by_user']}</td>";
                        echo "<td>{$row['responded_by_position']}</td>";
                        echo "<td>{$row['response_details']}</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='11' class='text-center'>لا توجد سجلات</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- زر التصدير إلى Excel -->
        <div class="text-center mt-4">
            <form method="POST" action="export_to_excel.php" target="_blank">
                <input type="hidden" name="query" value="<?php echo htmlspecialchars($query); ?>">
                <button type="submit" class="btn btn-success">تصدير إلى Excel</button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>