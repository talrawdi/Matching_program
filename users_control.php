<?php
session_start();
$conn = new mysqli("localhost", "root", "", "products_db2");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// دالة لتحميل المستخدمين من قاعدة البيانات
function loadUsers($conn) {
    $sql = "SELECT * FROM users ORDER BY user_id DESC";
    $result = $conn->query($sql);
    $users = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    return $users;
}

// دالة للتحقق من وجود المستخدم
function isUserExists($conn, $username) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    return $count > 0;
}

// دالة لإضافة مستخدم جديد
function addUser($conn, $username, $password, $access_rights, $position, $permissions) {
    if (isUserExists($conn, $username)) {
        return false; // المستخدم موجود مسبقاً
    }
    $stmt = $conn->prepare("INSERT INTO users (username, password, access_rights, position, Change_Sim_Entry, Report, SendMatching, MatchingList, RecordStatusViewer, MatchingReport) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssiiiiii", $username, $password, $access_rights, $position, $permissions['Change_Sim_Entry'], $permissions['Report'], $permissions['SendMatching'], $permissions['MatchingList'], $permissions['RecordStatusViewer'], $permissions['MatchingReport']);
    return $stmt->execute();
}

// دالة لإضافة مجموعة مستخدمين من ملف Excel
function addUsersFromExcel($conn, $filePath) {
    require_once 'vendor/autoload.php'; // مكتبة PHPExcel
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();
    $skippedUsers = [];
    $addedUsers = [];

    foreach ($rows as $row) {
        $username = $row[0];
        $password = $row[1];
        $access_rights = $row[2];
        $position = $row[3];
        $permissions = [
            'Change_Sim_Entry' => $row[4] ?? 0,
            'Report' => $row[5] ?? 0,
            'SendMatching' => $row[6] ?? 0,
            'MatchingList' => $row[7] ?? 0,
            'RecordStatusViewer' => $row[8] ?? 0,
            'MatchingReport' => $row[9] ?? 0
        ];

        if (isUserExists($conn, $username)) {
            $skippedUsers[] = $username;
        } elseif (!in_array($username, $addedUsers)) {
            addUser($conn, $username, $password, $access_rights, $position, $permissions);
            $addedUsers[] = $username;
        }
    }

    return $skippedUsers;
}

// دالة لتحديث مستخدم
function updateUser($conn, $user_id, $username, $password, $access_rights, $position, $permissions) {
    $stmt = $conn->prepare("UPDATE users SET username = ?, password = ?, access_rights = ?, position = ?, Change_Sim_Entry = ?, Report = ?, SendMatching = ?, MatchingList = ?, RecordStatusViewer = ?, MatchingReport = ? WHERE user_id = ?");
    $stmt->bind_param("ssssiiiiiii", $username, $password, $access_rights, $position, $permissions['Change_Sim_Entry'], $permissions['Report'], $permissions['SendMatching'], $permissions['MatchingList'], $permissions['RecordStatusViewer'], $permissions['MatchingReport'], $user_id);
    return $stmt->execute();
}

// دالة لحذف مستخدم
function deleteUser($conn, $user_id) {
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    return $stmt->execute();
}

// دالة للبحث عن مستخدم
function searchUser($conn, $search_query) {
    $search_query = "%$search_query%";
    $stmt = $conn->prepare("SELECT * FROM users WHERE username LIKE ?");
    $stmt->bind_param("s", $search_query);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    return $users;
}

// دالة لحفظ الصلاحيات
function savePermissions($conn, $user_id, $permissions) {
    $stmt = $conn->prepare("UPDATE users SET Change_Sim_Entry = ?, Report = ?, SendMatching = ?, MatchingList = ?, RecordStatusViewer = ?, MatchingReport = ? WHERE user_id = ?");
    $stmt->bind_param("iiiiiii", $permissions['Change_Sim_Entry'], $permissions['Report'], $permissions['SendMatching'], $permissions['MatchingList'], $permissions['RecordStatusViewer'], $permissions['MatchingReport'], $user_id);
    return $stmt->execute();
}

