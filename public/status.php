<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Portal\StatusView;

$id = is_string($_GET['id'] ?? null) ? $_GET['id'] : '';
$run = $runLog->find($id);

if ($run === null) {
    http_response_code(404);
}

[$label, $tone] = $run ? StatusView::label($run['status'], $run['result']) : ['', 'neutral'];
$finished = $run ? StatusView::isFinished($run['status']) : false;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $run ? e($run['flowName']) . ' run' : 'Run not found' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&display=swap">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="masthead">
    <div class="wrap">
        <p class="back"><a href="index.php">Back to automation requests</a></p>
        <h1><?= $run ? e($run['flowName']) : 'Run not found' ?></h1>
    </div>
</header>

<main class="wrap">
<?php if ($run === null): ?>
    <p class="alert">No run with ID <?= e($id) ?> was started from this portal. Check the ID, or start a new run from the automation list.</p>
<?php else: ?>
    <section class="run" data-run-id="<?= e($run['executionId']) ?>" data-finished="<?= $finished ? '1' : '0' ?>">
        <div class="run-head">
            <span id="run-status" class="status status--<?= e($tone) ?>" role="status" aria-live="polite"><?= e($label) ?></span>
            <p class="run-id">Run ID <?= e($run['executionId']) ?></p>
        </div>

        <ol class="track" aria-label="Run progress">
            <li data-state="done">
                <span class="track-name">Requested</span>
                <span class="track-detail"><?= e(format_time($run['requestedAt'])) ?> by <?= e($run['requester']) ?></span>
            </li>
            <li id="stage-running" data-state="<?= $finished ? 'done' : 'active' ?>">
                <span class="track-name">Running in OO</span>
                <span class="track-detail" id="running-detail"><?= $finished ? 'Done' : 'In progress' ?></span>
            </li>
            <li id="stage-finished" data-state="<?= $finished ? 'done' : 'waiting' ?>" data-tone="<?= e($tone) ?>">
                <span class="track-name">Result</span>
                <span class="track-detail" id="finished-detail"><?= $finished ? e($label) : 'Waiting for the run to finish' ?></span>
            </li>
        </ol>

        <p id="poll-error" class="alert" role="alert" hidden></p>

        <h2>Inputs</h2>
        <dl class="inputs">
            <?php foreach ($run['inputs'] as $name => $value): ?>
                <div>
                    <dt><?= e($name) ?></dt>
                    <dd><?= e($value) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </section>

    <script>
    (() => {
        const run = document.querySelector('.run');
        if (run.dataset.finished === '1') return;

        const statusEl = document.getElementById('run-status');
        const runningStage = document.getElementById('stage-running');
        const runningDetail = document.getElementById('running-detail');
        const finishedStage = document.getElementById('stage-finished');
        const finishedDetail = document.getElementById('finished-detail');
        const errorEl = document.getElementById('poll-error');

        async function poll() {
            try {
                const response = await fetch('api/status.php?id=' + encodeURIComponent(run.dataset.runId));
                const data = await response.json();
                if (!response.ok) throw new Error(data.error || 'The status check failed.');

                errorEl.hidden = true;
                statusEl.textContent = data.label;
                statusEl.className = 'status status--' + data.tone;

                if (data.finished) {
                    runningStage.dataset.state = 'done';
                    runningDetail.textContent = 'Done';
                    finishedStage.dataset.state = 'done';
                    finishedStage.dataset.tone = data.tone;
                    finishedDetail.textContent = data.label;
                    return;
                }
                setTimeout(poll, 2000);
            } catch (err) {
                errorEl.textContent = 'Could not get the latest status: ' + err.message + ' Retrying in 5 seconds.';
                errorEl.hidden = false;
                setTimeout(poll, 5000);
            }
        }

        setTimeout(poll, 1000);
    })();
    </script>
<?php endif; ?>
</main>
</body>
</html>
