import pytest

from oo.execution import Execution


def test_completed_and_resolved_is_success():
    execution = Execution("1", "COMPLETED", "RESOLVED")
    assert execution.is_finished()
    assert execution.succeeded()


def test_from_dict_reads_oo_field_names():
    execution = Execution.from_dict({"executionId": "42", "status": "RUNNING"})
    assert execution.execution_id == "42"
    assert execution.status == "RUNNING"
    assert execution.result_status_type is None


@pytest.mark.parametrize(
    ("status", "result", "expected"),
    [
        ("COMPLETED", "RESOLVED", True),
        ("COMPLETED", "ERROR", False),
        ("RUNNING", None, False),
        ("SYSTEM_FAILURE", None, False),
    ],
)
def test_succeeded(status, result, expected):
    assert Execution("1", status, result).succeeded() is expected