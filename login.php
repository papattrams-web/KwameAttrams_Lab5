<?php 
include 'db.php'; 
// Check if user is already logged in, and redirect
if(isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'faculty') { header("Location: faculty_dashboard.php"); } 
    else { header("Location: student_dashboard.php"); }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Ashesi LMS</title>
    <style>
        /* Styles adapted for Login/Register pages */
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body {
            background-color: #f4f6f9; /* Light grey background */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            border-radius: 8px;
            padding: 30px 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 350px;
            text-align: center;
        }
        .login-container h2 {
            color: #880000; /* Ashesi Maroon */
            margin-bottom: 25px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        input[type="email"], input[type="password"], select, input[type="text"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        button.btn-primary {
            width: 100%;
            background-color: #880000;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: 0.2s;
        }
        button.btn-primary:hover {
            background-color: #660000;
        }
        .login-container a {
            display: block;
            margin-top: 15px;
            color: #880000;
            text-decoration: none;
            font-size: 14px;
        }
        .error-message { color: red; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>ASHESI LMS LOGIN</h2>
        <form method="POST">
            <input type="email" name="email" placeholder="Email (e.g., user@ashesi.edu.gh)" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login" class="btn-primary">Sign In</button>
        </form>
        <a href="register.php">Need an account? Register Here</a>

        <?php
        if (isset($_POST['login'])) {
            $email = $_POST['email'];
            $pass = $_POST['password'];

            $stmt = $conn->prepare("SELECT user_id, full_name, password, role FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (password_verify($pass, $row['password'])) {
                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['role'] = $row['role'];
                    $_SESSION['name'] = $row['full_name'];
                    
                    if ($row['role'] == 'faculty') {
                        header("Location: faculty_dashboard.php");
                    } else {
                        header("Location: student_dashboard.php");
                    }
                    exit();
                } else {
                    echo "<p class='error-message'>Invalid Password</p>";
                }
            } else {
                echo "<p class='error-message'>User not found</p>";
            }
        }
        ?>
    </div>
</body>
</html>