<?php 
include 'db.php'; 
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$msg = "";
$role = $_SESSION['role'];

// LOGIC: Upload Material (Faculty Only)
if ($role == 'faculty' && isset($_POST['upload_material'])) {
    $course_id = $_POST['course_id'];
    $title = htmlspecialchars($_POST['title']);
    $user_id = $_SESSION['user_id'];
    
    // File Processing
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir); // Create folder if missing
    
    $file_name = basename($_FILES["file"]["name"]);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $new_name = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "", $file_name); // Sanitize name
    $target_file = $target_dir . $new_name;
    
    // Security: Allow only specific types
    $allowed = ['pdf', 'docx', 'doc', 'pptx', 'ppt', 'zip', 'txt'];
    
    if (in_array($file_ext, $allowed)) {
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("INSERT INTO materials (course_id, uploaded_by, title, file_path) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $course_id, $user_id, $title, $target_file);
            if($stmt->execute()) {
                $msg = "<p class='status-active'>File uploaded successfully!</p>";
            }
        } else {
            $msg = "<p class='status-closed'>Error uploading file.</p>";
        }
    } else {
        $msg = "<p class='status-closed'>Invalid file type. Allowed: PDF, DOCX, PPTX, ZIP.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Materials | Ashesi LMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="sidebar">
        <h2>ASHESI LMS</h2>
        <a href="<?php echo ($role=='faculty') ? 'faculty_dashboard.php' : 'student_dashboard.php'; ?>">Dashboard</a>
        <a href="#" class="active">Course Materials</a>
        <a href="assignments.php">Assignments & Grades</a> 
        <a href="reports.php">Reports</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="main-content">
        <header>
            <h1>Course Materials</h1>
            <div class="user-info">User: <strong><?php echo $_SESSION['name']; ?></strong></div>
        </header>

        <?php if($role == 'faculty'): ?>
        <div class="card">
            <h3>Upload New Material</h3>
            <?php echo $msg; ?>
            <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 15px; align-items: flex-end;">
                <div class="form-group" style="flex: 1;">
                    <label>Course</label>
                    <select name="course_id" required>
                        <?php
                        $courses = $conn->query("SELECT * FROM courses");
                        while($c = $courses->fetch_assoc()) {
                            echo "<option value='{$c['course_id']}'>{$c['course_code']} - {$c['course_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 2;">
                    <label>Document Title</label>
                    <input type="text" name="title" placeholder="e.g. Lecture 5 Slides" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Select File</label>
                    <input type="file" name="file" required style="border: none; padding-top: 10px;">
                </div>
                <div class="form-group">
                    <button type="submit" name="upload_material" class="btn-primary">Upload</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="card">
            <h3>Available Resources</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Course</th>
                        <th>Title</th>
                        <th>Uploaded By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT m.*, c.course_code, c.course_name, u.full_name 
                            FROM materials m 
                            JOIN courses c ON m.course_id = c.course_id 
                            JOIN users u ON m.uploaded_by = u.user_id 
                            ORDER BY m.upload_date DESC";
                    $res = $conn->query($sql);

                    if ($res->num_rows > 0) {
                        while($row = $res->fetch_assoc()) {
                            echo "<tr>
                                <td>".date('M d, Y', strtotime($row['upload_date']))."</td>
                                <td><span style='font-weight:bold; color:#880000'>{$row['course_code']}</span></td>
                                <td>{$row['title']}</td>
                                <td>{$row['full_name']}</td>
                                <td>
                                    <a href='{$row['file_path']}' download class='btn-primary' style='padding: 5px 10px; font-size: 12px; text-decoration: none;'>Download</a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center'>No materials uploaded yet.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>