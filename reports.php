<?php 
include 'db.php'; 
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports | Ashesi LMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="sidebar">
        <h2>ASHESI LMS</h2>
        <a href="<?php echo ($role=='faculty') ? 'faculty_dashboard.php' : 'student_dashboard.php'; ?>">Dashboard</a>
        <a href="course_materials.php">Course Materials</a>
        <a href="assignments.php">Assignments & Grades</a> 
        <a href="#" class="active">Reports</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="main-content">
        <header>
            <h1><?php echo ($role == 'faculty') ? "Class Performance Reports" : "My Attendance Report"; ?></h1>
            <div class="user-info">Logged in as: <strong><?php echo $_SESSION['name']; ?></strong></div>
        </header>

        <?php if($role == 'faculty'): ?>
        <div class="card">
            <h3>Student Attendance Summary</h3>
            <p style="margin-bottom: 20px; color: #666;">Overview of attendance percentages per student per course.</p>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Classes Held</th>
                        <th>Classes Attended</th>
                        <th>Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // COMPLEX QUERY: 
                    // 1. Get all students and courses.
                    // 2. Count total sessions for that course (Total).
                    // 3. Count how many the student logged (Attended).
                    $sql = "SELECT u.full_name, u.email, c.course_code, c.course_name, c.course_id, u.user_id
                            FROM users u 
                            CROSS JOIN courses c 
                            WHERE u.role = 'student'
                            ORDER BY c.course_code, u.full_name";
                    
                    $res = $conn->query($sql);
                    
                    while($row = $res->fetch_assoc()) {
                        $sid = $row['user_id'];
                        $cid = $row['course_id'];

                        // Count Total Sessions created for this course
                        $total_q = $conn->query("SELECT COUNT(*) as total FROM sessions WHERE course_id = $cid");
                        $total_sessions = $total_q->fetch_assoc()['total'];

                        // Count Sessions Attended by this student for this course
                        $att_q = $conn->query("SELECT COUNT(*) as attended FROM attendance_log al 
                                               JOIN sessions s ON al.session_id = s.session_id 
                                               WHERE s.course_id = $cid AND al.student_id = $sid");
                        $attended = $att_q->fetch_assoc()['attended'];

                        // Avoid division by zero
                        $perc = ($total_sessions > 0) ? round(($attended / $total_sessions) * 100) : 0;
                        
                        // Color coding for low attendance
                        $color = ($perc < 75) ? 'red' : 'green';

                        // Only show if the course has at least 1 session to avoid clutter
                        if($total_sessions > 0) {
                            echo "<tr>
                                <td>{$row['full_name']}</td>
                                <td>{$row['email']}</td>
                                <td>{$row['course_code']}</td>
                                <td>$total_sessions</td>
                                <td>$attended</td>
                                <td><strong style='color:$color'>$perc%</strong></td>
                            </tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if($role == 'student'): ?>
        <div class="card">
            <h3>My Course Statistics</h3>
            <table>
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Total Classes</th>
                        <th>My Attendance</th>
                        <th>Percentage</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $uid = $_SESSION['user_id'];
                    $courses = $conn->query("SELECT * FROM courses");

                    while($c = $courses->fetch_assoc()) {
                        $cid = $c['course_id'];
                        
                        // Total sessions in this course
                        $t_res = $conn->query("SELECT COUNT(*) as t FROM sessions WHERE course_id = $cid");
                        $total = $t_res->fetch_assoc()['t'];

                        // My attendance
                        $m_res = $conn->query("SELECT COUNT(*) as m FROM attendance_log a 
                                               JOIN sessions s ON a.session_id = s.session_id 
                                               WHERE s.course_id = $cid AND a.student_id = $uid");
                        $my_att = $m_res->fetch_assoc()['m'];

                        if($total > 0) {
                            $perc = round(($my_att / $total) * 100);
                            $status = ($perc >= 80) ? "<span class='status-active'>Good Standing</span>" : "<span class='status-closed'>Warning</span>";
                            
                            echo "<tr>
                                <td>{$c['course_code']}</td>
                                <td>{$c['course_name']}</td>
                                <td>$total</td>
                                <td>$my_att</td>
                                <td>$perc%</td>
                                <td>$status</td>
                            </tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>