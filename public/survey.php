<?php
session_start();
include("../config/db.php");

if(!isset($_GET['token']) || empty($_GET['token'])){
    die("No survey token provided.");
}

$token = $_GET['token'];

$stmt = $conn->prepare("SELECT * FROM surveys WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$survey = $stmt->get_result()->fetch_assoc();

if(!$survey){
    die("Survey not found");
}

/* PASSWORD PROTECTION */
if($survey['password']){

    $authenticated = false;

    if(isset($_POST['password'])){
        if(password_verify($_POST['password'], $survey['password'])){
            $authenticated = true;
        } else {
            $error = "Wrong Password";
        }
    }

    if(!$authenticated){
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Password Required</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white">

<div class="max-w-md mx-auto mt-20 bg-white border border-gray-200 p-6 rounded">

<h2 class="text-xl font-bold mb-4 text-black">This survey is password protected</h2>

<?php if(isset($error)): ?>
<p class="text-red-500 mb-2"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST">
<input type="password"
    name="password"
    placeholder="Enter Password"
    required
    class="border border-gray-300 bg-white text-black p-2 w-full mb-3 placeholder-gray-400">

<button class="bg-black text-white px-4 py-2 rounded w-full font-medium hover:bg-gray-800">
    Enter Survey
</button>
</form>
</div>

</body>
</html>
<?php
        exit;
    }
}

/* GET QUESTIONS */
$q_stmt = $conn->prepare("SELECT * FROM questions WHERE survey_id = ?");
$q_stmt->bind_param("i", $survey['id']);
$q_stmt->execute();
$questions = $q_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($survey['title']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-white">

<div class="max-w-2xl mx-auto mt-10 pb-10 px-4">

<div class="bg-white border border-gray-200 p-6 rounded mb-4">
<h1 class="text-2xl font-bold text-black"><?= htmlspecialchars($survey['title']) ?></h1>
<p class="text-gray-500"><?= htmlspecialchars($survey['description']) ?></p>
</div>

<?php if($questions->num_rows == 0): ?>
<div class="bg-white border border-gray-200 p-6 rounded">
    <p class="text-gray-400">This survey has no questions yet.</p>
</div>
<?php else: ?>

<form method="POST" action="submit.php">

<input type="hidden" name="survey_id" value="<?= (int)$survey['id'] ?>">

<?php while($q = $questions->fetch_assoc()): ?>

<div class="bg-white border border-gray-200 p-4 mb-4 rounded">

<p class="font-semibold mb-2 text-black">
    <?= htmlspecialchars($q['question']) ?>
    <?php if($q['required']): ?><span class="text-red-500">*</span><?php endif; ?>
</p>

<?php if($q['type'] == "text"): ?>

    <input type="text"
        name="answers[<?= (int)$q['id'] ?>]"
        class="border border-gray-300 bg-white text-black p-2 w-full placeholder-gray-400"
        placeholder="Your answer"
        <?= $q['required'] ? 'required' : '' ?>>

<?php elseif($q['type'] == "radio"): ?>

    <?php
    $o_stmt = $conn->prepare("SELECT * FROM options WHERE question_id = ?");
    $o_stmt->bind_param("i", $q['id']);
    $o_stmt->execute();
    $opts = $o_stmt->get_result();
    while($o = $opts->fetch_assoc()):
    ?>
    <label class="block mb-1 text-gray-700">
        <input type="radio"
            name="answers[<?= (int)$q['id'] ?>]"
            value="<?= htmlspecialchars($o['option_text']) ?>"
            <?= $q['required'] ? 'required' : '' ?>>
        <?= htmlspecialchars($o['option_text']) ?>
    </label>
    <?php endwhile; ?>

<?php elseif($q['type'] == "checkbox"): ?>

    <?php
    $o_stmt = $conn->prepare("SELECT * FROM options WHERE question_id = ?");
    $o_stmt->bind_param("i", $q['id']);
    $o_stmt->execute();
    $opts = $o_stmt->get_result();
    while($o = $opts->fetch_assoc()):
    ?>
    <label class="block mb-1 text-gray-700">
        <input type="checkbox"
            name="answers[<?= (int)$q['id'] ?>][]"
            value="<?= htmlspecialchars($o['option_text']) ?>">
        <?= htmlspecialchars($o['option_text']) ?>
    </label>
    <?php endwhile; ?>

<?php endif; ?>

</div>

<?php endwhile; ?>

<button class="bg-black text-white px-4 py-2 rounded font-medium hover:bg-gray-800">
Submit
</button>

</form>

<?php endif; ?>

</div>

</body>
</html>