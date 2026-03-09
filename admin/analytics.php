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
    die("Survey not found. <a href='create_survey.php'>Go back</a>");
}

// Total responses
$r_stmt = $conn->prepare("SELECT COUNT(*) as total FROM responses WHERE survey_id = ?");
$r_stmt->bind_param("i", $survey_id);
$r_stmt->execute();
$totalResponses = $r_stmt->get_result()->fetch_assoc()['total'];

// Get questions
$q_stmt = $conn->prepare("SELECT * FROM questions WHERE survey_id = ?");
$q_stmt->bind_param("i", $survey_id);
$q_stmt->execute();
$questions = $q_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Build analytics data per question
$analytics = [];
foreach($questions as $q) {
    $data = ['question' => $q, 'answers' => [], 'distribution' => [], 'options' => [], 'total_answers' => 0];

    $a_stmt = $conn->prepare("SELECT a.answer FROM answers a JOIN responses r ON r.id = a.response_id WHERE a.question_id = ? AND r.survey_id = ?");
    $a_stmt->bind_param("ii", $q['id'], $survey_id);
    $a_stmt->execute();
    $answers = $a_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $data['total_answers'] = count($answers);

    if($q['type'] == 'text') {
        $data['answers'] = array_column($answers, 'answer');
    } else {
        $distribution = [];
        foreach($answers as $a) {
            $val = $a['answer'];
            if($q['type'] == 'checkbox') {
                $vals = array_map('trim', explode(',', $val));
                foreach($vals as $v) {
                    if($v !== '') {
                        $distribution[$v] = ($distribution[$v] ?? 0) + 1;
                    }
                }
            } else {
                if($val !== '' && $val !== null) {
                    $distribution[$val] = ($distribution[$val] ?? 0) + 1;
                }
            }
        }

        // Get defined options to show zero-count ones too
        $o_stmt = $conn->prepare("SELECT option_text FROM options WHERE question_id = ?");
        $o_stmt->bind_param("i", $q['id']);
        $o_stmt->execute();
        $opts = $o_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $data['options'] = array_column($opts, 'option_text');

        foreach($data['options'] as $opt) {
            if(!isset($distribution[$opt])) {
                $distribution[$opt] = 0;
            }
        }

        $data['distribution'] = $distribution;
    }

    $analytics[] = $data;
}

