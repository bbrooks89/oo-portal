import pytest

from oo.fake_runner import FakeFlowRunner
from oo.flow_client import FlowClient
from oo.runner import OoError


@pytest.fixture
def runner():
    return FakeFlowRunner()


def test_fake_fail_action_ends_in_error(runner):
    execution_id = runner.start_flow("firewall-maintenance", {"action": "fail"})
    execution = runner.wait_for_completion(execution_id)
    assert execution.result_status_type == "ERROR"

def test_fake_open_action_succeeds(runner):
    execution_id = runner.start_flow("firewall-maintenance", {"action": "open"})
    execution = runner.wait_for_completion(execution_id)
    assert execution.result_status_type == "RESOLVED"

def test_unreachable_server_raises_oo_error():
    client = FlowClient("http://127.0.0.1:9", "user", "pass", timeout=2)
    with pytest.raises(OoError):
        client.start_flow("firewall-maintenance")