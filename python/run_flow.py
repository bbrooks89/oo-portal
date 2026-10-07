import sys

from oo.fake_runner import FakeFlowRunner
from oo.runner import OoError


def main() -> int:
    action = sys.argv[1] if len(sys.argv) > 1 else "open"
    runner = FakeFlowRunner()  # the real REST client comes in the next lesson

    try:
        execution_id = runner.start_flow(
            "firewall-maintenance",
            {"action": action, "client": "state-01"},
            f"Firewall {action} - practice",
        )
        print(f"Started execution {execution_id}")

        execution = runner.wait_for_completion(execution_id)
        print(f"Finished: {execution.status} / {execution.result_status_type}")
        return 0 if execution.succeeded() else 1
    except OoError as error:
        print(f"OO error: {error}", file=sys.stderr)
        return 2


if __name__ == "__main__":
    sys.exit(main())