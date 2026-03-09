<?php
include("../config/db.php");

if(!isset($_POST['survey_id']) || !is_numeric($_POST['survey_id'])){
    die("Invalid submission.");
}

$survey_id = (int)$_POST['survey_id'];

// Verify survey exists
$stmt = $conn->prepare("SELECT token FROM surveys WHERE id = ?");
$stmt->bind_param("i", $survey_id);
$stmt->execute();
$survey = $stmt->get_result()->fetch_assoc();

if(!$survey){
    die("Survey not found.");
}

$ins = $conn->prepare("INSERT INTO responses(survey_id) VALUES(?)");
$ins->bind_param("i", $survey_id);
$ins->execute();
$response_id = $conn->insert_id;

if(isset($_POST['answers']) && is_array($_POST['answers'])){
    foreach($_POST['answers'] as $question_id => $answer){
        $question_id = (int)$question_id;

        // Checkbox answers come as array, join them
        if(is_array($answer)){
            $answer = implode(", ", $answer);
        }

        $stmt = $conn->prepare("INSERT INTO answers(response_id, question_id, answer) VALUES(?, ?, ?)");
        $stmt->bind_param("iis", $response_id, $question_id, $answer);
        $stmt->execute();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white min-h-screen flex items-center justify-center">
    <div class="bg-white border border-gray-200 p-8 rounded text-center max-w-md">
        <h1 class="text-2xl font-bold text-black mb-2">Thank You!</h1>
        <p class="text-gray-500 mb-4">Your response has been submitted successfully.</p>
        <a href="survey.php?token=<?= htmlspecialchars($survey['token']) ?>"
           class="text-gray-600 hover:text-black">Submit another response</a>
    </div>
</body>
</html>