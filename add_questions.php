<?php
include("../config/db.php");

if(!isset($_GET['survey_id']) || !is_numeric($_GET['survey_id'])){
    die("Invalid survey. <a href='create_survey.php'>Go back</a>");
}

$survey_id = (int)$_GET['survey_id'];

// Verify survey exists
$stmt = $conn->prepare("SELECT * FROM surveys WHERE id = ?");
$stmt->bind_param("i", $survey_id);
$stmt->execute();
$survey = $stmt->get_result()->fetch_assoc();

if(!$survey){
    die("Survey not found. <a href='create_survey.php'>Go back</a>");
}

$message = "";

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
            $opt = trim($opt);
            if($opt !== ""){
                $stmt2 = $conn->prepare("INSERT INTO options(question_id, option_text) VALUES(?, ?)");
                $stmt2->bind_param("is", $question_id, $opt);
                $stmt2->execute();
            }
        }
    }

    $message = "Question added!";
}

// Handle question deletion
if(isset($_GET['delete_q']) && is_numeric($_GET['delete_q'])){
    $qid = (int)$_GET['delete_q'];
    $conn->prepare("DELETE FROM options WHERE question_id = ?")->execute() || true;
    $del = $conn->prepare("DELETE FROM options WHERE question_id = ?");
    $del->bind_param("i", $qid);
    $del->execute();
    $del2 = $conn->prepare("DELETE FROM questions WHERE id = ? AND survey_id = ?");
    $del2->bind_param("ii", $qid, $survey_id);
    $del2->execute();
    header("Location: add_questions.php?survey_id=$survey_id");
    exit;
}