// معالجة النماذج المرسلة
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $access_rights = $_POST['access_rights'];
        $position = $_POST['position'];
        $permissions = [
            'Change_Sim_Entry' => isset($_POST['Change_Sim_Entry']) ? 1 : 0,
            'Report' => isset($_POST['Report']) ? 1 : 0,
            'SendMatching' => isset($_POST['SendMatching']) ? 1 : 0,
            'MatchingList' => isset($_POST['MatchingList']) ? 1 : 0,
            'RecordStatusViewer' => isset($_POST['RecordStatusViewer']) ? 1 : 0,
            'MatchingReport' => isset($_POST['MatchingReport']) ? 1 : 0
        ];
        if (addUser($conn, $username, $password, $access_rights, $position, $permissions)) {
            echo "<script>alert('User added successfully!');</script>";
        } else {
            echo "<script>alert('User already exists!');</script>";
        }
    } elseif (isset($_POST['update_user'])) {
        $user_id = $_POST['user_id'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $access_rights = $_POST['access_rights'];
        $position = $_POST['position'];
        $permissions = [
            'Change_Sim_Entry' => isset($_POST['Change_Sim_Entry']) ? 1 : 0,
            'Report' => isset($_POST['Report']) ? 1 : 0,
            'SendMatching' => isset($_POST['SendMatching']) ? 1 : 0,
            'MatchingList' => isset($_POST['MatchingList']) ? 1 : 0,
            'RecordStatusViewer' => isset($_POST['RecordStatusViewer']) ? 1 : 0,
            'MatchingReport' => isset($_POST['MatchingReport']) ? 1 : 0
        ];
        if (updateUser($conn, $user_id, $username, $password, $access_rights, $position, $permissions)) {
            echo "<script>alert('User updated successfully!');</script>";
        } else {
            echo "<script>alert('Error updating user.');</script>";
        }
    } elseif (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        if (deleteUser($conn, $user_id)) {
            echo "<script>alert('User deleted successfully!');</script>";
        } else {
            echo "<script>alert('Error deleting user.');</script>";
        }
    } elseif (isset($_POST['search'])) {
        $search_query = $_POST['search_query'];
        $users = searchUser($conn, $search_query);
    } elseif (isset($_POST['save_permissions'])) {
        $user_id = $_POST['user_id'];
        $permissions = [
            'Change_Sim_Entry' => isset($_POST['Change_Sim_Entry']) ? 1 : 0,
            'Report' => isset($_POST['Report']) ? 1 : 0,
            'SendMatching' => isset($_POST['SendMatching']) ? 1 : 0,
            'MatchingList' => isset($_POST['MatchingList']) ? 1 : 0,
            'RecordStatusViewer' => isset($_POST['RecordStatusViewer']) ? 1 : 0,
            'MatchingReport' => isset($_POST['MatchingReport']) ? 1 : 0
        ];
        if (savePermissions($conn, $user_id, $permissions)) {
            echo "<script>alert('Permissions saved successfully!');</script>";
        } else {
            echo "<script>alert('Error saving permissions.');</script>";
        }
    } elseif (isset($_POST['import_users'])) {
        if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == UPLOAD_ERR_OK) {
            $filePath = $_FILES['excel_file']['tmp_name'];
            $skippedUsers = addUsersFromExcel($conn, $filePath);
            if (!empty($skippedUsers)) {
                echo "<script>alert('The following users were skipped because they already exist: " . implode(', ', $skippedUsers) . "');</script>";
            } else {
                echo "<script>alert('All users were added successfully!');</script>";
            }
        } else {
            echo "<script>alert('Error uploading file.');</script>";
        }
    }
}

// تحميل المستخدمين عند فتح الصفحة
$users = isset($users) ? $users : loadUsers($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
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
        .container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin: 20px;
        }
        .form-section {
            flex: 1;
            min-width: 300px;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .form-section h2 {
            margin-bottom: 15px;
            color: #555;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group input[type="checkbox"] {
            width: auto;
        }
        button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #0056b3;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #a71d2a;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: #fff;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .permissions-form {
            margin-top: 20px;
        }
        .permissions-form button.cancel {
            background-color: #dc3545;
        }
        .permissions-form button.cancel:hover {
            background-color: #a71d2a;
        }
    </style>
    <script>
        // تأكيد الحذف
        function confirmDelete() {
            return confirm("Are you sure you want to delete this user?");
        }
    </script>
