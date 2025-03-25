<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// معلومات المستخدم من الجلسة
$username = $_SESSION['username'];

// معالجة طلبات AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['action'])) {
        if ($data['action'] === 'loadRequests') {
            // جلب الطلبات المعلقة
            $conn = new mysqli("localhost", "root", "", "products_db2");

            if ($conn->connect_error) {
                echo json_encode([]);
                exit();
            }

            $sql = "SELECT id, phone_number, matching_request, created_at, created_by_user, created_by_position, matching_status, is_locked, locked_by_user FROM records WHERE matching_status = 'pending'";
            $result = $conn->query($sql);

            $requests = [];
            while ($row = $result->fetch_assoc()) {
                $requests[] = [
                    'id' => $row['id'],
                    'phone_number' => $row['phone_number'],
                    'matching_request' => $row['matching_request'],
                    'created_at' => $row['created_at'],
                    'created_by_user' => $row['created_by_user'],
                    'created_by_position' => $row['created_by_position'],
                    'matching_status' => $row['matching_status'],
                    'locked_by_user' => $row['is_locked'] ? $row['locked_by_user'] : null
                ];
            }

            echo json_encode($requests);
            $conn->close();
            exit();
        } elseif ($data['action'] === 'openRequest') {
            // فتح طلب معين
            $recordId = $data['recordId'];
            $username = $data['username'];

            $conn = new mysqli("localhost", "root", "", "products_db2");

            if ($conn->connect_error) {
                echo json_encode(['success' => false, 'message' => 'فشل الاتصال بقاعدة البيانات.']);
                exit();
            }

            // التحقق من حالة القفل
            $sql = "SELECT is_locked, locked_by_user FROM records WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $recordId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'لم يتم العثور على السجل.']);
                exit();
            }

            $row = $result->fetch_assoc();
            $isLocked = $row['is_locked'];
            $lockedByUser = $row['locked_by_user'];

            if ($isLocked && $lockedByUser !== $username) {
                echo json_encode(['success' => false, 'message' => 'هذا السجل محجوز بواسطة ' . $lockedByUser]);
                exit();
            }

            // قفل السجل للمستخدم الحالي
            $sql = "UPDATE records SET is_locked = TRUE, locked_by_user = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $username, $recordId);
            $stmt->execute();

            echo json_encode(['success' => true]);

            $stmt->close();
            $conn->close();
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة المطابقة</title>
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
    </style>
</head>
<body>
    <div class="header">
        <img src="images/mathchinglogo.JPG" alt="Matching Program ">
        <h1>قائمة المطابقة</h1>
        <p>عرض وإدارة طلبات المطابقة</p>
    </div>
    <div class="main-container">
        <div id="pendingRequestsLabel" class="alert alert-info text-center">عدد الطلبات المعلقة: <span id="pendingCount">0</span></div>
        <div id="loader" class="loader" style="display: none;"></div>
        <table id="requestsTable" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>رقم الهاتف</th>
                    <th>طلب المطابقة</th>
                    <th>تاريخ الإرسال</th>
                    <th>مرسل الطلب</th>
                    <th>القسم</th>
                    <th>حالة الطلب</th>
                    <th>محجوز بواسطة</th>
                </tr>
            </thead>
            <tbody>
                <!-- البيانات سيتم تعبئتها عبر JavaScript -->
            </tbody>
        </table>
    </div>

    <!-- JavaScript لمعالجة الأحداث -->
    <script>
        // دالة لتحميل الطلبات
        function loadRequests() {
            document.getElementById('loader').style.display = 'block';
            fetch('matching_list.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action: 'loadRequests' })
            })
            .then(response => response.json())
            .then(data => {
                const tableBody = document.querySelector('#requestsTable tbody');
                tableBody.innerHTML = ''; // مسح الجدول الحالي

                data.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${row.id}</td>
                        <td>${row.phone_number}</td>
                        <td>${row.matching_request}</td>
                        <td>${row.created_at}</td>
                        <td>${row.created_by_user}</td>
                        <td>${row.created_by_position}</td>
                        <td>${row.matching_status}</td>
                        <td>${row.locked_by_user || ''}</td>
                    `;
                    tr.addEventListener('dblclick', () => openRequest(row.id));
                    tableBody.appendChild(tr);
                });

                document.getElementById('pendingCount').innerText = data.length;
                document.getElementById('loader').style.display = 'none';
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('loader').style.display = 'none';
            });
        }

        // دالة لفتح طلب معين
        function openRequest(recordId) {
            fetch('matching_list.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action: 'openRequest', recordId: recordId, username: '<?php echo $username; ?>' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'matching_approval.php?recordId=' + recordId;
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
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