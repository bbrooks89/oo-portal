<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Oo\OoException;
use App\Portal\StatusView;

$flows = $catalog->all();

// Which automation is selected: from the form (POST) or the catalog link (GET).
$requested = $_POST['flow'] ?? $_GET['flow'] ?? null;
$flowId = is_string($requested) && $catalog->find($requested) !== null
    ? $requested
    : array_key_first($flows);
$flow = $catalog->find($flowId);

$errors = [];
$old = is_array($_POST['inputs'] ?? null) ? $_POST['inputs'] : [];
$requester = is_string($_POST['requester'] ?? null)
    ? trim($_POST['requester'])
    : ($_SESSION['requester'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors['form'] = 'This form expired. Reload the page and try again.';
    }
    if ($requester === '') {
        $errors['requester'] = 'Enter your name so this run can be traced to you.';
    }

    $result = $catalog->validate($flowId, $old);
    $errors += $result['errors'];

    if ($errors === []) {
        try {
            $executionId = $runService->start($flow, $flowId, $result['inputs'], $requester);
            $_SESSION['requester'] = $requester;

            // Post/Redirect/Get: refreshing the next page won't start a second run.
            header('Location: status.php?id=' . urlencode($executionId), true, 303);
            exit;
        } catch (OoException $ex) {
            $errors['form'] = 'The run did not start because OO could not be reached. '
                . 'Check that the OO server is running, then try again. (' . $ex->getMessage() . ')';
        }
    }
}

$recent = $runLog->recent(10);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Automation requests</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&display=swap">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="masthead">
    <div class="wrap">
        <h1>Automation requests</h1>
        <p>Start an approved automation and follow it through to the result.</p>
    </div>
</header>

<main class="wrap">
    <div class="request">
        <nav class="catalog" aria-label="Automations">
            <h2>Automations</h2>
            <ul>
                <?php foreach ($flows as $id => $item): ?>
                    <li>
                        <a href="?flow=<?= e(urlencode($id)) ?>"
                            <?= $id === $flowId ? 'aria-current="page"' : '' ?>>
                            <?= e($item['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <section class="form-panel" aria-labelledby="flow-title">
            <h2 id="flow-title"><?= e($flow['name']) ?></h2>
            <p class="lede"><?= e($flow['description']) ?></p>

            <?php if (isset($errors['form'])): ?>
                <p class="alert" role="alert"><?= e($errors['form']) ?></p>
            <?php endif; ?>

            <form method="post" novalidate>
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="flow" value="<?= e($flowId) ?>">

                <?php foreach ($flow['inputs'] as $field):
                    $name = $field['name'];
                    $fieldId = 'in-' . $name;
                    $value = is_string($old[$name] ?? null) ? $old[$name] : '';
                    $error = $errors[$name] ?? null;
                ?>
                    <div class="field<?= $error ? ' has-error' : '' ?>">
                        <label for="<?= e($fieldId) ?>"><?= e($field['label']) ?></label>

                        <?php if ($field['type'] === 'select'): ?>
                            <select id="<?= e($fieldId) ?>" name="inputs[<?= e($name) ?>]"
                                <?= $error ? 'aria-invalid="true" aria-describedby="' . e($fieldId) . '-error"' : '' ?>>
                                <option value="">Choose one</option>
                                <?php foreach ($field['options'] as $optValue => $optLabel): ?>
                                    <option value="<?= e($optValue) ?>"
                                        <?= (string) $optValue === $value ? 'selected' : '' ?>>
                                        <?= e($optLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input id="<?= e($fieldId) ?>" name="inputs[<?= e($name) ?>]"
                                type="<?= e($field['type']) ?>" value="<?= e($value) ?>"
                                <?= $error ? 'aria-invalid="true" aria-describedby="' . e($fieldId) . '-error"' : '' ?>>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <p class="field-error" id="<?= e($fieldId) ?>-error"><?= e($error) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="field<?= isset($errors['requester']) ? ' has-error' : '' ?>">
                    <label for="requester">Requested by</label>
                    <input id="requester" name="requester" type="text" autocomplete="name"
                        value="<?= e($requester) ?>"
                        <?= isset($errors['requester']) ? 'aria-invalid="true" aria-describedby="requester-error"' : '' ?>>
                    <?php if (isset($errors['requester'])): ?>
                        <p class="field-error" id="requester-error"><?= e($errors['requester']) ?></p>
                    <?php endif; ?>
                </div>

                <button type="submit">Start run</button>
            </form>
        </section>
    </div>

    <section class="history" aria-labelledby="history-title">
        <h2 id="history-title">Recent runs</h2>

        <?php if ($recent === []): ?>
            <p class="empty">No runs yet. Start one above and it will appear here.</p>
        <?php else: ?>
            <div class="table-scroll">
                <table>
                    <thead>
                    <tr>
                        <th scope="col">Started</th>
                        <th scope="col">Automation</th>
                        <th scope="col">Requested by</th>
                        <th scope="col">Result</th>
                        <th scope="col">Run ID</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent as $run):
                        [$label, $tone] = StatusView::label($run['status'], $run['result']);
                    ?>
                        <tr>
                            <td><?= e(format_time($run['requestedAt'])) ?></td>
                            <td><?= e($run['flowName']) ?></td>
                            <td><?= e($run['requester']) ?></td>
                            <td><span class="status status--<?= e($tone) ?>"><?= e($label) ?></span></td>
                            <td><a href="status.php?id=<?= e(urlencode($run['executionId'])) ?>"><?= e($run['executionId']) ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
