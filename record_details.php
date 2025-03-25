<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// الحصول على معرف السجل من الرابط
$recordId = $_GET['recordId'];

// الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "products_db2");

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// جلب بيانات السجل
$sql = "SELECT * FROM records WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $recordId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("لم يتم العثور على السجل.");
}

$record = $result->fetch_assoc();

// جلب الصور المرتبطة بالسجل
$images = [];
$sql = "SELECT image FROM images WHERE record_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $recordId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $images[] = base64_encode($row['image']);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل السجل</title>
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
        <h1>تفاصيل السجل</h1>
        <p>عرض تفاصيل السجل والصور المرتبطة به</p>
    </div>
    <div class="main-container">
        <h1 class="text-center mb-4">تفاصيل السجل</h1>

        <!-- تفاصيل السجل -->
        <div class="mb-3">
            <label class="form-label">رقم الهاتف:</label>
            <input type="text" class="form-control" value="<?php echo $record['phone_number']; ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">طلب المطابقة:</label>
            <input type="text" class="form-control" value="<?php echo $record['matching_request']; ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">الحالة:</label>
            <input type="text" class="form-control" value="<?php echo $record['matching_status']; ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">تاريخ الإنشاء:</label>
            <input type="text" class="form-control" value="<?php echo $record['created_at']; ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">تم الإنشاء بواسطة:</label>
            <input type="text" class="form-control" value="<?php echo $record['created_by_user']; ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">المنصب:</label>
            <input type="text" class="form-control" value="<?php echo $record['created_by_position']; ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">وقت الرد:</label>
            <input type="text" class="form-control" value="<?php echo $record['response_time']; ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">تم الرد بواسطة:</label>
            <input type="text" class="form-control" value="<?php echo $record['responded_by_user']; ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">منصب المستجيب:</label>
            <input type="text" class="form-control" value="<?php echo $record['responded_by_position']; ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">تفاصيل الرد:</label>
            <textarea class="form-control" readonly><?php echo $record['response_details']; ?></textarea>
        </div>

        <!-- عرض الصور -->
        <div id="imagePreview" class="text-center">
            <?php foreach ($images as $image): ?>
                <img src="data:image/jpeg;base64,<?php echo $image; ?>" class="image-preview">
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>