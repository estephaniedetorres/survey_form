<?php
include("../config/db.php");

if(!isset($_GET['survey_id']) || !is_numeric($_GET['survey_id'])){
    die("Invalid survey. <a href='create_survey.php'>Go back</a>");
}

$survey_id = (int)$_GET['survey_id'];

$stmt = $conn->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->bind_param("i", $survey_id);
$stmt->execute();
$survey = $stmt->get_result()->fetch_assoc();

if(!$survey){
    die("Survey not found.");
}

$message = "";

if(isset($_POST['demo_question_text'])){

    foreach($_POST['demo_question_text'] as $index => $question){

        $type = $_POST['demo_type'][$index];

        $stmt = $conn->prepare("INSERT INTO questions(survey_id, question, type, required) VALUES(?, ?, ?, 0)");
        $stmt->bind_param("iss", $survey_id, $question, $type);
        $stmt->execute();

        $question_id = $conn->insert_id;

        if($type != "text"){

            $options = $_POST["demo_options_$index"] ?? [];

            foreach($options as $opt){

                if(trim($opt) != ""){

                    $stmt2 = $conn->prepare("INSERT INTO options(question_id, option_text) VALUES(?, ?)");
                    $stmt2->bind_param("is", $question_id, $opt);
                    $stmt2->execute();

                }

            }

        }

    }

    $message = "Demographics questions added!";
}

