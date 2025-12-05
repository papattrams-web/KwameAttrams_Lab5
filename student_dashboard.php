<?php 
include 'db.php'; 
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') { header("Location: login.php"); exit(); }

$msg = "";
$msg_type = "";

// LOGIC: Mark Attendance
if (isset($_POST['submit_code'])) {
    $code = trim($_POST['code']);
    $stu_id = $_SESSION['user_id'];

    // 1. Find Session
    $stmt = $conn->prepare("SELECT session_id, course_id FROM sessions WHERE access_code = ? AND is_active = 1");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if($session = $result->fetch_assoc()) {
        // 2. Check Duplicate
        $check = $conn->query("SELECT log_id FROM attendance_log WHERE session_id = {$session['session_id']} AND student_id = $stu_id");
        if($check->num_rows == 0) {
            // 3. Mark Present
            $conn->query("INSERT INTO attendance_log (session_id, student_id) VALUES ({$session['session_id']}, $stu_id)");
            $msg = "Attendance Marked Successfully!";
            $msg_type = "success";
        } else {
            $msg = "You have already marked attendance for this session.";
            $msg_type = "error";
        }
    } else {
        $msg = "Invalid Code or Session is Closed.";
        $msg_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard | Ashesi LMS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Specific Styles for Student Messages */
        .alert-success { background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>ASHESI LMS</h2>
        <a href="#" class="active">My Attendance</a>
        <a href="#">Course Materials</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="main-content">
        <header>
            <h1>Student Portal</h1>
            <div class="user-info">Logged in as: <strong><?php echo $_SESSION['name']; ?></strong></div>
        </header>

        <div class="card" style="max-width: 600px;">
            <h3>Mark Attendance</h3>
            <p style="margin-bottom: 15px; color: #666;">Enter the 5-character code provided by your lecturer.</p>
            
            <?php if($msg != ""): ?>
                <div class="<?php echo ($msg_type == 'success') ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <form method="POST" style="display: flex; gap: 10px;">
                <input type="text" name="code" placeholder="Ex: X7K9P" maxlength="5" style="font-size: 20px; text-transform: uppercase; letter-spacing: 2px; width: 200px;" required>
                <button type="submit" name="submit_code" class="btn-primary">Submit Code</button>
            </form>
        </div>

        <div class="card">
            <h3>Attendance Report</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Course</th>
                        <th>Session Code</th>
                        <th>Time Marked</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $uid = $_SESSION['user_id'];
                    $sql = "SELECT a.marked_at, s.session_date, s.access_code, c.course_name, c.course_code 
                            FROM attendance_log a 
                            JOIN sessions s ON a.session_id = s.session_id 
                            JOIN courses c ON s.course_id = c.course_id 
                            WHERE a.student_id = $uid 
                            ORDER BY a.marked_at DESC";
                    $res = $conn->query($sql);

                    if($res->num_rows > 0) {
                        while($row = $res->fetch_assoc()) {
                            echo "<tr>
                                <td>{$row['session_date']}</td>
                                <td>{$row['course_code']} - {$row['course_name']}</td>
                                <td style='font-family:monospace'>{$row['access_code']}</td>
                                <td>".date('h:i A', strtotime($row['marked_at']))."</td>
                                <td><span class='status-active'>Present</span></td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding: 20px;'>No attendance records found yet.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>