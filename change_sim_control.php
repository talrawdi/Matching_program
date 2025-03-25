<?php
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// معلومات المستخدم من الجلسة
$username = $_SESSION['username'];
$access_rights = $_SESSION['access_rights'];

// الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "products_db2");

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// هذه السطور ضرورية لضبط الترميز
$conn->set_charset("utf8mb4");
$conn->query("SET NAMES 'utf8mb4'");
$conn->query("SET CHARACTER SET utf8mb4");
$conn->query("SET SESSION collation_connection = 'utf8mb4_general_ci'");


// معالجة إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $number = $_POST['number'];
    $problem = $_POST['problem'];
    $branch = $_POST['branch'];
    $employee = $_POST['employee'];
    $add_date = $_POST['add_date'];
    $observe = $_POST['observe'];
    $remark = $_POST['remark'];

    // معالجة الصورة
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = file_get_contents($_FILES['image']['tmp_name']);
    }

    // إدخال البيانات في قاعدة البيانات
    $sql = "INSERT INTO products (number, problem, branch, employee, add_date, observe, remark, img) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssb", $number, $problem, $branch, $employee, $add_date, $observe, $remark, $image);

    if ($stmt->execute()) {
        echo "<script>alert('تم إدخال البيانات بنجاح');</script>";
    } else {
        echo "<script>alert('حدث خطأ أثناء إدخال البيانات');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الرقابة على تغيير الشرائح</title>
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
            max-height: 85px; /* تعديل الحجم ليطابق ملف matching_list.php */
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
        .form-label {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="images/mathchinglogo.JPG" alt="Matching Program Logo"> <!-- إزالة الشعار القديم وإضافة الجديد -->
        <h1>الرقابة على تغيير الشرائح</h1>
        <p>إدارة ومراقبة عمليات تغيير الشرائح</p>
    </div>
    <div class="main-container">
        <form method="POST" action="change_sim_control.php" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6">
                    <label for="number" class="form-label">رقم الهاتف:</label>
                    <input type="text" id="number" name="number" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label for="problem" class="form-label">المشكلة:</label>
                    <select id="problem" name="problem" class="form-select" required>
                        <option value="">اختر المشكلة</option>
                        <option value="لا يوجد عقد">لا يوجد عقد</option>
                        <option value="بضمانة موظف">بضمانة موظف</option>
                        <option value="لا يوجد بطاقة">لا يوجد بطاقة</option>
                        <option value="ناقص وجهة البطاقة">ناقص وجهة البطاقة</option>
                        <option value="بيانات العقد غير صحيحة">بيانات العقد غير صحيحة</option>
                        <option value="ناقص رقم الهاتف في العقد">ناقص رقم الهاتف في العقد</option>
                        <option value="ناقص خلفية البطاقة">ناقص خلفية البطاقة</option>
                        <option value="تغيير شريحة بالخطأ">تغيير شريحة بالخطأ</option>
                        <option value="وثيقة غير واضحة">وثيقة غير واضحة</option>
                        <option value="تغيير شريحة ببطاقة شخص آخر">تغيير شريحة ببطاقة شخص آخر</option>
                        <option value="تم ارسال الإلتزام لاحقا">تم ارسال الإلتزام لاحقا</option>
                        <option value="بطاقة + إلتزام">بطاقة + إلتزام</option>
                        <option value="ناقص الشريحة في الالتزام">ناقص الشريحة في الالتزام</option>
                        <option value="لمشكلة في المرفقات">لمشكلة في المرفقات</option>
                        <option value="إلتزام">إلتزام</option>
                        <option value="بطاقة مزورة">بطاقة مزورة</option>
                        <option value="صورة ورقية">صورة ورقية</option>
                        <option value="وثيقة غير معتمدة">وثيقة غير معتمدة</option>
                        <option value="أخرى">أخرى</option>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <label for="branch" class="form-label">الفرع:</label>
                    <select id="branch" name="branch" class="form-select" required>
    <option value="">اختر الفرع</option>
    <option value="By Dealer">By Dealer</option>
    <option value="RADAA">RADAA</option>
    <option value="MUKALLA">MUKALLA</option>
    <option value="TAIZ">TAIZ</option>
    <option value="IBB">IBB</option>
    <option value="HODAIDAH 2">HODAIDAH 2</option>
    <option value="Taiz Alhoban">Taiz Alhoban</option>
    <option value="SANAA 4">SANAA 4</option>
    <option value="SANA'A 3">SANA'A 3</option>
    <option value="HODAIDAH 1">HODAIDAH 1</option>
    <option value="BAIDA">BAIDA</option>
    <option value="DHAMAR">DHAMAR</option>
    <option value="SANAA 1">SANAA 1</option>
    <option value="ADDALEA">ADDALEA</option>
    <option value="SGH">SGH</option>
    <option value="YAFEA">YAFEA</option>
    <option value="MAAREB">MAAREB</option>
    <option value="SANA'A 2">SANA'A 2</option>
    <option value="BAIT ALFAQEEH">BAIT ALFAQEEH</option>
    <option value="HAJJAH">HAJJAH</option>
    <option value="ALGAIDAH">ALGAIDAH</option>
    <option value="HO VIP">HO VIP</option>
    <option value="SHABWAH">SHABWAH</option>
    <option value="Mini VIP Services">Mini VIP Services</option>
    <option value="HO Corporate sales">HO Corporate sales</option>
    <option value="SAADAH">SAADAH</option>
    <option value="SAYOUN">SAYOUN</option>
    <option value="Other">Other</option>
</select>
                </div>
                <div class="col-md-6">
                    <label for="employee" class="form-label">اسم الموظف:</label>
                    <input type="text" id="employee" name="employee" class="form-control" required>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <label for="add_date" class="form-label">تاريخ الإضافة:</label>
                    <input type="date" id="add_date" name="add_date" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label for="observe" class="form-label">المراقب:</label>
                    <input type="text" id="observe" name="observe" class="form-control" value="<?php echo $username; ?>" readonly>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <label for="remark" class="form-label">ملاحظات:</label>
                    <textarea id="remark" name="remark" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <label for="image" class="form-label">اختر صورة:</label>
                    <input type="file" id="image" name="image" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12 text-center">
                    <img id="preview" src="#" alt="Preview" style="max-width: 100%; display: none;">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12 text-center">
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </div>
        </form>
    </div>

    <!-- JavaScript لعرض الصورة المختارة -->
    <script>
        document.getElementById('image').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview').src = e.target.result;
                    document.getElementById('preview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>