<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الأدمن</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
            font-size: 2.5rem;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 1rem;
            font-style: italic;
            color: #d1e7ff;
        }
        
        /* شريط التنقل الأفقي الجديد */
        .nav-container {
            background: linear-gradient(90deg, #0056b3, #003f7f);
            padding: 0 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .nav-menu {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            padding: 0;
            margin: 0;
            list-style: none;
        }
        .nav-item {
            position: relative;
            margin: 0 5px;
        }
        .nav-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            cursor: pointer;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transform: perspective(1px) translateZ(0);
            backface-visibility: hidden;
        }
        .nav-btn i {
            margin-left: 8px;
            font-size: 1.1rem;
        }
        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: perspective(1px) translateZ(0) scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        .nav-btn:active {
            transform: perspective(1px) translateZ(0) scale(0.98);
        }
        .nav-btn.active {
            background: rgba(255, 255, 255, 0.3);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .nav-item:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: #fff;
            transition: all 0.3s ease;
        }
        .nav-item:hover:after {
            width: 80%;
            left: 10%;
        }
        
        /* المحتوى الرئيسي */
        .content {
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            margin: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            min-height: calc(100vh - 250px);
        }
        .content iframe {
            width: 100%;
            height: 100%;
            min-height: 600px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background: white;
        }
        
        /* زر تسجيل الخروج */
        .logout-btn {
            background: linear-gradient(90deg, #dc3545, #a71d2a);
            margin-right: auto;
            margin-left: 20px;
        }
        .logout-btn:hover {
            background: linear-gradient(90deg, #a71d2a, #7f141e);
        }
        
        /* تأثيرات 3D للزر عند النقر */
        @keyframes click-wave {
            0% {
                transform: perspective(1px) translateZ(0);
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4);
            }
            70% {
                transform: perspective(1px) translateZ(0) scale(0.95);
                box-shadow: 0 0 0 10px rgba(255, 255, 255, 0);
            }
            100% {
                transform: perspective(1px) translateZ(0) scale(1);
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0);
            }
        }
        .nav-btn:active {
            animation: click-wave 0.3s ease-out;
        }
        
        /* تصميم متجاوب */
        @media (max-width: 992px) {
            .nav-menu {
                justify-content: flex-start;
                overflow-x: auto;
                padding-bottom: 10px;
                flex-wrap: nowrap;
            }
            .nav-item {
                margin: 0 3px;
            }
            .nav-btn {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
            .nav-btn i {
                margin-left: 5px;
                font-size: 1rem;
            }
        }
    </style>
</head> 
<body>
    <div class="header">
        <img src="images/mathchinglogo.JPG" alt="Matching Program Logo"> <!-- Updated logo -->
        <div>
            <h1>لوحة تحكم الأدمن</h1>
            <p>إدارة المستخدمين، التقارير، والإعدادات بسهولة</p>
        </div>
    </div>
    <div class="main-container">
        <!-- الشريط الجانبي -->
        <div class="sidebar" id="sidebar">
            <div class="toggle-btn" onclick="toggleSidebar()">☰</div>
            <button class="btn btn-primary" onclick="loadPage('users_control.php')">
                <i class="fas fa-users"></i>
                <span class="btn-text">التحكم بالمستخدمين</span>
            </button>
            <button class="btn btn-primary" onclick="loadPage('control_report.php')">
                <i class="fas fa-file-alt"></i>
                <span class="btn-text">تقارير رقابة السيم</span>
            </button>
            <button class="btn btn-primary" onclick="loadPage('matching_report.php')">
                <i class="fas fa-chart-bar"></i>
                <span class="btn-text">تقارير المطابقة</span>
            </button>
            <!-- تم إلغاء الزر الذي يسمح بفتح صفحة main_user.php مباشرة -->
            <button class="btn btn-primary" onclick="loadPage('settings.php')">
                <i class="fas fa-cogs"></i>
                <span class="btn-text">الإعدادات</span>
            </button>
            <button class="btn btn-primary" onclick="loadPage('database_control.php')">
                <i class="fas fa-database"></i>
                <span class="btn-text">التحكم بقاعدة البيانات</span>
            </button>
            <button class="btn btn-primary" onclick="loadPage('employee_details.php')">
                <i class="fas fa-id-card"></i>
                <span class="btn-text">إضافة تفاصيل الموظفين</span>
            </button>
            <button class="btn btn-danger" onclick="logout()">
                <i class="fas fa-sign-out-alt"></i>
                <span class="btn-text">تسجيل الخروج</span>
            </button>
        </div>

        <!-- لوحة المحتوى -->
        <div class="content">
            <iframe id="contentFrame" src=""></iframe>
        </div>
    </div>

    <!-- JavaScript لمعالجة الأحداث -->
    <script>
        // دالة لتحميل الصفحة الفرعية داخل iframe
        function loadPage(page) {
            document.getElementById('contentFrame').src = page;
        }

        // دالة لتسجيل الخروج
        function logout() {
            fetch('logout.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'login.php';
                    } else {
                        alert('حدث خطأ أثناء تسجيل الخروج.');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // دالة لطي وتوسيع الشريط الجانبي
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            const btnTexts = document.querySelectorAll('.btn-text');
            btnTexts.forEach(text => {
                text.style.display = sidebar.classList.contains('collapsed') ? 'none' : 'inline';
            });
        }
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>