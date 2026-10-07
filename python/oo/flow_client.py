import requests

from .execution import Execution
from .runner import FlowRunner, OoError


class FlowClient(FlowRunner):
    """Talks to OO Central's REST API (modeled on OO 10.x)."""

    def __init__(self, base_url: str, username: str, password: str, timeout: float = 10) -> None:
        self._base_url = base_url.rstrip("/")
        self._timeout = timeout
        self._session = requests.Session()
        self._session.auth = (username, password)
        self._session.headers["Accept"] = "application/json"

    def start_flow(self, flow_uuid: str, inputs: dict | None = None, run_name: str | None = None) -> str:
        body = {"flowUuid": flow_uuid, "inputs": inputs or {}}
        if run_name is not None:
            body["runName"] = run_name

        response = self._request("POST", "/executions", json=body)
        return response.text.strip().strip('"')

    def get_execution(self, execution_id: str) -> Execution:
        response = self._request("GET", f"/executions/{execution_id}/summary")
        data = response.json()
        summary = data[0]
        return Execution.from_dict(summary)

    def _request(self, method: str, path: str, **kwargs) -> requests.Response:
        """
        Sends an HTTP request to the API and returns the response.

        Args:
            method: The HTTP method (e.g., "GET", "POST", "PUT", "DELETE").
            path: The endpoint path (e.g., "/executions/123").
            **kwargs: Additional keyword arguments passed directly to the 
                      underlying requests call (such as json, params, headers, or timeout).
        """
        try:
            response = self._session.request(
                method, self._base_url + path, timeout=self._timeout, **kwargs
            )
        except requests.RequestException as error:
            raise OoError(f"Request to OO failed: {error}") from error

        if response.status_code >= 400:
            raise OoError(f"OO returned HTTP {response.status_code}: {response.text}")
        return response