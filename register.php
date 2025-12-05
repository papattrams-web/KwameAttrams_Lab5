<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register | Ashesi LMS</title>
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background: white; border-radius: 8px; padding: 30px 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); width: 350px; text-align: center; }
        .login-container h2 { color: #880000; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        input, select { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; }
        button.btn-primary { width: 100%; background-color: #880000; color: white; border: none; padding: 12px; border-radius: 4px; cursor: pointer; transition: 0.2s; }
        button.btn-primary:hover { background-color: #660000; }
        .error-message { color: red; font-size: 13px; margin-bottom: 15px; text-align: left; }
        .success-message { color: green; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>ASHESI LMS REGISTRATION</h2>
        
        <?php
        if (isset($_POST['register'])) {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $raw_pass = $_POST['password'];
            $role = $_POST['role'];
            $errors = [];

            // 1. Email Domain Validation
            if (!preg_match("/^[a-zA-Z0-9._%+-]+@ashesi\.edu\.gh$/", $email)) {
                $errors[] = "Only @ashesi.edu.gh emails are allowed.";
            }

            // 2. Password Strength Validation
            if (strlen($raw_pass) < 8 || !preg_match("#[0-9]+#", $raw_pass)) {
                $errors[] = "Password must be 8+ chars and include a number.";
            }

            if (empty($errors)) {
                $pass = password_hash($raw_pass, PASSWORD_DEFAULT);
                
                // Using Prepared Statements for security
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $email, $pass, $role);
                
                try {
                    if ($stmt->execute()) {
                        echo "<p class='success-message'>Registration successful! <a href='login.php'>Login here</a></p>";
                    }
                } catch (mysqli_sql_exception $e) {
                    if ($e->getCode() == 1062) {
                        echo "<p class='error-message'>Error: Email already exists.</p>";
                    } else {
                        echo "<p class='error-message'>System Error. Try again.</p>";
                    }
                }
            } else {
                foreach($errors as $err) echo "<p class='error-message'>• $err</p>";
            }
        }
        ?>

        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email (@ashesi.edu.gh)" required>
            <input type="password" name="password" placeholder="Password (Min 8 chars)" required>
            <select name="role" required>
                <option value="" disabled selected>Select Role</option>
                <option value="student">Student</option>
                <option value="faculty">Faculty/Intern</option>
            </select>
            <button type="submit" name="register" class="btn-primary">Sign Up</button>
        </form>
        <a href="login.php" style="display:block; margin-top:15px; color:#880000; text-decoration:none;">Already have an account? Login</a>
    </div>
</body>
</html>