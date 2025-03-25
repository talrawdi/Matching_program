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

// معالجة حفظ الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_details = $_POST['request_details'];
    $phone_number = $_POST['phone_number'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // التحقق من صحة البيانات
    if (strlen($phone_number) !== 9 || !is_numeric($phone_number)) {
        echo json_encode(['success' => false, 'message' => 'رقم الهاتف يجب أن يكون 9 أرقام.']);
        exit();
    }

    // إدخال الطلب في قاعدة البيانات
    $sql = "INSERT INTO detailed_bills (user_id, created_by, request_details, position, phone_number, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssss", $user_id, $username, $request_details, $position, $phone_number, $start_date, $end_date);
    $stmt->execute();
    $detailed_bill_id = $stmt->insert_id;

    // تحميل الصور
    if (!empty($_FILES['images']['tmp_name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $index => $tmp_name) {
            $image_data = file_get_contents($tmp_name);
            $sql = "INSERT INTO csc_images (detailed_bill_id, image) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $detailed_bill_id, $image_data);
            $stmt->execute();
        }
    }

    echo json_encode(['success' => true, 'message' => 'تم حفظ الطلب بنجاح.']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلب فاتورة تفصيلية</title>
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
        .image-preview {
            max-width: 100%;
            max-height: 400px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <h1 class="text-center mb-4">طلب فاتورة تفصيلية</h1>
        <form id="detailBillForm" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="request_details" class="form-label">تفاصيل الطلب:</label>
                <textarea class="form-control" id="request_details" name="request_details" rows="3" required></textarea>
            </div>
            <div class="mb-3">
                <label for="phone_number" class="form-label">رقم الهاتف:</label>
                <input type="text" class="form-control" id="phone_number" name="phone_number" maxlength="9" required>
            </div>
            <div class="mb-3">
                <label for="start_date" class="form-label">تاريخ البدء:</label>
                <input type="date" class="form-control" id="start_date" name="start_date" required>
            </div>
            <div class="mb-3">
                <label for="end_date" class="form-label">تاريخ الانتهاء:</label>
                <input type="date" class="form-control" id="end_date" name="end_date" required>
            </div>
            <div class="mb-3">
                <label for="images" class="form-label">تحميل الصور:</label>
                <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary w-100">حفظ الطلب</button>
            </div>
        </form>

        <!-- معاينة الصور -->
        <div id="imagePreview" class="text-center"></div>
    </div>

    <!-- JavaScript لمعالجة الأحداث -->
    <script>
        // عرض معاينة الصور
        document.getElementById('images').addEventListener('change', function(event) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = ''; // مسح المعاينة السابقة

            const files = event.target.files;
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();

                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('image-preview');
                    preview.appendChild(img);
                };

                reader.readAsDataURL(file);
            }
        });

        // إرسال النموذج
        document.getElementById('detailBillForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);

            fetch('detail_bill.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload(); // إعادة تحميل الصفحة
                } else {
                    alert('حدث خطأ: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>