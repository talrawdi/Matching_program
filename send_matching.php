<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// معلومات المستخدم من الجلسة
$username = $_SESSION['username'];

// الاتصال بقاعدة البيانات مع تحديد الترميز
$conn = new mysqli("localhost", "root", "", "products_db2");
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// تحديد الترميز للاتصال
$conn->set_charset("utf8"); // أو "utf8_general_ci" إذا لزم الأمر

// جلب صلاحيات المستخدم من قاعدة البيانات
$user_id = $_SESSION['user_id'];
$sql = "SELECT SendMatching FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_permissions = $result->fetch_assoc();

// التحقق من الصلاحية
if ($user_permissions['SendMatching'] == 0) {
    header("Location: main_user.php");
    exit();
}

// معالجة طلبات AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // الحصول على البيانات من النموذج مع التنظيف
    $phoneNumber = $conn->real_escape_string($_POST['phoneNumber']);
    $matchingRequest = $conn->real_escape_string($_POST['matchingRequest']);

    // التحقق من إدخال الحقول المطلوبة
    if (strlen($phoneNumber) !== 9 || empty($matchingRequest) || empty($_FILES['images'])) {
        echo json_encode(['success' => false, 'message' => 'يجب إدخال جميع الحقول المطلوبة.']);
        exit();
    }

    // جلب منصب المستخدم من جدول users
    $stmt = $conn->prepare("SELECT position FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'لم يتم العثور على المستخدم.']);
        exit();
    }

    $userPosition = $result->fetch_assoc()['position'];

    // إدخال السجل في جدول records
    $stmt = $conn->prepare("INSERT INTO records (phone_number, matching_request, created_at, created_by_user, created_by_position, matching_status) VALUES (?, ?, NOW(), ?, ?, 'pending')");
    $stmt->bind_param("ssss", $phoneNumber, $matchingRequest, $username, $userPosition);
    
    if ($stmt->execute()) {
        $recordId = $stmt->insert_id;
        
        // تحميل الصور
        $uploadSuccess = true;
        foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
            if ($_FILES['images']['error'][$index] === UPLOAD_ERR_OK) {
                $imageData = file_get_contents($tmpName);
                $stmt = $conn->prepare("INSERT INTO images (record_id, image) VALUES (?, ?)");
                $stmt->bind_param("is", $recordId, $imageData);
                if (!$stmt->execute()) {
                    $uploadSuccess = false;
                }
            }
        }
        
        if ($uploadSuccess) {
            echo json_encode(['success' => true, 'message' => 'تم حفظ السجل بنجاح.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'تم حفظ البيانات ولكن حدث خطأ في بعض الصور.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ في حفظ البيانات.']);
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إرسال المطابقة</title>
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
        .image-preview {
            max-width: 100%;
            max-height: 400px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="images/mathchinglogo.JPG" alt="Matching Program Logo">
        <h1>إرسال المطابقة</h1>
        <p>قم بإرسال طلبات المطابقة بسهولة</p>
    </div>
    <div class="main-container">
        <form id="sendMatchingForm" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="phoneNumber" class="form-label">رقم الهاتف</label>
                <input type="text" class="form-control" id="phoneNumber" name="phoneNumber" maxlength="9" required>
            </div>
            <div class="mb-3">
                <label for="matchingRequest" class="form-label">طلب المطابقة</label>
                <textarea class="form-control" id="matchingRequest" name="matchingRequest" rows="3" required></textarea>
            </div>
            <div class="mb-3">
                <label for="images" class="form-label">تحميل الصور</label>
                <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*" required>
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary w-100">حفظ السجل</button>
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
        document.getElementById('sendMatchingForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);

            fetch('send_matching.php', { // تأكد من أن المسار صحيح
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم حفظ السجل بنجاح!');
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