// Response timeline (per day)
$tl_stmt = $conn->prepare("SELECT DATE(submitted_at) as date, COUNT(*) as count FROM responses WHERE survey_id = ? GROUP BY DATE(submitted_at) ORDER BY date ASC");
$tl_stmt->bind_param("i", $survey_id);
$tl_stmt->execute();
$timeline = $tl_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - <?= htmlspecialchars($survey['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
</head>
<body class="bg-white min-h-screen py-10">

<div class="max-w-4xl mx-auto px-4">

    <div class="mb-6 flex justify-between items-center">
        <a href="create_survey.php" class="text-gray-500 hover:text-black">&larr; Back to Surveys</a>
        <a href="responses.php?survey_id=<?= $survey_id ?>" class="text-gray-500 hover:text-black">View Responses &rarr;</a>
    </div>

    <!-- Survey Info -->
    <div class="bg-white border border-gray-200 p-6 rounded mb-6">
        <h1 class="text-xl font-bold text-black"><?= htmlspecialchars($survey['title']) ?></h1>
        <p class="text-gray-500"><?= htmlspecialchars($survey['description']) ?></p>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-gray-200 p-4 rounded text-center">
            <p class="text-3xl font-bold text-black"><?= $totalResponses ?></p>
            <p class="text-sm text-gray-500">Total Responses</p>
        </div>
        <div class="bg-white border border-gray-200 p-4 rounded text-center">
            <p class="text-3xl font-bold text-black"><?= count($questions) ?></p>
            <p class="text-sm text-gray-500">Questions</p>
        </div>
        <div class="bg-white border border-gray-200 p-4 rounded text-center">
            <p class="text-3xl font-bold text-black">
                <?php
                if($totalResponses > 0 && count($questions) > 0) {
                    $totalAnswered = 0;
                    foreach($analytics as $a) $totalAnswered += $a['total_answers'];
                    echo round(($totalAnswered / ($totalResponses * count($questions))) * 100) . '%';
                } else {
                    echo '—';
                }
                ?>
            </p>
            <p class="text-sm text-gray-500">Completion Rate</p>
        </div>
    </div>

    <?php if($totalResponses == 0): ?>
    <div class="bg-white border border-gray-200 p-6 rounded">
        <p class="text-gray-400">No responses yet. Analytics will appear once responses are submitted.</p>
    </div>
    <?php else: ?>

    <!-- Response Timeline -->
    <?php if(count($timeline) > 0): ?>
    <div class="bg-white border border-gray-200 p-6 rounded mb-6">
        <h2 class="text-lg font-bold mb-4 text-black">Responses Over Time</h2>
        <canvas id="timelineChart" height="100"></canvas>
    </div>
    <?php endif; ?>

    <!-- Per-Question Analytics -->
    <?php foreach($analytics as $i => $a): ?>
    <div class="bg-white border border-gray-200 p-6 rounded mb-4">
        <div class="flex justify-between items-start mb-3">
            <div>
                <h3 class="font-bold text-black"><?= ($i + 1) . '. ' . htmlspecialchars($a['question']['question']) ?></h3>
                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                    <?= $a['question']['type'] == 'text' ? 'Text' : ($a['question']['type'] == 'radio' ? 'Multiple Choice' : 'Checkbox') ?>
                </span>
            </div>
            <span class="text-sm text-gray-400"><?= $a['total_answers'] ?> answer<?= $a['total_answers'] != 1 ? 's' : '' ?></span>
        </div>

        <?php if($a['question']['type'] == 'text'): ?>
            <!-- Text Answers -->
            <?php if(count($a['answers']) > 0): ?>
            <div class="max-h-64 overflow-y-auto">
                <?php foreach($a['answers'] as $idx => $ans): ?>
                <div class="py-2 <?= $idx < count($a['answers']) - 1 ? 'border-b border-gray-100' : '' ?>">
                    <p class="text-sm text-gray-700"><?= htmlspecialchars($ans ?: '(no answer)') ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-gray-400 text-sm">No answers.</p>
            <?php endif; ?>

        <?php else: ?>
            <!-- Chart + Table for Choice Questions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <canvas id="chart_<?= $a['question']['id'] ?>" height="200"></canvas>
                </div>
                <div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-1 text-gray-500">Option</th>
                                <th class="text-right py-1 text-gray-500">Count</th>
                                <th class="text-right py-1 text-gray-500">%</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $totalVotes = array_sum($a['distribution']);
                        foreach($a['distribution'] as $label => $count):
                            $pct = $totalVotes > 0 ? round(($count / $totalVotes) * 100, 1) : 0;
                        ?>
                            <tr class="border-b border-gray-100">
                                <td class="py-1 text-black"><?= htmlspecialchars($label) ?></td>
                                <td class="py-1 text-right text-gray-600"><?= $count ?></td>
                                <td class="py-1 text-right text-gray-600"><?= $pct ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>

</div>

<script>
const chartColors = [
    '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
    '#ec4899', '#06b6d4', '#f97316', '#14b8a6', '#6366f1'
];

<?php if($totalResponses > 0 && count($timeline) > 0): ?>
// Response Timeline Chart
new Chart(document.getElementById('timelineChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($timeline, 'date')) ?>,
        datasets: [{
            label: 'Responses',
            data: <?= json_encode(array_map('intval', array_column($timeline, 'count'))) ?>,
            borderColor: '#000',
            backgroundColor: 'rgba(0,0,0,0.05)',
            fill: true,
            tension: 0.3,
            pointRadius: 4,
            pointBackgroundColor: '#000'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});
<?php endif; ?>

<?php foreach($analytics as $a): ?>
<?php if($a['question']['type'] != 'text' && count($a['distribution']) > 0): ?>
new Chart(document.getElementById('chart_<?= $a['question']['id'] ?>'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($a['distribution'])) ?>,
        datasets: [{
            data: <?= json_encode(array_values($a['distribution'])) ?>,
            backgroundColor: chartColors.slice(0, <?= count($a['distribution']) ?>),
            borderWidth: 1,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 8, font: { size: 11 } } }
        }
    }
});
<?php endif; ?>
<?php endforeach; ?>
</script>

</body>
</html>
