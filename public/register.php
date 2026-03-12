<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if username already exists
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) > 0) {
        $error = 'Username already taken';
    } else {
        // Hash password for security
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $insert = "INSERT INTO users (username, password) VALUES ('$username', '$hashed')";
        if (mysqli_query($conn, $insert)) {
            $_SESSION['username'] = $username;
            header('Location: ../admin/create_survey.php');
            exit();
        } else {
            $error = 'Registration failed';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white min-h-screen py-10">
    <div class="max-w-md mx-auto px-4">
        <div class="bg-white border border-gray-200 p-6 rounded mb-6 text-center">
            <h2 class="text-2xl font-bold mb-2 text-black">Register</h2>
            <?php if(isset($error)) echo '<p class="text-red-500 mb-4">'.$error.'</p>'; ?>
            <form method="post" class="space-y-4">
                <div>
                    <label class="block text-left text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" required class="border border-gray-300 bg-white text-black p-2 w-full rounded placeholder-gray-400">
                </div>
                <div>
                    <label class="block text-left text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required class="border border-gray-300 bg-white text-black p-2 w-full rounded placeholder-gray-400">
                </div>
                <button type="submit" class="bg-black text-white px-4 py-2 rounded font-medium hover:bg-gray-800 w-full">Register</button>
            </form>
            <div class="mt-4">
                <a href="login.php" class="text-gray-600 hover:text-black underline">Already have an account? Login</a>
            </div>
        </div>
    </div>
</body>
</html>
