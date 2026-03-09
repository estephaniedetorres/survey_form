<?php
include("../config/db.php");

if(!isset($_GET['survey_id']) || !is_numeric($_GET['survey_id'])){
    die("Invalid survey. <a href='create_survey.php'>Go back</a>");
}

$survey_id = (int)$_GET['survey_id'];

// Get survey info
$stmt = $conn->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->bind_param("i", $survey_id);
$stmt->execute();
$survey = $stmt->get_result()->fetch_assoc();

if(!$survey){
    die("Survey not found. <a href='create_survey.php'>Go back</a>");
}

// Get questions
$q_stmt = $conn->prepare("SELECT * FROM questions WHERE survey_id = ?");
$q_stmt->bind_param("i", $survey_id);
$q_stmt->execute();
$questions = $q_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all responses with their answers
$r_stmt = $conn->prepare("SELECT * FROM responses WHERE survey_id = ? ORDER BY submitted_at DESC");
$r_stmt->bind_param("i", $survey_id);
$r_stmt->execute();
$responses = $r_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responses - <?= htmlspecialchars($survey['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white min-h-screen py-10">

<div class="max-w-4xl mx-auto px-4">

    <div class="mb-6 flex justify-between items-center">
        <a href="create_survey.php" class="text-gray-500 hover:text-black">&larr; Back to Surveys</a>
        <a href="analytics.php?survey_id=<?= $survey_id ?>" class="text-gray-500 hover:text-black">View Analytics &rarr;</a>
    </div>

    <div class="bg-white border border-gray-200 p-6 rounded mb-6">
        <h1 class="text-xl font-bold text-black"><?= htmlspecialchars($survey['title']) ?></h1>
        <p class="text-gray-500"><?= htmlspecialchars($survey['description']) ?></p>
        <p class="text-sm text-gray-400 mt-2">Total responses: <?= count($responses) ?></p>
    </div>

    <?php if(count($responses) == 0): ?>
        <div class="bg-white border border-gray-200 p-6 rounded">
            <p class="text-gray-400">No responses yet.</p>
        </div>
    <?php else: ?>
        <?php foreach($responses as $index => $resp): ?>
        <div class="bg-white border border-gray-200 p-6 rounded mb-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-bold text-black">Response #<?= $index + 1 ?></h2>
                <span class="text-sm text-gray-400"><?= $resp['submitted_at'] ?></span>
            </div>

            <?php
            // Get answers for this response
            $a_stmt = $conn->prepare("SELECT a.answer, q.question, q.type FROM answers a JOIN questions q ON q.id = a.question_id WHERE a.response_id = ?");
            $a_stmt->bind_param("i", $resp['id']);
            $a_stmt->execute();
            $answers = $a_stmt->get_result();
            ?>

            <?php while($a = $answers->fetch_assoc()): ?>
            <div class="mb-3 pb-3 border-b border-gray-100 last:border-b-0">
                <p class="text-sm font-semibold text-gray-500"><?= htmlspecialchars($a['question']) ?></p>
                <p class="text-black"><?= htmlspecialchars($a['answer'] ?: '(no answer)') ?></p>
            </div>
            <?php endwhile; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

</body>
</html>