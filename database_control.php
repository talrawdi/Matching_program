<?php
session_start();

// التحقق من تسجيل الدخول وصلاحية الأدمن
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "products_db2");
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// معالجة طلبات الحذف
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $table = $_GET['table'];
    $id = $_GET['id'] ?? null;

    if ($action === 'deleteRecord' && $id) {
        // حذف سجل محدد
        $sql = "DELETE FROM $table WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'تم حذف السجل بنجاح.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء الحذف: ' . $stmt->error]);
        }
        exit();
    } elseif ($action === 'deleteAllRecords') {
        // حذف جميع السجلات
        $sql = "DELETE FROM $table";
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'تم حذف جميع السجلات بنجاح.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء الحذف: ' . $conn->error]);
        }
        exit();
    }
}

// تحميل الجداول المتاحة
$tables = ['user_logs', 'users', 'products', 'records', 'images'];
$selectedTable = $_GET['table'] ?? $tables[0]; // الجدول المحدد (افتراضيًا الجدول الأول)

// تحميل بيانات الجدول المحدد
$query = "SELECT * FROM $selectedTable";
$result = $conn->query($query);
$columns = [];
$rows = [];

if ($result) {
    $columns = $result->fetch_fields(); // الحصول على أسماء الأعمدة
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row; // الحصول على الصفوف
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحكم بقاعدة البيانات</title>
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
        <h1>التحكم بقاعدة البيانات</h1>
        <p>إدارة الجداول والسجلات بسهولة</p>
    </div>
    <div class="main-container">
        <h1 class="text-center mb-4">التحكم بقاعدة البيانات</h1>

        <!-- قائمة اختيار الجداول -->
        <form method="GET" action="database_control.php" class="mb-4">
            <div class="row">
                <div class="col-md-6">
                    <label for="table" class="form-label">اختر الجدول:</label>
                    <select id="table" name="table" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($tables as $table): ?>
                            <option value="<?php echo $table; ?>" <?php echo $selectedTable === $table ? 'selected' : ''; ?>>
                                <?php echo $table; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>

        <!-- جدول عرض البيانات -->
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <?php foreach ($columns as $column): ?>
                            <th><?php echo $column->name; ?></th>
                        <?php endforeach; ?>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <td>
                                    <?php if ($column->name === 'password'): ?>
                                        **** <!-- عرض كلمة المرور على شكل نجوم -->
                                    <?php else: ?>
                                        <?php echo $row[$column->name]; ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="editRecord(<?php echo $row[$columns[0]->name]; ?>)">تعديل</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteRecord(<?php echo $row[$columns[0]->name]; ?>)">حذف</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- أزرار التحكم -->
        <div class="mt-4">
            <button class="btn btn-danger" onclick="deleteAllRecords()">حذف جميع السجلات</button>
            <button class="btn btn-primary" onclick="refreshTable()">تحديث الجدول</button>
        </div>
    </div>

    <!-- JavaScript لمعالجة الأحداث -->
    <script>
        // دالة لتحديث الجدول
        function refreshTable() {
            window.location.reload();
        }

        // دالة لتعديل السجل
        function editRecord(id) {
            alert("تعديل السجل ذو المعرف: " + id);
            // يمكنك فتح نافذة تعديل أو توجيه المستخدم إلى صفحة التعديل
        }

        // دالة لحذف السجل
        function deleteRecord(id) {
            if (confirm("هل أنت متأكد من حذف هذا السجل؟")) {
                fetch(`database_control.php?action=deleteRecord&table=<?php echo $selectedTable; ?>&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert("تم حذف السجل بنجاح!");
                            refreshTable();
                        } else {
                            alert("حدث خطأ أثناء الحذف: " . data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        }

        // دالة لحذف جميع السجلات
        function deleteAllRecords() {
            if (confirm("هل أنت متأكد من حذف جميع السجلات؟ هذه العملية لا يمكن التراجع عنها.")) {
                fetch(`database_control.php?action=deleteAllRecords&table=<?php echo $selectedTable; ?>`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert("تم حذف جميع السجلات بنجاح!");
                            refreshTable();
                        } else {
                            alert("حدث خطأ أثناء الحذف: " . data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        }
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>