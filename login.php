<?php
session_start();
include '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Secure password check
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $username;
            header('Location: ../admin/create_survey.php');
            exit();
        } else {
            $error = 'Invalid credentials';
        }
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white min-h-screen py-10">
    <div class="max-w-md mx-auto px-4">
        <div class="bg-white border border-gray-200 p-6 rounded mb-6 text-center">
            <h2 class="text-2xl font-bold mb-2 text-black">Login</h2>
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
                <button type="submit" class="bg-black text-white px-4 py-2 rounded font-medium hover:bg-gray-800 w-full">Login</button>
            </form>
            <div class="mt-4 space-y-2">
                <a href="../admin/create_survey.php?guest=1" class="text-gray-600 hover:text-black underline">Continue as Guest</a><br>
                <a href="register.php" class="text-gray-600 hover:text-black underline">Don't have an account? Register</a>
            </div>
        </div>
    </div>
</body>
</html>
