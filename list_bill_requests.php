<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "products_db2");

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// جلب معلومات المستخدم
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$sql = "SELECT position FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$position = $user['position'];

// دالة لجلب الطلبات من قاعدة البيانات
function fetchRequests($conn) {
    $sql = "SELECT id, phone_number, request_type, position, created_by, created_at, status, responded_by_user FROM detailed_bills WHERE status = 'Pending'";
    $result = $conn->query($sql);

    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }

    return $requests;
}

// جلب الطلبات
$requests = fetchRequests($conn);

// إغلاق الاتصال
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض طلبات الفواتير</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Tajawal', sans-serif;
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
    <div class="main-container">
        <h1 class="text-center mb-4">عرض طلبات الفواتير</h1>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <strong>مرحباً، <?php echo $username; ?> - <?php echo $position; ?></strong>
                </div>
                <div id="statusCount" class="alert alert-warning">
                    عدد الطلبات في الانتظار: <span id="pendingCount">0</span>
                </div>
                <div id="currentTime" class="alert alert-secondary">
                    الوقت والتاريخ الحالي: <span id="time"></span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <table id="requestsTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>رقم الهاتف</th>
                            <th>نوع الطلب</th>
                            <th>المنصب</th>
                            <th>تم الإنشاء بواسطة</th>
                            <th>تاريخ الإنشاء</th>
                            <th>الحالة</th>
                            <th>تم الرد بواسطة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo $request['id']; ?></td>
                                <td><?php echo $request['phone_number']; ?></td>
                                <td><?php echo $request['request_type']; ?></td>
                                <td><?php echo $request['position']; ?></td>
                                <td><?php echo $request['created_by']; ?></td>
                                <td><?php echo $request['created_at']; ?></td>
                                <td><?php echo $request['status']; ?></td>
                                <td><?php echo $request['responded_by_user'] ?? ''; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- JavaScript لمعالجة الأحداث -->
    <script>
        // دالة لتحديث الوقت والتاريخ
        function updateTime() {
            const now = new Date();
            const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('time').textContent = now.toLocaleDateString('ar-EG', options);
        }

        // تحديث الوقت كل ثانية
        setInterval(updateTime, 1000);
        updateTime(); // تحديث الوقت عند التحميل

        // دالة لتحميل الطلبات
        function loadRequests() {
            fetch('fetch_requests.php')
                .then(response => response.json())
                .then(data => {
                    const tableBody = document.querySelector('#requestsTable tbody');
                    tableBody.innerHTML = ''; // مسح الجدول الحالي

                    let pendingCount = 0;

                    data.forEach(row => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${row.id}</td>
                            <td>${row.phone_number}</td>
                            <td>${row.request_type}</td>
                            <td>${row.position}</td>
                            <td>${row.created_by}</td>
                            <td>${row.created_at}</td>
                            <td>${row.status}</td>
                            <td>${row.responded_by_user || ''}</td>
                        `;
                        tableBody.appendChild(tr);

                        if (row.status === 'Pending') {
                            pendingCount++;
                        }
                    });

                    document.getElementById('pendingCount').textContent = pendingCount;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        // تحميل الطلبات عند فتح الصفحة
        loadRequests();

        // تحديث الطلبات كل 5 ثوانٍ
        setInterval(loadRequests, 5000);
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>