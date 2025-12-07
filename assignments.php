<?php 
include 'db.php'; 
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$msg = "";

// --- FACULTY LOGIC ---
if ($role == 'faculty') {
    // 1. Create Assignment
    if (isset($_POST['create_assignment'])) {
        $course_id = $_POST['course_id'];
        $title = htmlspecialchars($_POST['title']);
        $desc = htmlspecialchars($_POST['description']);
        $due = $_POST['due_date'];
        
        $stmt = $conn->prepare("INSERT INTO assignments (course_id, title, description, due_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $course_id, $title, $desc, $due);
        if ($stmt->execute()) $msg = "<p class='status-active'>Assignment Created!</p>";
    }

    // 2. Grade Submission
    if (isset($_POST['submit_grade'])) {
        $sub_id = $_POST['submission_id'];
        $score = $_POST['score'];
        $feedback = htmlspecialchars($_POST['feedback']);
        
        $stmt = $conn->prepare("UPDATE submissions SET score = ?, feedback = ? WHERE submission_id = ?");
        $stmt->bind_param("isi", $score, $feedback, $sub_id);
        if ($stmt->execute()) $msg = "<p class='status-active'>Grade Saved!</p>";
    }
}

// --- STUDENT LOGIC ---
if ($role == 'student') {
    // Submit Assignment
    if (isset($_POST['upload_submission'])) {
        $assign_id = $_POST['assignment_id'];
        
        // File Upload Logic
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir);
        $file_name = time() . "_sub_" . basename($_FILES["file"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("INSERT INTO submissions (assignment_id, student_id, file_path) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $assign_id, $user_id, $target_file);
            if ($stmt->execute()) $msg = "<p class='status-active'>Assignment Submitted!</p>";
        } else {
            $msg = "<p class='status-closed'>Error uploading file.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assignments | Ashesi LMS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .grade-box { background: #f9f9f9; padding: 15px; border-left: 4px solid #880000; margin-top: 10px; }
        .sub-table td { vertical-align: middle; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>ASHESI LMS</h2>
        <a href="<?php echo ($role=='faculty') ? 'faculty_dashboard.php' : 'student_dashboard.php'; ?>">Dashboard</a>
        <a href="course_materials.php">Course Materials</a>
        <a href="assignments.php" class="active">Assignments</a> <a href="reports.php">Reports</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="main-content">
        <header>
            <h1>Assignments & Grades</h1>
            <div class="user-info">User: <strong><?php echo $_SESSION['name']; ?></strong></div>
        </header>
        
        <?php echo $msg; ?>

        <?php if($role == 'faculty'): ?>
            
            <div class="card">
                <h3>Create New Assignment</h3>
                <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Course</label>
                        <select name="course_id" required>
                            <?php
                            $c_res = $conn->query("SELECT * FROM courses");
                            while($c = $c_res->fetch_assoc()) {
                                echo "<option value='{$c['course_id']}'>{$c['course_code']} - {$c['course_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="datetime-local" name="due_date" required>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Title</label>
                        <input type="text" name="title" placeholder="e.g. Midterm Project" required>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label>Description</label>
                        <input type="text" name="description" placeholder="Instructions..." required>
                    </div>
                    <button type="submit" name="create_assignment" class="btn-primary" style="grid-column: span 2;">Post Assignment</button>
                </form>
            </div>

            <div class="card">
                <h3>Student Submissions</h3>
                <table class="sub-table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Assignment</th>
                            <th>Student</th>
                            <th>Submitted File</th>
                            <th>Grade / Feedback</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT s.*, u.full_name, a.title, c.course_code 
                                FROM submissions s
                                JOIN assignments a ON s.assignment_id = a.assignment_id
                                JOIN users u ON s.student_id = u.user_id
                                JOIN courses c ON a.course_id = c.course_id
                                ORDER BY s.submission_date DESC";
                        $subs = $conn->query($sql);

                        if ($subs->num_rows > 0) {
                            while($row = $subs->fetch_assoc()) {
                                echo "<tr>
                                    <td>{$row['course_code']}</td>
                                    <td>{$row['title']}</td>
                                    <td>{$row['full_name']}</td>
                                    <td><a href='{$row['file_path']}' download class='status-active' style='text-decoration:none;'>Download</a></td>
                                    <form method='POST'>
                                        <td>
                                            <input type='hidden' name='submission_id' value='{$row['submission_id']}'>
                                            <input type='number' name='score' placeholder='Score' value='{$row['score']}' style='width:60px' required> / 100
                                            <br><input type='text' name='feedback' placeholder='Feedback' value='{$row['feedback']}' style='width:100%; margin-top:5px;'>
                                        </td>
                                        <td><button type='submit' name='submit_grade' class='btn-primary' style='padding:5px 10px; font-size:12px;'>Save</button></td>
                                    </form>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No submissions yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>


        <?php if($role == 'student'): ?>
            <div class="card">
                <h3>Pending Assignments</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Assignment</th>
                            <th>Due Date</th>
                            <th>Action / Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get assignments and check if this specific student has already submitted
                        $sql = "SELECT a.*, c.course_code, c.course_name, 
                                (SELECT score FROM submissions WHERE assignment_id = a.assignment_id AND student_id = $user_id) as score,
                                (SELECT feedback FROM submissions WHERE assignment_id = a.assignment_id AND student_id = $user_id) as feedback,
                                (SELECT file_path FROM submissions WHERE assignment_id = a.assignment_id AND student_id = $user_id) as submitted_file
                                FROM assignments a 
                                JOIN courses c ON a.course_id = c.course_id 
                                ORDER BY a.due_date DESC";
                        
                        $assigns = $conn->query($sql);

                        while($row = $assigns->fetch_assoc()) {
                            echo "<tr>
                                <td><span style='font-weight:bold; color:#880000'>{$row['course_code']}</span></td>
                                <td>
                                    <strong>{$row['title']}</strong><br>
                                    <small>{$row['description']}</small>
                                </td>
                                <td>".date('M d, h:i A', strtotime($row['due_date']))."</td>
                                <td>";
                            
                            if ($row['submitted_file']) {
                                // Already submitted: Show Grade
                                echo "<div class='grade-box'>";
                                if ($row['score'] !== null) {
                                    echo "<strong>Grade: <span style='font-size:18px'>{$row['score']}/100</span></strong><br>";
                                    echo "<small>Feedback: {$row['feedback']}</small>";
                                } else {
                                    echo "<span class='status-active'>Submitted</span><br><small>Pending Grading</small>";
                                }
                                echo "</div>";
                            } else {
                                // Not submitted: Show Upload Form
                                echo "<form method='POST' enctype='multipart/form-data' style='display:flex; gap:5px; align-items:center;'>
                                        <input type='hidden' name='assignment_id' value='{$row['assignment_id']}'>
                                        <input type='file' name='file' required style='width:180px;'>
                                        <button type='submit' name='upload_submission' class='btn-primary' style='padding:5px 10px;'>Submit</button>
                                      </form>";
                            }
                            echo "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>