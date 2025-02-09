<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cnic = $_POST['cnic'];
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE cnic = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $cnic);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                $_SESSION['admin'] = true;
                header("Location: admin_dashboard.php");
            } else {
                header("Location: application.php");
            }
            exit();
        }
    }
    $error = "Invalid CNIC or password";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - STEP PGC</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <style>
        .auth-side-image {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('step-bg.jpg');
            background-size: cover;
            background-position: center;
        }

        @media (max-width: 768px) {
            .auth-container {
                grid-template-columns: 1fr;
            }
            .auth-side-image {
                display: none;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100">
    <div class="min-h-screen grid grid-cols-1 md:grid-cols-2 auth-container">
        <!-- Left side - Image -->
        <div class="auth-side-image hidden md:flex flex-col justify-between p-12 text-white">
            <div class="flex items-center space-x-4">
                <img src="step.png" alt="STEP Logo" class="h-12">
                <div class="h-8 w-px bg-white/30"></div>
                <img src="pgc.png" alt="PGC Logo" class="h-12">
            </div>
            <div>
                <h2 class="text-4xl font-bold mb-4">Welcome Back!</h2>
                <p class="text-xl">Log in to continue your journey with STEP PGC</p>
            </div>
            <div class="text-sm">
                © 2025 STEP PGC. All rights reserved.
            </div>
        </div>

        <!-- Right side - Login Form -->
        <div class="flex items-center justify-center p-8 bg-white">
            <div class="w-full max-w-md space-y-8">
                <div class="text-center">
                    <h1 class="text-3xl font-bold text-gray-900">Login</h1>
                    <!-- <p class="mt-2 text-gray-600">Welcome back to STEP PGC</p> -->
                </div>

                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">CNIC (XXXXX-XXXXXXX-X)</label>
                        <input type="text" name="cnic" 
                               class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="XXXXX-XXXXXXX-X"
                               pattern="[0-9]{5}-[0-9]{7}-[0-9]{1}"
                               required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" 
                               class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               required>
                    </div>

                    <button type="submit" 
                            class="w-full py-3 px-4 border border-transparent rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Sign In
                    </button>

                    <p class="text-center text-sm text-gray-600"></p>
                        Don't have an account? 
                        <a href="signup.php" class="font-medium text-blue-600 hover:text-blue-500">Sign up</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
