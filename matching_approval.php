<?php
session_start();

// ضبط المنطقة الزمنية
date_default_timezone_set('Asia/Riyadh'); // استبدل 'Asia/Riyadh' بالمنطقة الزمنية المناسبة

// تفعيل تصحيح الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح بالوصول.']);
    exit();
}

// الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "products_db2");

// التحقق من الاتصال
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'فشل الاتصال بقاعدة البيانات: ' . $conn->connect_error]);
    exit();
}

// جلب آخر سجل معلق للمستخدم الحالي
$username = $_SESSION['username'];
$sql = "SELECT * FROM records WHERE matching_status = 'pending' AND created_by_user = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>
        alert('لا توجد طلبات معلقة.');
        window.location.href = 'matching_list.php';
    </script>";
    exit();
}

$record = $result->fetch_assoc();
$recordId = $record['id'];

// جلب الصور المرتبطة بالسجل
$sql_images = "SELECT image FROM images WHERE record_id = ?";
$stmt_images = $conn->prepare($sql_images); // تعريف وتحضير $stmt_images
$stmt_images->bind_param("i", $recordId);
$stmt_images->execute();
$result_images = $stmt_images->get_result();

$images = [];
while ($row = $result_images->fetch_assoc()) {
    $images[] = $row['image'];
}

