# OO Portal (practice project)

An object-oriented PHP client for the Operations Orchestration REST API, plus a mock OO server to practice against.

## Layout

```
oo-portal/
├── composer.json          PSR-4 autoloading: App\ → src/
├── docker-compose.yml     mock-oo server + app container (PHP 8.3)
├── app/bootstrap.php      shared setup for portal pages (autoload, session, helpers)
├── bin/run-flow.php       CLI script: start a flow, wait, print the result
├── mock-oo/router.php     fake OO Central REST API
├── public/                the web portal (document root)
│   ├── index.php          pick an automation, enter inputs, start a run
│   ├── status.php         live progress for one run
│   ├── api/status.php     JSON status endpoint the status page polls
│   └── style.css
├── src/Oo/
│   ├── FlowClient.php     start flows, get status, wait for completion
│   ├── Execution.php      one run's status (readonly value object)
│   └── OoException.php    errors from OO
├── src/Portal/
│   ├── FlowCatalog.php    available automations and input validation
│   ├── RunLog.php         who ran what, when, and the result
│   └── StatusView.php     OO statuses to on-screen labels
└── storage/runs.json      created on the first run
```

## Run it (from WSL, in the oo-portal folder)

1. Generate Composer's autoloader (creates `vendor/`):

   ```bash
   docker run --rm -v "$PWD":/app -w /app composer:2 dump-autoload
   ```

2. Start the mock OO server:

   ```bash
   docker compose up -d mock-oo
   ```

3. Run a flow that succeeds:

   ```bash
   docker compose run --rm app php bin/run-flow.php open
   ```

   Expect `Started execution ...`, a ~5 second wait, then `Finished: COMPLETED / RESOLVED`.

4. Run a flow that fails:

   ```bash
   docker compose run --rm app php bin/run-flow.php fail
   echo $?   # 1 = flow finished with an error result
   ```

5. Stop the mock server when done:

   ```bash
   docker compose down
   ```

## Step 2: the web portal

1. Start the portal (it starts the mock OO server too):

   ```bash
   docker compose up -d portal
   ```

2. Open http://localhost:8000 in your Windows browser.

3. Pick an automation, fill in the inputs, enter your name, and click **Start run**.
   The status page updates every 2 seconds until the run finishes.

4. Try the failure paths:
   - Firewall automation with action "Simulate a failure (practice)".
   - Submit with an empty field, or a server name like `bad name!`, to see validation.
   - `docker compose stop mock-oo`, then start a run, to see the "OO could not be reached" message.

## Try these to learn

- Stop the mock server and run step 3 again. Watch `OoException` get caught (exit code 2).
- Add a `cancel(string $executionId)` method to `FlowClient`.
- Add a `getExecutions(int $limit)` method that lists recent runs (add a matching route to the mock).
- Real OO 10.x often requires a CSRF token header on POST requests. Add support for it.
