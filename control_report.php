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

// Handle search and export functionality
$from_date = isset($_POST['from_date']) ? $_POST['from_date'] : '';
$to_date = isset($_POST['to_date']) ? $_POST['to_date'] : '';
$search_number = isset($_POST['search_number']) ? $_POST['search_number'] : '';

// بناء الاستعلام بأمان
$query = "SELECT * FROM products WHERE 1=1";
$params = [];

if (!empty($from_date) && !empty($to_date)) {
    $query .= " AND add_date BETWEEN ? AND ?";
    $params[] = $from_date;
    $params[] = $to_date;
}

if (!empty($search_number)) {
    $query .= " AND number = ?";
    $params[] = $search_number;
}

$query .= " ORDER BY id DESC";

// إعداد الاستعلام باستخدام prepared statements
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Export to Excel
if (isset($_POST['export_excel'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=report_" . date('Ymd_His') . ".xls");
    echo "ID\tNumber\tProblem\tBranch\tEmployee\tAdd Date\tObserve\tRemark\n";
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "{$row['id']}\t{$row['number']}\t{$row['problem']}\t{$row['branch']}\t{$row['employee']}\t{$row['add_date']}\t{$row['observe']}\t{$row['remark']}\n";
        }
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<!-- باقي كود HTML كما هو -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير التحكم</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
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
    </style>
</head>
<body>
    <div class="header">
        <img src="images/mathchinglogo.JPG" alt="Matching Program Logo"> <!-- استبدال الشعار -->
        <h1>تقرير التحكم</h1>
        <p>عرض وتحليل تقارير التحكم</p>
    </div>
    <div class="main-container">
        <form method="POST" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <label for="from_date">من تاريخ:</label>
                    <input type="date" id="from_date" name="from_date" class="form-control" value="<?= $from_date ?>">
                </div>
                <div class="col-md-3">
                    <label for="to_date">إلى تاريخ:</label>
                    <input type="date" id="to_date" name="to_date" class="form-control" value="<?= $to_date ?>">
                </div>
                <div class="col-md-3">
                    <label for="search_number">رقم الهاتف:</label>
                    <input type="text" id="search_number" name="search_number" class="form-control" value="<?= $search_number ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">بحث</button>
                    <button type="submit" name="export_excel" class="btn btn-success">تصدير إلى Excel</button>
                </div>
            </div>
        </form>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>رقم الهاتف</th>
                    <th>المشكلة</th>
                    <th>الفرع</th>
                    <th>اسم الموظف</th>
                    <th>تاريخ الإضافة</th>
                    <th>المراقب</th>
                    <th>ملاحظات</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>{$row['id']}</td>";
                        echo "<td>{$row['number']}</td>";
                        echo "<td>{$row['problem']}</td>";
                        echo "<td>{$row['branch']}</td>";
                        echo "<td>{$row['employee']}</td>";
                        echo "<td>{$row['add_date']}</td>";
                        echo "<td>{$row['observe']}</td>";
                        echo "<td>{$row['remark']}</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' class='text-center'>لا توجد سجلات</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>