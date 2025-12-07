<?php 
include 'db.php'; 
// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'faculty') { 
    header("Location: login.php"); 
    exit(); 
}

// --- LOGIC BLOCKS ---

// 1. LOGIC: Create Session
$msg = "";
if (isset($_POST['create_session'])) {
    $course_id = $_POST['course_id'];
    $date = $_POST['session_date']; 
    $time = $_POST['session_time'];
    // Generate a random 5-character code
    $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 5)); 
    $creator = $_SESSION['user_id'];

    // PREPARE STATEMENT
    $stmt = $conn->prepare("INSERT INTO sessions (course_id, created_by, session_date, session_time, access_code) VALUES (?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("iisss", $course_id, $creator, $date, $time, $code);
        if($stmt->execute()) {
            $msg = "Session successfully created! Code: <b>$code</b>";
        } else {
            $msg = "Error executing query: " . $stmt->error;
        }
    } else {
        $msg = "Database Error: " . $conn->error;
    }
}

// 2. LOGIC: Close Session
if (isset($_GET['close_id'])) {
    $sid = (int)$_GET['close_id'];
    $conn->query("UPDATE sessions SET is_active = 0 WHERE session_id = $sid");
    header("Location: faculty_dashboard.php"); // Redirect to self to clean URL
    exit();
}


// 3. LOGIC: View Attendees List
$attendee_list_html = "";
if (isset($_GET['view_session_id'])) {
    $sid = (int)$_GET['view_session_id'];

    // Fetch session details
    $session_q = $conn->query("SELECT s.session_date, s.session_time, s.access_code, c.course_code, c.course_name FROM sessions s JOIN courses c ON s.course_id = c.course_id WHERE s.session_id = $sid");
    
    // Check if session exists before proceeding
    if ($session_q->num_rows > 0) {
        $session_details = $session_q->fetch_assoc();

        // Fetch attendees details (NAME and EMAIL)
        $attendees_sql = "SELECT u.full_name, u.email, a.marked_at 
                          FROM attendance_log a 
                          JOIN users u ON a.student_id = u.user_id 
                          WHERE a.session_id = $sid 
                          ORDER BY u.full_name ASC";
        $attendees_res = $conn->query($attendees_sql);

        $header = "Attendance List for {$session_details['course_code']} ({$session_details['course_name']}) on {$session_details['session_date']} at " . date('h:i A', strtotime($session_details['session_time']));
        
        $attendee_list_html = "<div class='card'>";
        // Using the existing style for consistency
        $attendee_list_html .= "<h3>$header</h3>"; 
        $attendee_list_html .= "<table><thead><tr><th>Student Name</th><th>Email</th><th>Time Marked</th></tr></thead><tbody>";
        
        if ($attendees_res->num_rows > 0) {
            while($row = $attendees_res->fetch_assoc()) {
                $marked_time = date('h:i A', strtotime($row['marked_at']));
                // Displaying the names and emails
                $attendee_list_html .= "<tr><td>{$row['full_name']}</td><td>{$row['email']}</td><td>$marked_time</td></tr>";
            }
        } else {
             $attendee_list_html .= "<tr><td colspan='3' style='text-align:center'>No students have marked attendance for this session yet.</td></tr>";
        }

        $attendee_list_html .= "</tbody></table>";
        $attendee_list_html .= "<a href='faculty_dashboard.php' class='btn-primary' style='display:inline-block; margin-top:15px; text-decoration:none;'>&larr; Back to Sessions</a>";
        $attendee_list_html .= "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Dashboard | Ashesi LMS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .msg-box { padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .msg-success { background: #eafaf1; color: green; border: 1px solid #c3e6cb; }
        .msg-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        /* Style for the link used in the table */
        .view-link { 
            display: inline-block; 
            padding: 5px 10px; 
            background: #e0e0e0; 
            color: #444; 
            border-radius: 3px;
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
            transition: background 0.2s;
        }
        .view-link:hover { background: #ccc; color: #000; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>ASHESI LMS</h2>
        <a href="faculty_dashboard.php">Dashboard</a> 
        <a href="course_materials.php">Course Materials</a>
        <a href="assignments.php">Assignments & Grades</a> 
        <a href="reports.php">Reports</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="main-content">
        <header>
            <h1>Faculty Dashboard</h1>
            <div class="user-info">Logged in as: <strong><?php echo $_SESSION['name']; ?></strong></div>
        </header>
        
        <?php echo $attendee_list_html; ?>

        <?php if (!isset($_GET['view_session_id'])): ?>

        <div class="card">
            <h3>Create New Attendance Session</h3>
            
            <?php if($msg): ?>
                <div class="msg-box <?php echo (strpos($msg, 'Error') !== false) ? 'msg-error' : 'msg-success'; ?>">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" style="display: flex; gap: 20px; align-items: flex-end;">
                <div class="form-group" style="flex: 2;">
                    <label>Course</label>
                    <select name="course_id" required>
                        <?php
                        $courses = $conn->query("SELECT * FROM courses");
                        if($courses->num_rows > 0){
                            while($c = $courses->fetch_assoc()) {
                                echo "<option value='{$c['course_id']}'>{$c['course_code']} - {$c['course_name']}</option>";
                            }
                        } else {
                            echo "<option disabled>No Courses Found</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Date</label>
                    <input type="date" name="session_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                 <div class="form-group" style="flex: 1;">
                    <label>Time</label>
                    <input type="time" name="session_time" value="<?php echo date('H:i'); ?>" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="create_session" class="btn-primary">Generate Code</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h3>Session History</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Course</th>
                        <th>Access Code</th>
                        <th>Status</th>
                        <th>Attendees</th> <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $uid = $_SESSION['user_id'];
                    $sql = "SELECT s.*, c.course_code, c.course_name, 
                            (SELECT COUNT(*) FROM attendance_log WHERE session_id = s.session_id) as count
                            FROM sessions s 
                            JOIN courses c ON s.course_id = c.course_id 
                            WHERE s.created_by = $uid 
                            ORDER BY s.session_id DESC";
                    
                    $res = $conn->query($sql);

                    if ($res && $res->num_rows > 0) {
                        while($row = $res->fetch_assoc()) {
                            $statusClass = $row['is_active'] ? 'status-active' : 'status-closed';
                            $statusText = $row['is_active'] ? 'Open' : 'Closed';
                            
                            $displayTime = isset($row['session_time']) ? date('h:i A', strtotime($row['session_time'])) : '--:--';

                            echo "<tr>
                                <td>{$row['session_date']} <span style='color:#999; font-size:12px'>$displayTime</span></td>
                                <td>{$row['course_code']}</td>
                                <td><span class='access-code'>{$row['access_code']}</span></td>
                                <td><span class='$statusClass'>$statusText</span></td>
                                <td>";
                            
                            // NEW LINK TO VIEW ATTENDEES
                            echo "<a href='faculty_dashboard.php?view_session_id={$row['session_id']}' class='view-link'>{$row['count']} Students (View)</a>";
                            
                            echo "</td><td>";
                            
                            if($row['is_active']) {
                                echo "<a href='faculty_dashboard.php?close_id={$row['session_id']}' style='color:red; text-decoration:underline; font-size:13px;'>Close Session</a>";
                            } else {
                                echo "<span style='color:#aaa;'>Archived</span>";
                            }
                            echo "</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center; padding:15px;'>No sessions created yet.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <?php endif; ?>
    </div>
</body>
</html>