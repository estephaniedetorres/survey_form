<?php
include("config/db.php");
$surveys = $conn->query("SELECT * FROM surveys ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Form</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white min-h-screen py-10">

<div class="max-w-2xl mx-auto px-4">

    <div class="bg-white border border-gray-200 p-6 rounded mb-6 text-center">
        <h1 class="text-2xl font-bold mb-2 text-black">Survey Form</h1>
        <p class="text-gray-500 mb-4">Create surveys, add questions, and collect responses.</p>
        <a href="admin/create_survey.php"
            class="inline-block bg-black text-white px-6 py-2 rounded font-medium hover:bg-gray-800">
            Create New Survey
        </a>
    </div>

    <div class="bg-white border border-gray-200 p-6 rounded">
        <h2 class="text-lg font-bold mb-4 text-black">Your Surveys</h2>

        <?php if($surveys->num_rows == 0): ?>
            <p class="text-gray-400">No surveys created yet.</p>
        <?php else: ?>
            <?php while($s = $surveys->fetch_assoc()): ?>
            <div class="border-b border-gray-200 pb-3 mb-3 last:border-b-0">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-semibold text-black"><?= htmlspecialchars($s['title']) ?></p>
                        <p class="text-gray-500 text-sm"><?= htmlspecialchars($s['description']) ?></p>
                        <p class="text-xs text-gray-400 mt-1">
                            <?= $s['password'] ? 'Password protected' : 'Open access' ?>
                            &middot; <?= $s['created_at'] ?>
                        </p>
                    </div>
                    <div class="flex gap-3 text-sm">
                        <a href="admin/add_questions.php?survey_id=<?= $s['id'] ?>" class="text-gray-600 hover:text-black">Questions</a>
                        <a href="admin/responses.php?survey_id=<?= $s['id'] ?>" class="text-gray-600 hover:text-black">Responses</a>
                        <a href="admin/analytics.php?survey_id=<?= $s['id'] ?>" class="text-gray-600 hover:text-black">Analytics</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