if(isset($_POST['add'])){

    $q = $_POST['question'];
    $type = $_POST['type'];
    $required = isset($_POST['required']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO questions(survey_id, question, type, required) VALUES(?, ?, ?, ?)");
    $stmt->bind_param("issi", $survey_id, $q, $type, $required);
    $stmt->execute();

    $question_id = $conn->insert_id;

    if($type != "text" && !empty($_POST['options'])){

        foreach($_POST['options'] as $opt){

            if(trim($opt) != ""){

                $stmt2 = $conn->prepare("INSERT INTO options(question_id, option_text) VALUES(?, ?)");
                $stmt2->bind_param("is", $question_id, $opt);
                $stmt2->execute();

            }

        }

    }

    $message = "Question added!";
}

$questions = $conn->query("SELECT * FROM questions WHERE survey_id = $survey_id");
?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<title>Add Questions</title>

<script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-white min-h-screen py-10">

<div class="max-w-2xl mx-auto px-4">

<a href="create_survey.php" class="text-gray-500 hover:text-black mb-6 block">
← Back to Surveys
</a>

<div class="bg-white border border-gray-200 p-4 rounded mb-6">
<h1 class="text-xl font-bold"><?= htmlspecialchars($survey['title']) ?></h1>
<p class="text-gray-500 text-sm"><?= htmlspecialchars($survey['description']) ?></p>
</div>

<?php if($message): ?>

<div class="bg-gray-50 border border-gray-200 p-3 rounded mb-4">
<?= $message ?>
</div>

<?php endif; ?>



<button onclick="addDemographics()"
class="bg-gray-100 border border-gray-300 text-black px-4 py-2 rounded mb-6 hover:bg-gray-200">
+ Add Demographics Section
</button>


<form method="POST" id="demographics-container"
class="hidden bg-white border border-gray-200 p-6 rounded mb-8">

<h2 class="text-lg font-bold mb-4">Demographics</h2>

<div id="demographics-questions"></div>

<button
class="bg-black text-white px-4 py-2 rounded hover:bg-gray-800">
Save Demographics Questions
</button>

</form>



<form method="POST"
class="bg-white border border-gray-200 p-6 rounded mb-8">

<h2 class="text-lg font-bold mb-4">Add Question</h2>

<input name="question"
placeholder="Question text"
required
class="border border-gray-300 p-2 w-full mb-3">

<select name="type"
id="qtype"
onchange="toggleOptions()"
class="border border-gray-300 p-2 w-full mb-3">

<option value="text">Text</option>
<option value="radio">Multiple Choice</option>
<option value="checkbox">Checkbox</option>

</select>

<label class="flex items-center gap-2 mb-3 text-sm">

<input type="checkbox" name="required">

Required

</label>

<div id="options-section" class="hidden">

<input name="options[]" placeholder="Option 1"
class="border border-gray-300 p-2 w-full mb-2">

<input name="options[]" placeholder="Option 2"
class="border border-gray-300 p-2 w-full mb-2">

<button type="button"
onclick="addOption()"
class="text-gray-500 text-sm hover:text-black">

+ Add Option

</button>

</div>

<button name="add"
class="bg-black text-white px-4 py-2 rounded mt-3 hover:bg-gray-800">

Add Question

</button>

</form>

<div class="bg-white border border-gray-200 p-6 rounded">

<h2 class="text-lg font-bold mb-4">
Questions (<?= $questions->num_rows ?>)
</h2>

<?php if($questions->num_rows == 0): ?>

<p class="text-gray-400">No questions added yet.</p>

<?php else: ?>

<?php $num=1; while($q=$questions->fetch_assoc()): ?>

<div class="border-b border-gray-200 pb-3 mb-3">

<p class="font-semibold">
<?= $num ?>. <?= htmlspecialchars($q['question']) ?>
</p>

<span class="text-xs bg-gray-100 px-2 py-1 rounded">
<?= $q['type'] ?>
</span>

<?php if($q['required']): ?>
<span class="text-xs bg-black text-white px-2 py-1 rounded">
Required
</span>
<?php endif; ?>

</div>

<?php $num++; endwhile; ?>

<?php endif; ?>

</div>

</div>


<script>


function toggleOptions(){

var type=document.getElementById("qtype").value;

document.getElementById("options-section")
.classList.toggle("hidden",type==="text");

}

function addOption(){

var div=document.getElementById("options-section");

var input=document.createElement("input");

input.name="options[]";
input.placeholder="Option";
input.className="border border-gray-300 p-2 w-full mb-2";

div.insertBefore(input,div.lastElementChild);

}



function addDemographics(){

const container=document.getElementById("demographics-container");
const list=document.getElementById("demographics-questions");

container.classList.remove("hidden");

if(list.innerHTML!="") return;

const demographics=[

"Where do you live?",
"What is your age?",
"What is your gender?",
"What is your education level?",
"What is your occupation?",
"What is your marital status?"

];

demographics.forEach((q,i)=>{

list.innerHTML+=`

<div class="border border-gray-200 rounded p-4 mb-4">

<input type="hidden" name="demo_question_text[]" value="${q}">

<p class="font-semibold mb-2">${i+1}. ${q}</p>

<select name="demo_type[]"
onchange="updateDemoOptions(this)"
class="border border-gray-300 p-2 w-full mb-3">

<option value="text">Text</option>
<option value="radio">Multiple Choice</option>
<option value="checkbox">Checkbox</option>

</select>

<div class="demo-options hidden">

<input name="demo_options_${i}[]" placeholder="Option 1"
class="border border-gray-300 p-2 w-full mb-2">

<input name="demo_options_${i}[]" placeholder="Option 2"
class="border border-gray-300 p-2 w-full mb-2">

<button type="button"
onclick="addDemoOption(this)"
class="text-sm text-gray-500 hover:text-black">

+ Add Option

</button>

</div>

</div>
`;

});

}


function updateDemoOptions(select){

const optionBox=select.parentElement.querySelector(".demo-options");

if(select.value==="text"){

optionBox.classList.add("hidden");

}else{

optionBox.classList.remove("hidden");

}

}


function addDemoOption(btn){

const container=btn.parentElement;

const count=container.querySelectorAll("input").length+1;

const input=document.createElement("input");

input.placeholder="Option "+count;
input.name=container.querySelector("input").name;
input.className="border border-gray-300 p-2 w-full mb-2";

container.insertBefore(input,btn);

}

</script>

</body>
</html>