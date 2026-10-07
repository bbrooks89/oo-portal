import random

from .execution import Execution
from .runner import FlowRunner


class FakeFlowRunner(FlowRunner):
    def __init__(self) -> None:
        self._runs: dict[str, dict] = {}

    def start_flow(self, flow_uuid: str, inputs: dict | None = None, run_name: str | None = None) -> str:
        execution_id = str(random.randint(100_000_000, 999_999_999))
        self._runs[execution_id] = inputs or {}
        return execution_id

    def get_execution(self, execution_id: str) -> Execution:
        inputs = self._runs.get(execution_id, {})
        action = inputs.get("action", "")
        result = "ERROR" if action == "fail" else "RESOLVED"
        return Execution(execution_id, "COMPLETED", result)