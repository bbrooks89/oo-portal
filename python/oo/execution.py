from dataclasses import dataclass

FINISHED_STATUSES = {"COMPLETED", "SYSTEM_FAILURE", "CANCELED"}


@dataclass(frozen=True)
class Execution:
    execution_id: str
    status: str
    result_status_type: str | None = None

    @classmethod
    def from_dict(cls, data: dict) -> "Execution":
        return cls(
            execution_id=str(data.get("executionId", "")),
            status=str(data.get("status", "UNKNOWN")),
            result_status_type=data.get("resultStatusType"),
        )

    def is_finished(self) -> bool:
        return self.status in FINISHED_STATUSES

    def succeeded(self) -> bool:
        return self.status == "COMPLETED" and self.result_status_type == "RESOLVED"
