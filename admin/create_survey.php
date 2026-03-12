<?php
include("../config/db.php");
session_start();
$is_guest = isset($_GET['guest']) && $_GET['guest'] == '1';
$is_logged_in = isset($_SESSION['username']);

$success = false;
$link = "";
$new_survey_id = 0;

if(($is_logged_in || $is_guest) && isset($_POST['create'])){
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $password = $_POST['password'];

    $token = bin2hex(random_bytes(5));

    if($password != ""){
        $password = password_hash($password, PASSWORD_DEFAULT);
    } else {
        $password = NULL;
    }

    $stmt = $conn->prepare("INSERT INTO surveys(title, description, token, password) VALUES(?, ?, ?, ?)");
    $stmt->bind_param("ssss", $title, $desc, $token, $password);
    $stmt->execute();
    $new_survey_id = $conn->insert_id;

    $link = getSurveyUrl($token);
    $networkLink = getNetworkSurveyUrl($token);
    $success = true;
}

// Fetch existing surveys
$surveys = $conn->query("SELECT * FROM surveys ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Survey</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white min-h-screen py-10">

<div class="max-w-2xl mx-auto px-4">

    <div class="mb-6">
        <a href="../index.php" class="text-gray-500 hover:text-black">&larr; Back to Home</a>
    </div>

    <?php if(!$is_logged_in && !$is_guest): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded p-4 mb-6">
            <p class="text-black mb-2">You must <a href="../public/login.php" class="underline">login</a> or <a href="create_survey.php?guest=1" class="underline">continue as guest</a> to create a survey.</p>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
    <div class="bg-gray-50 border border-gray-200 rounded p-4 mb-6">
        <p class="font-semibold text-black mb-2">Survey created!</p>
        <p class="text-sm text-gray-500 mb-1">Local link:</p>
        <input type="text" value="<?= htmlspecialchars($link) ?>" readonly
            class="border border-gray-300 bg-white text-black p-2 w-full mb-2 text-sm" onclick="this.select()">
        <p class="text-sm text-gray-500 mb-1">Network link <span class="text-xs">(share with other devices on your network)</span>:</p>
        <input type="text" value="<?= htmlspecialchars($networkLink) ?>" readonly
            class="border border-gray-300 bg-white text-black p-2 w-full mb-3 text-sm" onclick="this.select()">
        <a href="add_questions.php?survey_id=<?= $new_survey_id ?>"
           class="inline-block bg-black text-white px-4 py-2 rounded text-sm font-medium hover:bg-gray-800">
            Add Questions &rarr;
        </a>
    </div>
    <?php endif; ?>

    <?php if($is_logged_in || $is_guest): ?>
    <form method="POST" class="bg-white border border-gray-200 p-6 rounded mb-8">
        <h1 class="text-xl font-bold mb-4 text-black">Create Survey</h1>

        <input name="title" placeholder="Survey Title" required
            class="border border-gray-300 bg-white text-black p-2 w-full mb-3 placeholder-gray-400">

        <textarea name="description" placeholder="Description"
            class="border border-gray-300 bg-white text-black p-2 w-full mb-3 placeholder-gray-400"></textarea>

        <input name="password" placeholder="Optional Password (leave blank for no password)"
            class="border border-gray-300 bg-white text-black p-2 w-full mb-3 placeholder-gray-400">

        <button name="create"
            class="bg-black text-white px-4 py-2 rounded font-medium hover:bg-gray-800">
            Create Survey
        </button>
    </form>
    <?php endif; ?>

    <div class="bg-white border border-gray-200 p-6 rounded">
        <h2 class="text-lg font-bold mb-4 text-black">Existing Surveys</h2>

        <?php if($surveys->num_rows == 0): ?>
            <p class="text-gray-400">No surveys yet.</p>
        <?php else: ?>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 text-gray-500">Title</th>
                        <th class="text-left py-2 text-gray-500">Protected</th>
                        <th class="text-left py-2 text-gray-500">Created</th>
                        <th class="text-left py-2 text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($s = $surveys->fetch_assoc()): ?>
                    <?php
                        $survey_link = getNetworkSurveyUrl($s['token']);
                    ?>
                    <tr class="border-b border-gray-100">
                        <td class="py-2 text-black"><?= htmlspecialchars($s['title']) ?></td>
                        <td class="py-2 text-gray-500"><?= $s['password'] ? 'Yes' : 'No' ?></td>
                        <td class="py-2 text-gray-500"><?= $s['created_at'] ?></td>
                        <td class="py-2 space-x-2">
                            <a href="add_questions.php?survey_id=<?= $s['id'] ?>" class="text-gray-600 hover:text-black">Questions</a>
                            <a href="responses.php?survey_id=<?= $s['id'] ?>" class="text-gray-600 hover:text-black">Responses</a>
                            <a href="analytics.php?survey_id=<?= $s['id'] ?>" class="text-gray-600 hover:text-black">Analytics</a>
                            <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($survey_link) ?>')" class="text-gray-600 hover:text-black cursor-pointer">Copy Link</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>

</body>
</html>