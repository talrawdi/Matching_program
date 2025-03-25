<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// معلومات المستخدم من الجلسة
$username = $_SESSION['username'];

// الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "products_db2");

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// جلب الحالة المحددة من الطلب (إذا كانت موجودة)
$currentStatus = isset($_GET['status']) ? $_GET['status'] : 'All';

// جلب رقم الهاتف من البحث (إذا كان موجودًا)
$phoneNumber = isset($_GET['phoneNumber']) ? $_GET['phoneNumber'] : '';

// جلب ترتيب الفرز من الطلب (إذا كان موجودًا)
$orderBy = isset($_GET['orderBy']) ? $_GET['orderBy'] : 'ASC';

// إعداد الاستعلام باستخدام معاملات مهيأة
$stmt = $conn->prepare("
    SELECT * FROM records 
    WHERE (matching_status = ? OR ? = 'All') 
    AND (phone_number LIKE ? OR ? = '') 
    ORDER BY created_at $orderBy
");

// إعداد القيم للمعاملات
$phoneSearch = "%$phoneNumber%";
$stmt->bind_param("ssss", $currentStatus, $currentStatus, $phoneSearch, $phoneNumber);

// تنفيذ الاستعلام
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معاينة حالة السجل</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color:rgb(248, 250, 250);
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
            max-width: 1400px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #007bff;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
        }
        .table thead th {
            background-color: #007bff;
            color: white;
            text-align: center;
            font-weight: bold;
        }
        .table tbody td {
            text-align: center;
            vertical-align: middle;
        }
        .table tbody tr:nth-child(odd) {
            background-color: #f9f9f9;
        }
        .table tbody tr:hover {
            background-color: #f1f1f1;
            transition: background-color 0.3s ease;
        }
        .form-select, .form-control {
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            font-weight: bold;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .no-records {
            text-align: center;
            color: #888;
            font-size: 18px;
            padding: 20px;
        }
    </style>
    <script>
        function updateTable() {
            document.getElementById('filterForm').submit();
        }

        // Function to refresh the table every 10 seconds
        function autoRefreshTable() {
            const urlParams = new URLSearchParams(window.location.search);
            fetch(window.location.pathname + '?' + urlParams.toString())
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTableBody = doc.querySelector('tbody').innerHTML;
                    document.querySelector('tbody').innerHTML = newTableBody;
                })
                .catch(error => console.error('Error refreshing table:', error));
        }

        // Set interval to refresh the table
        setInterval(autoRefreshTable, 10000);

        // Validate phone number input
        const phoneNumberInput = document.querySelector('input[name="phoneNumber"]');
        phoneNumberInput.addEventListener('input', function (e) {
            const value = e.target.value;
            // Allow only digits and limit to 9 characters
            e.target.value = value.replace(/\D/g, '').slice(0, 9);
        });

        document.getElementById('filterForm').addEventListener('submit', function (e) {
            const phoneNumber = phoneNumberInput.value;
            if (phoneNumber.length !== 9) {
                e.preventDefault();
                alert('يجب أن يكون رقم الهاتف مكونًا من 9 أرقام بالضبط.');
            }
        });
    </script>
</head>
<body>
    <div class="header">
        <img src="images/mathchinglogo.JPG" alt="Matching Program Logo"> <!-- استبدال الشعار -->
        <h1>معاينة حالة السجل</h1>
        <p>عرض وتتبع حالة السجلات بسهولة</p>
    </div>
    <div class="main-container">
        <h1>معاينة حالة سجل المطابقة</h1>

        <!-- القائمة المنسدلة لاختيار الحالة -->
        <form method="GET" action="" class="mb-4" id="filterForm">
            <div class="row g-3">
                <div class="col-md-3">
                    <select name="status" class="form-select" onchange="updateTable()">
                        <option value="All" <?php echo $currentStatus === 'All' ? 'selected' : ''; ?>>الجميع</option>
                        <option value="Pending" <?php echo $currentStatus === 'Pending' ? 'selected' : ''; ?>>انتظار</option>
                        <option value="Approved" <?php echo $currentStatus === 'Approved' ? 'selected' : ''; ?>>مقبول</option>
                        <option value="Rejected" <?php echo $currentStatus === 'Rejected' ? 'selected' : ''; ?>>مرفوض</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="phoneNumber" class="form-control" placeholder="أدخل رقم الهاتف (9 أرقام)" value="<?php echo $phoneNumber; ?>">
                </div>
                <div class="col-md-3">
                    <select name="orderBy" class="form-select" onchange="updateTable()">
                        <option value="ASC" <?php echo $orderBy === 'ASC' ? 'selected' : ''; ?>>تصاعدي</option>
                        <option value="DESC" <?php echo $orderBy === 'DESC' ? 'selected' : ''; ?>>تنازلي</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">بحث</button>
                </div>
            </div>
        </form>

        <!-- جدول عرض السجلات -->
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>رقم الهاتف</th>
                        <th>طلب المطابقة</th>
                        <th>الحالة</th>
                        <th>تاريخ الإنشاء</th>
                        <th>وقت الفتح</th>
                        <th>تم الإنشاء بواسطة</th>
                        <th>المنصب</th>
                        <th>وقت الرد</th>
                        <th>تم الرد بواسطة</th>
                        <th>منصب المستجيب</th>
                        <th>تفاصيل الرد</th>
                        <th>تم القفل بواسطة</th>
                        <th>مدة الرد</th>
                        <th>مدة الرد من الفتح</th>
                        <th>مغلق</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr onclick=\"window.location.href='record_details.php?recordId={$row['id']}'\">";
                            echo "<td>{$row['id']}</td>";
                            echo "<td>{$row['phone_number']}</td>";
                            echo "<td>{$row['matching_request']}</td>";
                            echo "<td>{$row['matching_status']}</td>";
                            echo "<td>{$row['created_at']}</td>";
                            echo "<td>{$row['opened_at']}</td>";
                            echo "<td>{$row['created_by_user']}</td>";
                            echo "<td>{$row['created_by_position']}</td>";
                            echo "<td>{$row['response_time']}</td>";
                            echo "<td>{$row['responded_by_user']}</td>";
                            echo "<td>{$row['responded_by_position']}</td>";
                            echo "<td>{$row['response_details']}</td>";
                            echo "<td>{$row['locked_by_user']}</td>";
                            echo "<td>{$row['response_duration']}</td>";
                            echo "<td>{$row['response_duration_from_open']}</td>";
                            echo "<td>" . ($row['is_locked'] ? 'نعم' : 'لا') . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='16' class='no-records'>لا توجد سجلات</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>