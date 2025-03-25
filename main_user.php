<?php
session_start();

// الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "products_db2");
$conn->set_charset("utf8mb4"); // إضافة هذه السطر لضبط الترميز
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// التحقق من تسجيل الدخول فقط إذا كان المستخدم مديرًا
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_permissions = $result->fetch_assoc();
    $stmt->close();
} else {
    $user_permissions = []; // إذا لم يكن المستخدم مسجلاً، لا توجد صلاحيات
}

// جلب قائمة الصفحات من قاعدة البيانات
$pages_sql = "SELECT * FROM pages";
$pages_result = $conn->query($pages_sql);
$pages = [];
while ($row = $pages_result->fetch_assoc()) {
    $pages[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الواجهة الرئيسية</title>
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
            max-height: 90px; /* Set size to match the new logo */
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
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
            cursor: pointer;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card img {
            width: 100px;
            height: 100px;
            margin: 20px auto;
            display: block;
            transform: scale(1.5); /* تكبير الصور بنسبة 50% */
        }
        .card-body {
            text-align: center;
        }
        .card-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
        }
        .disabled-card {
            opacity: 0.5;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="images/mathchinglogo.JPG" alt="Matching Program Logo">
        <h1>الواجهة الرئيسية</h1>
        <p>مرحباً بك في النظام، اختر الإجراء المطلوب</p>
    </div>
    <div class="main-container">
        <h1 class="text-center mb-4">مرحباً، <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'زائر'; ?></h1>
        <div class="row">
            <?php foreach ($pages as $page): ?>
                <?php 
                $permission_column = $page['permission_column']; 
                if (isset($user_permissions[$permission_column]) && $user_permissions[$permission_column] == 1): 
                ?>
                    <div class="col-md-4 mb-4">
                        <a href="<?php echo $page['page_name']; ?>.php" class="text-decoration-none">
                            <div class="card">
                                <img src="<?php echo $page['icon_path']; ?>" alt="<?php echo $page['page_title']; ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $page['page_title']; ?></h5>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php" class="btn btn-dark btn-lg">تسجيل الخروج</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-lg">تسجيل الدخول</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript لمعالجة الأحداث -->
    <script>
        // لا حاجة لدالة handleAction هنا لأن الروابط تعمل مباشرة
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>