// معالجة الرد على الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // التحقق من البيانات المرسلة
    if (!isset($data['status']) || !isset($data['responseDetails'])) {
        echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة.']);
        exit();
    }

    $status = $data['status'];
    $responseDetails = $data['responseDetails'];
    $username = $_SESSION['username'];
    $responseTime = date('Y-m-d H:i:s'); // وقت الرد الحالي
    $createdAt = $record['created_at'];
    $openedAt = $record['opened_at'];

    // جلب منصب المستخدم من جدول users
    $sql_position = "SELECT position FROM users WHERE username = ?";
    $stmt_position = $conn->prepare($sql_position);
    $stmt_position->bind_param("s", $username);
    $stmt_position->execute();
    $result_position = $stmt_position->get_result();

    if ($result_position->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'لم يتم العثور على المستخدم.']);
        exit();
    }

    $position = $result_position->fetch_assoc()['position'];
    $stmt_position->close();

    // حساب الوقت المستغرق من وقت إدخال السجل إلى وقت الرد عليه
    $responseDuration = strtotime($responseTime) - strtotime($createdAt);
    $responseDurationFormatted = gmdate("H:i:s", $responseDuration);

    // حساب الوقت المستغرق من وقت فتح الصفحة إلى وقت الرد
    $openedAt = $_SESSION['record_opened_at'] ?? date('Y-m-d H:i:s'); // وقت فتح السجل
    $responseDurationFromOpen = strtotime($responseTime) - strtotime($openedAt);
    $responseDurationFromOpenFormatted = gmdate("H:i:s", $responseDurationFromOpen);

   
    // تحديث حالة الطلب مع تسجيل وقت الرد
    $sql_update = "UPDATE records SET 
        matching_status = ?, 
        responded_by_user = ?, 
        responded_by_position = ?, 
        response_details = ?, 
        response_duration = ?, 
        response_duration_from_open = ?, 
        response_time = ?, 
        is_locked = FALSE, 
        locked_by_user = NULL 
        WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);

    if (!$stmt_update) {
        echo json_encode(['success' => false, 'message' => 'فشل في تحضير الاستعلام: ' . $conn->error]);
        exit();
    }

    $stmt_update->bind_param(
        "sssssssi", 
        $status, 
        $username, 
        $position, 
        $responseDetails, 
        $responseDurationFormatted, 
        $responseDurationFromOpenFormatted, 
        $responseTime, // وقت الرد
        $recordId
    );

    if ($stmt_update->execute()) {
        // التحقق من وجود سجلات معلقة بعد الرد
        $sql_check_pending = "SELECT id FROM records WHERE matching_status = 'pending' AND created_by_user = ? LIMIT 1";
        $stmt_check_pending = $conn->prepare($sql_check_pending);
        $stmt_check_pending->bind_param("s", $username);
        $stmt_check_pending->execute();
        $result_check_pending = $stmt_check_pending->get_result();

        if ($result_check_pending->num_rows === 0) {
            echo json_encode(['success' => true, 'message' => 'لا توجد طلبات معلقة.']);
        } else {
            echo json_encode(['success' => true]);
        }

        $stmt_check_pending->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل في تنفيذ الاستعلام: ' . $stmt_update->error]);
    }

    $stmt_update->close();
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
    <title>الموافقة على المطابقة</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            max-height: 90px;
            border: 3px solid #0056b3;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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
        
        /* تحسينات لعرض الصور */
        .image-container {
            max-height: 500px;
            overflow: auto;
            margin: 20px auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f5f5f5;
            text-align: center;
            padding: 10px;
        }
        
        #carouselImage {
            max-width: 100%;
            max-height: 450px;
            object-fit: contain;
            transition: transform 0.3s ease;
            margin: 0 auto;
            display: block;
        }
        
        /* تحسين أزرار التالي والسابق */
        .carousel-control-prev, 
        .carousel-control-next {
            width: 50px;
            height: 50px;
            background-color: rgba(0, 123, 255, 0.8);
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0.9;
            transition: all 0.3s ease;
        }
        
        .carousel-control-prev {
            left: 15px;
            right: auto;
        }
        
        .carousel-control-next {
            right: 15px;
            left: auto;
        }
        
        .carousel-control-prev:hover, 
        .carousel-control-next:hover {
            background-color: rgba(0, 86, 179, 0.9);
            opacity: 1;
        }
        
        .carousel-control-prev-icon, 
        .carousel-control-next-icon {
            width: 1.5rem;
            height: 1.5rem;
        }

        .modal-image-container {
            width: 100%;
            height: 80vh;
            overflow: auto;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        
        #expandedImage {
            max-width: 100%;
            max-height: 100%;
            transform-origin: center center;
            cursor: grab;
            transition: transform 0.3s ease;
        }
        
        #expandedImage.grabbing {
            cursor: grabbing;
        }
        
        .modal-footer .btn {
            min-width: 100px;
        }
        
        /* تحسينات لأزرار التكبير/التصغير */
        .zoom-controls {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            background: rgba(255,255,255,0.7);
            padding: 5px;
            border-radius: 20px;
        }

               /* تحسينات للكاروسيل */
               .carousel-item {
            text-align: center;
        }
        
        .carousel-image {
            max-height: 450px;
            width: auto;
            max-width: 100%;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .carousel-image:hover {
            transform: scale(1.02);
        }
        
        .zoom-controls .btn {
            margin: 0 5px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.2rem;
        }
        
        /* تحسينات لتفاصيل الرد */
        #responseDetails {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin: 15px 0;
            resize: vertical;
            min-height: 100px;
        }
        
        /* تحسينات لأزرار الموافقة/الرفض */
        .btn-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
        }
        
        .btn-group .btn {
            min-width: 120px;
            padding: 10px 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="images/mathchinglogo.JPG" alt="Matching Program Logo">
        <h1>الموافقة على المطابقة</h1>
        <p>اتخاذ القرار بشأن طلبات المطابقة</p>
    </div>
    
    <div class="container mt-4">
        <!-- عداد الوقت -->
        <div id="timerContainer" class="text-center mb-4">
            <h5>الوقت المنقضي: <span id="timer">00:00:00</span></h5>
        </div>
        
        <!-- تفاصيل الطلب -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">تفاصيل الطلب</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>رقم الهاتف:</strong> <?php echo $record['phone_number']; ?></p>
                        <p><strong>طلب المطابقة:</strong> <?php echo $record['matching_request']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>تاريخ الإنشاء:</strong> <?php echo $record['created_at']; ?></p>
                        <p><strong>تم الإنشاء بواسطة:</strong> <?php echo $record['created_by_user']; ?></p>
                        <p><strong>القسم:</strong> <?php echo $record['created_by_position']; ?></p>
                        <p><strong>الحالة:</strong> <span class="badge bg-warning text-dark"><?php echo $record['matching_status']; ?></span></p>
                    </div>
                </div>
            </div>
        </div>

          <!-- عرض الصور -->
          <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0 text-center">عرض الصور</h5>
            </div>
            <div class="card-body position-relative">
                <?php if (!empty($images)): ?>
                    <div id="imageCarousel" class="carousel slide" data-bs-ride="false">
                        <div class="carousel-inner">
                            <?php foreach ($images as $index => $image): ?>
                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                    <img src="data:image/png;base64,<?php echo base64_encode($image); ?>" 
                                         class="carousel-image" 
                                         alt="صورة الطلب" 
                                         onclick="openImageModal('<?php echo base64_encode($image); ?>')">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#imageCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">السابق</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#imageCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">التالي</span>
                        </button>
                    </div>
                    <div class="zoom-controls d-none d-md-block">
                        <button class="btn btn-outline-primary btn-sm" onclick="zoomIn()"><i class="fas fa-search-plus"></i></button>
                        <button class="btn btn-outline-primary btn-sm" onclick="zoomOut()"><i class="fas fa-search-minus"></i></button>
                        <button class="btn btn-outline-primary btn-sm" onclick="resetZoom()"><i class="fas fa-sync-alt"></i></button>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning text-center">لا توجد صور متاحة لهذا السجل.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal لعرض الصورة الموسعة -->
        <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">عرض الصورة</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-image-container">
                            <img id="expandedImage" src="" alt="صورة مكبرة">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="btn-group">
                            <button class="btn btn-primary" onclick="zoomInModal()">
                                <i class="fas fa-search-plus"></i> تكبير
                            </button>
                            <button class="btn btn-primary" onclick="zoomOutModal()">
                                <i class="fas fa-search-minus"></i> تصغير
                            </button>
                            <button class="btn btn-secondary" onclick="resetZoomModal()">
                                <i class="fas fa-sync-alt"></i> إعادة تعيين
                            </button>
                        </div>
                        <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                            <i class="fas fa-check"></i> تم
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- تفاصيل الرد -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">تفاصيل الرد</h5>
            </div>
            <div class="card-body">
                <textarea id="responseDetails" class="form-control" placeholder="أدخل تفاصيل الرد هنا..." rows="4"></textarea>
            </div>
        </div>

        <!-- خيارات الرد -->
        <div class="text-center mb-5">
            <div class="btn-group">
                <button class="btn btn-success btn-lg" onclick="respondToRequest('approved')">
                    <i class="fas fa-check-circle me-2"></i>مطابقة
                </button>
                <button class="btn btn-danger btn-lg" onclick="respondToRequest('rejected')">
                    <i class="fas fa-times-circle me-2"></i>غير مطابقة
                </button>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        let scale = 1.0;
        let timerInterval;
        let modalScale = 1.0;
        let isDragging = false;
        let startPos = { x: 0, y: 0 };
        let translate = { x: 0, y: 0 };
        let currentTransform = { x: 0, y: 0, scale: 1 };

        // دالة لفتح الصورة في المودال
        function openImageModal(imageData) {
            const modal = new bootstrap.Modal(document.getElementById('imageModal'));
            const imgElement = document.getElementById('expandedImage');
            
            imgElement.src = `data:image/png;base64,${imageData}`;
            resetZoomModal(); // إعادة تعيين التكبير والموقع عند فتح صورة جديدة
            
            modal.show();
        }

        // دالة التكبير في المودال
        function zoomInModal() {
            modalScale += 0.2;
            updateModalImage();
        }

        // دالة التصغير في المودال
        function zoomOutModal() {
            if (modalScale > 0.2) {
                modalScale -= 0.2;
                updateModalImage();
            }
        }

        // إعادة تعيين التكبير والموقع في المودال
        function resetZoomModal() {
            modalScale = 1.0;
            translate = { x: 0, y: 0 };
            updateModalImage();
        }

        // تحديث صورة المودال مع التحويلات
        function updateModalImage() {
            const img = document.getElementById('expandedImage');
            img.style.transform = `translate(${translate.x}px, ${translate.y}px) scale(${modalScale})`;
        }

        // أحداث الماوس للتحريك
        document.getElementById('expandedImage').addEventListener('mousedown', (e) => {
            if (modalScale <= 1) return;
            
            isDragging = true;
            startPos = {
                x: e.clientX - translate.x,
                y: e.clientY - translate.y
            };
            document.getElementById('expandedImage').classList.add('grabbing');
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            
            translate.x = e.clientX - startPos.x;
            translate.y = e.clientY - startPos.y;
            updateModalImage();
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
            document.getElementById('expandedImage').classList.remove('grabbing');
        });

        // دالة لبدء عداد الوقت
        function startTimer() {
            const timerElement = document.getElementById('timer');
            let secondsElapsed = 0;

            timerInterval = setInterval(() => {
                secondsElapsed++;
                const hours = String(Math.floor(secondsElapsed / 3600)).padStart(2, '0');
                const minutes = String(Math.floor((secondsElapsed % 3600) / 60)).padStart(2, '0');
                const seconds = String(secondsElapsed % 60).padStart(2, '0');
                timerElement.textContent = `${hours}:${minutes}:${seconds}`;
            }, 1000);
        }

        // بدء عداد الوقت عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', () => {
            startTimer();
            <?php $_SESSION['record_opened_at'] = date('Y-m-d H:i:s'); ?>
        });

        // دالة للرد على الطلب
        function respondToRequest(status) {
            const responseDetails = document.getElementById('responseDetails').value;

            const confirmation = confirm("هل أنت متأكد من أنك تريد حفظ هذا الرد؟");
            if (!confirmation) return;

            fetch('matching_approval.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    status: status,
                    responseDetails: responseDetails
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.message === 'لا توجد طلبات معلقة.') {
                        alert(data.message);
                        window.location.href = 'matching_list.php';
                    } else {
                        alert("تم تحديث حالة الطلب بنجاح.");
                        clearInterval(timerInterval);
                        window.location.reload();
                    }
                } else {
                    alert("حدث خطأ أثناء تحديث حالة الطلب: " + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("حدث خطأ أثناء الاتصال بالخادم.");
            });
        }

        // دالة للتكبير في العرض الرئيسي
        function zoomIn() {
            scale += 0.1;
            updateCarouselImageScale();
        }

        // دالة للتصغير في العرض الرئيسي
        function zoomOut() {
            scale = Math.max(0.5, scale - 0.1);
            updateCarouselImageScale();
        }

        // إعادة تعيين التكبير في العرض الرئيسي
        function resetZoom() {
            scale = 1.0;
            updateCarouselImageScale();
        }

        // تحديث حجم الصورة في الكاروسيل
        function updateCarouselImageScale() {
            const activeImage = document.querySelector('.carousel-item.active .carousel-image');
            if (activeImage) {
                activeImage.style.transform = `scale(${scale})`;
            }
        }
    </script>
</body>
</html>