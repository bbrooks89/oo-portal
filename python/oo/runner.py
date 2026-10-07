import time
from abc import ABC, abstractmethod

from .execution import Execution


class OoError(Exception):
    """Raised when OO can't be reached or returns an error."""


class FlowRunner(ABC):
    @abstractmethod
    def start_flow(self, flow_uuid: str, inputs: dict | None = None, run_name: str | None = None) -> str:
        ...

    @abstractmethod
    def get_execution(self, execution_id: str) -> Execution:
        ...

    def wait_for_completion(self, execution_id: str, poll_seconds: float = 2, max_wait_seconds: float = 60) -> Execution:
        deadline = time.monotonic() + max_wait_seconds
        while True:
            execution = self.get_execution(execution_id)
            if execution.is_finished():
                return execution
            if time.monotonic() >= deadline:
                raise OoError(f"Execution {execution_id} did not finish within {max_wait_seconds} seconds")
            time.sleep(poll_seconds)