// Handle question edit
if(isset($_POST['edit_question'])){
    $qid = (int)$_POST['edit_id'];
    $q_text = $_POST['edit_question_text'];
    $q_type = $_POST['edit_type'];
    $q_required = isset($_POST['edit_required']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE questions SET question = ?, type = ?, required = ? WHERE id = ? AND survey_id = ?");
    $stmt->bind_param("ssiii", $q_text, $q_type, $q_required, $qid, $survey_id);
    $stmt->execute();

    // Delete old options
    $del_opts = $conn->prepare("DELETE FROM options WHERE question_id = ?");
    $del_opts->bind_param("i", $qid);
    $del_opts->execute();

    // Insert new options if not text
    if($q_type != "text" && !empty($_POST['edit_options'])){
        foreach($_POST['edit_options'] as $opt){
            $opt = trim($opt);
            if($opt !== ""){
                $stmt2 = $conn->prepare("INSERT INTO options(question_id, option_text) VALUES(?, ?)");
                $stmt2->bind_param("is", $qid, $opt);
                $stmt2->execute();
            }
        }
    }

    $message = "Question updated!";
}

// Get existing questions
$questions = $conn->query("SELECT * FROM questions WHERE survey_id = " . (int)$survey_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Questions - <?= htmlspecialchars($survey['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white min-h-screen py-10">

<div class="max-w-2xl mx-auto px-4">

    <div class="mb-6 flex justify-between items-center">
        <a href="create_survey.php" class="text-gray-500 hover:text-black">&larr; Back to Surveys</a>
    </div>

    <div class="bg-white border border-gray-200 p-4 rounded mb-6">
        <h1 class="text-xl font-bold text-black"><?= htmlspecialchars($survey['title']) ?></h1>
        <p class="text-gray-500 text-sm"><?= htmlspecialchars($survey['description']) ?></p>
    </div>

    <?php if($message): ?>
        <div class="bg-gray-50 border border-gray-200 text-black p-3 rounded mb-4">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white border border-gray-200 p-6 rounded mb-8">
        <h2 class="text-lg font-bold mb-4 text-black">Add a Question</h2>

        <input name="question" placeholder="Question text" required
            class="border border-gray-300 bg-white text-black p-2 w-full mb-3 placeholder-gray-400">

        <select name="type" id="qtype" onchange="toggleOptions()" class="border border-gray-300 bg-white text-black p-2 w-full mb-3">
            <option value="text">Text (free response)</option>
            <option value="radio">Multiple Choice (pick one)</option>
            <option value="checkbox">Checkbox (pick multiple)</option>
        </select>

        <label class="flex items-center gap-2 mb-3 text-sm text-gray-700">
            <input type="checkbox" name="required" value="1">
            Required question
        </label>

        <div id="options-section" class="hidden mb-3">
            <label class="block text-sm font-semibold mb-2 text-gray-600">Options:</label>
            <div id="options-list">
                <input name="options[]" placeholder="Option 1" class="border border-gray-300 bg-white text-black p-2 w-full mb-2 placeholder-gray-400">
                <input name="options[]" placeholder="Option 2" class="border border-gray-300 bg-white text-black p-2 w-full mb-2 placeholder-gray-400">
            </div>
            <button type="button" onclick="addOption()"
                class="text-gray-500 text-sm hover:text-black">+ Add another option</button>
        </div>

        <button name="add"
            class="bg-black text-white px-4 py-2 rounded font-medium hover:bg-gray-800">
            Add Question
        </button>
    </form>

    <div class="bg-white border border-gray-200 p-6 rounded">
        <h2 class="text-lg font-bold mb-4 text-black">
            Questions (<?= $questions->num_rows ?>)
        </h2>

        <?php if($questions->num_rows == 0): ?>
            <p class="text-gray-400">No questions added yet.</p>
        <?php else: ?>
            <?php $num = 1; while($q = $questions->fetch_assoc()): ?>
                <div class="border-b border-gray-200 pb-3 mb-3">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-semibold text-black"><?= $num ?>. <?= htmlspecialchars($q['question']) ?></p>
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                                <?= $q['type'] == 'text' ? 'Text' : ($q['type'] == 'radio' ? 'Multiple Choice' : 'Checkbox') ?>
                            </span>
                            <?php if($q['required']): ?>
                                <span class="text-xs bg-black text-white px-2 py-0.5 rounded">Required</span>
                            <?php endif; ?>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" onclick="openEdit(<?= $q['id'] ?>, <?= htmlspecialchars(json_encode($q['question'])) ?>, '<?= $q['type'] ?>', <?= $q['required'] ?>)"
                               class="text-gray-500 text-sm hover:text-black">Edit</button>
                            <a href="?survey_id=<?= $survey_id ?>&delete_q=<?= $q['id'] ?>"
                               class="text-gray-500 text-sm hover:text-black"
                               onclick="return confirm('Delete this question?')">Delete</a>
                        </div>
                    </div>
                    <?php
                    $q_opts_json = [];
                    if($q['type'] != 'text'){
                        $opts = $conn->query("SELECT * FROM options WHERE question_id = " . (int)$q['id']);
                        if($opts->num_rows > 0){
                            echo '<ul class="ml-4 mt-1 text-sm text-gray-500">';
                            while($o = $opts->fetch_assoc()){
                                echo '<li>• ' . htmlspecialchars($o['option_text']) . '</li>';
                                $q_opts_json[] = $o['option_text'];
                            }
                            echo '</ul>';
                        }
                    }
                    ?>
                    <script>if(!window.qOpts) window.qOpts={}; window.qOpts[<?= $q['id'] ?>]=<?= json_encode($q_opts_json) ?>;</script>
                </div>
            <?php $num++; endwhile; ?>
        <?php endif; ?>
    </div>

</div>

<script>
function toggleOptions(){
    var type = document.getElementById('qtype').value;
    var section = document.getElementById('options-section');
    section.classList.toggle('hidden', type === 'text');
}

var optCount = 2;
function addOption(){
    optCount++;
    var div = document.getElementById('options-list');
    var input = document.createElement('input');
    input.name = 'options[]';
    input.placeholder = 'Option ' + optCount;
    input.className = 'border border-gray-300 bg-white text-black p-2 w-full mb-2 placeholder-gray-400';
    div.appendChild(input);
}

function openEdit(id, question, type, required){
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-question-text').value = question;
    document.getElementById('edit-type').value = type;
    document.getElementById('edit-required').checked = required == 1;

    var optDiv = document.getElementById('edit-options-list');
    optDiv.innerHTML = '';
    var editOptSection = document.getElementById('edit-options-section');
    editOptSection.classList.toggle('hidden', type === 'text');

    var opts = (window.qOpts && window.qOpts[id]) ? window.qOpts[id] : [];
    if(opts.length > 0){
        opts.forEach(function(o, i){
            var inp = document.createElement('input');
            inp.name = 'edit_options[]';
            inp.value = o;
            inp.placeholder = 'Option ' + (i+1);
            inp.className = 'border border-gray-300 bg-white text-black p-2 w-full mb-2 placeholder-gray-400';
            optDiv.appendChild(inp);
        });
    } else if(type !== 'text') {
        for(var i=1;i<=2;i++){
            var inp = document.createElement('input');
            inp.name = 'edit_options[]';
            inp.placeholder = 'Option ' + i;
            inp.className = 'border border-gray-300 bg-white text-black p-2 w-full mb-2 placeholder-gray-400';
            optDiv.appendChild(inp);
        }
    }

    document.getElementById('edit-modal').classList.remove('hidden');
}

function closeEdit(){
    document.getElementById('edit-modal').classList.add('hidden');
}

function toggleEditOptions(){
    var type = document.getElementById('edit-type').value;
    document.getElementById('edit-options-section').classList.toggle('hidden', type === 'text');
}

function addEditOption(){
    var div = document.getElementById('edit-options-list');
    var count = div.querySelectorAll('input').length + 1;
    var inp = document.createElement('input');
    inp.name = 'edit_options[]';
    inp.placeholder = 'Option ' + count;
    inp.className = 'border border-gray-300 bg-white text-black p-2 w-full mb-2 placeholder-gray-400';
    div.appendChild(inp);
}
</script>

<!-- Edit Modal -->
<div id="edit-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white border border-gray-200 rounded p-6 w-full max-w-lg mx-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-black">Edit Question</h2>
            <button type="button" onclick="closeEdit()" class="text-gray-400 hover:text-black text-xl">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="edit_question" value="1">
            <input type="hidden" name="edit_id" id="edit-id">

            <input name="edit_question_text" id="edit-question-text" placeholder="Question text" required
                class="border border-gray-300 bg-white text-black p-2 w-full mb-3 placeholder-gray-400">

            <select name="edit_type" id="edit-type" onchange="toggleEditOptions()" class="border border-gray-300 bg-white text-black p-2 w-full mb-3">
                <option value="text">Text (free response)</option>
                <option value="radio">Multiple Choice (pick one)</option>
                <option value="checkbox">Checkbox (pick multiple)</option>
            </select>

            <label class="flex items-center gap-2 mb-3 text-sm text-gray-700">
                <input type="checkbox" name="edit_required" id="edit-required" value="1">
                Required question
            </label>

            <div id="edit-options-section" class="hidden mb-3">
                <label class="block text-sm font-semibold mb-2 text-gray-600">Options:</label>
                <div id="edit-options-list"></div>
                <button type="button" onclick="addEditOption()"
                    class="text-gray-500 text-sm hover:text-black">+ Add another option</button>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                    class="bg-black text-white px-4 py-2 rounded font-medium hover:bg-gray-800">
                    Save Changes
                </button>
                <button type="button" onclick="closeEdit()"
                    class="border border-gray-300 text-black px-4 py-2 rounded hover:bg-gray-50">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>