</head>
<body>
    <div class="header">
        <img src="images/mathchinglogo.JPG" alt="Matching Program Logo"> <!-- Updated logo -->
        <h1>User Management System</h1>
        <p>Manage users, permissions, and more with ease</p>
    </div>
    <div class="container">
        <!-- إضافة مستخدم -->
        <div class="form-section">
            <h2>Add User</h2>
            <form method="post" action="users_control.php">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Access Rights:</label>
                    <select name="access_rights">
                        <option value="Admin">Admin</option>
                        <option value="User">User</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Position:</label>
                    <select name="position">
                        <option value="Back_Office">Back Office</option>
                        <option value="Activation">Activation</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Permissions:</label>
                    <label><input type="checkbox" name="Change_Sim_Entry"> Change Sim Entry</label>
                    <label><input type="checkbox" name="Report"> Report</label>
                    <label><input type="checkbox" name="SendMatching"> Send Matching</label>
                    <label><input type="checkbox" name="MatchingList"> Matching List</label>
                    <label><input type="checkbox" name="RecordStatusViewer"> Record Status Viewer</label>
                    <label><input type="checkbox" name="MatchingReport"> Matching Report</label>
                </div>
                <button type="submit" name="add_user" class="btn-success">Add User</button>
            </form>
        </div>

        <!-- البحث عن مستخدم -->
        <div class="form-section">
            <h2>Search User</h2>
            <form method="post" action="users_control.php">
                <div class="form-group">
                    <label>Search Username:</label>
                    <input type="text" name="search_query">
                    <button type="submit" name="search" class="btn-primary">Search</button>
                </div>
            </form>
        </div>

        <!-- استيراد المستخدمين -->
        <div class="form-section">
            <h2>Import Users from Excel</h2>
            <form method="post" action="users_control.php" enctype="multipart/form-data" onsubmit="return confirmImport();">
                <div class="form-group">
                    <label>Upload Excel File:</label>
                    <input type="file" name="excel_file" accept=".xlsx, .xls" required>
                </div>
                <button type="submit" name="import_users" class="btn-success">Import Users</button>
            </form>
        </div>

        <!-- تعديل الصلاحيات -->
        <div class="form-section">
            <h2>Edit Permissions</h2>
            <?php if (isset($_POST['edit_user'])): ?>
            <?php
            $user_id = $_POST['user_id'];
            $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            ?>
            <div class="permissions-form">
                <form method="post" action="users_control.php">
                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                    <label><input type="checkbox" name="Change_Sim_Entry" <?php echo $user['Change_Sim_Entry'] ? 'checked' : ''; ?>> Change Sim Entry</label>
                    <label><input type="checkbox" name="Report" <?php echo $user['Report'] ? 'checked' : ''; ?>> Report</label>
                    <label><input type="checkbox" name="SendMatching" <?php echo $user['SendMatching'] ? 'checked' : ''; ?>> Send Matching</label>
                    <label><input type="checkbox" name="MatchingList" <?php echo $user['MatchingList'] ? 'checked' : ''; ?>> Matching List</label>
                    <label><input type="checkbox" name="RecordStatusViewer" <?php echo $user['RecordStatusViewer'] ? 'checked' : ''; ?>> Record Status Viewer</label>
                    <label><input type="checkbox" name="MatchingReport" <?php echo $user['MatchingReport'] ? 'checked' : ''; ?>> Matching Report</label>
                    <button type="submit" name="save_permissions" class="btn-success">Save Permissions</button>
                    <button type="button" class="cancel btn-danger" onclick="window.location.href=window.location.href;">Cancel</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- جدول المستخدمين -->
    <table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Password</th>
            <th>Access Rights</th>
            <th>Position</th>
            <th>Change Sim Entry</th>
            <th>Report</th>
            <th>Send Matching</th>
            <th>Matching List</th>
            <th>Record Status Viewer</th>
            <th>Matching Report</th>
            <th>Actions</th>
        </tr>
        <?php if (isset($users) && !empty($users)): ?>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['user_id']; ?></td>
                <td><?php echo $user['username']; ?></td>
                <td>****</td>
                <td><?php echo $user['access_rights']; ?></td>
                <td><?php echo $user['position']; ?></td>
                <td><?php echo $user['Change_Sim_Entry'] ? 'Yes' : 'No'; ?></td>
                <td><?php echo $user['Report'] ? 'Yes' : 'No'; ?></td>
                <td><?php echo $user['SendMatching'] ? 'Yes' : 'No'; ?></td>
                <td><?php echo $user['MatchingList'] ? 'Yes' : 'No'; ?></td>
                <td><?php echo $user['RecordStatusViewer'] ? 'Yes' : 'No'; ?></td>
                <td><?php echo $user['MatchingReport'] ? 'Yes' : 'No'; ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                        <button type="submit" name="edit_user" class="btn-primary">Edit</button>
                    </form>
                    <form method="post" style="display:inline;" onsubmit="return confirmDelete();">
                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                        <button type="submit" name="delete_user" class="btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="12" style="text-align:center;">No users found.</td>
            </tr>
        <?php endif; ?>
    </table>
</body>
</html>