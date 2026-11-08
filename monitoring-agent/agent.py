#!/usr/bin/env python3
"""NetWatch monitoring agent using native OS ping and the monitoring API."""

from __future__ import annotations

import argparse
import json
import hashlib
import hmac
import platform
import re
import subprocess
import time
from datetime import datetime, timezone
from pathlib import Path
from typing import Any
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen


def load_config(path: Path) -> dict[str, Any]:
    with path.open("r", encoding="utf-8") as handle:
        config = json.load(handle)
    if not isinstance(config, dict) or not config.get("api_url") or not config.get("agent_key"):
        raise ValueError("config must contain api_url and agent_key")
    return config


def ping(host: str, timeout_seconds: int) -> dict[str, Any]:
    timeout_ms = max(1, timeout_seconds * 1000)
    if platform.system().lower() == "windows":
        command = ["ping", "-n", "1", "-w", str(timeout_ms), host]
    else:
        command = ["ping", "-c", "1", "-W", str(timeout_seconds), host]

    try:
        completed = subprocess.run(
            command,
            capture_output=True,
            text=True,
            timeout=timeout_seconds + 2,
            check=False,
        )
    except (OSError, subprocess.TimeoutExpired) as error:
        return {
            "is_reachable": False,
            "response_time_ms": None,
            "packet_loss_percent": 100.0,
            "error_message": str(error),
        }

    output = f"{completed.stdout}\n{completed.stderr}"
    match = re.search(r"time[=<]\s*([0-9]+(?:[.,][0-9]+)?)\s*ms", output, re.IGNORECASE)
    reachable = completed.returncode == 0
    return {
        "is_reachable": reachable,
        "response_time_ms": float(match.group(1).replace(",", ".")) if reachable and match else None,
        "packet_loss_percent": 0.0 if reachable else 100.0,
        "error_message": None if reachable else output.strip()[-500:],
    }


def get_devices(config: dict[str, Any]) -> dict[str, Any]:
    timestamp = str(int(time.time()))
    signature = hmac.new(
        config["agent_key"].encode("utf-8"),
        f"{timestamp}\n".encode("utf-8"),
        hashlib.sha256,
    ).hexdigest()
    request = Request(
        f"{config['api_url']}?action=devices",
        headers={
            "X-NetWatch-Agent-Key": config["agent_key"],
            "X-NetWatch-Agent-Timestamp": timestamp,
            "X-NetWatch-Agent-Signature": signature,
            "Accept": "application/json",
        },
    )
    with urlopen(request, timeout=15) as response:
        payload = json.loads(response.read().decode("utf-8"))
    if not isinstance(payload, dict) or not isinstance(payload.get("devices"), list):
        raise ValueError("API returned an invalid device response")
    return payload


def send_results(config: dict[str, Any], results: list[dict[str, Any]]) -> None:
    body = json.dumps({"results": results}).encode("utf-8")
    timestamp = str(int(time.time()))
    signature = hmac.new(
        config["agent_key"].encode("utf-8"),
        timestamp.encode("utf-8") + b"\n" + body,
        hashlib.sha256,
    ).hexdigest()
    request = Request(
        f"{config['api_url']}?action=results",
        data=body,
        method="POST",
        headers={
            "X-NetWatch-Agent-Key": config["agent_key"],
            "X-NetWatch-Agent-Timestamp": timestamp,
            "X-NetWatch-Agent-Signature": signature,
            "Content-Type": "application/json",
            "Accept": "application/json",
        },
    )
    with urlopen(request, timeout=15) as response:
        if response.status < 200 or response.status >= 300:
            raise RuntimeError(f"API returned HTTP {response.status}")


def run_once(config: dict[str, Any]) -> None:
    payload = get_devices(config)
    settings = payload.get("settings") or {}
    timeout = int(settings.get("ping_timeout_seconds", 5))
    checked_at = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M:%S")
    results = []
    for device in payload["devices"]:
        if not isinstance(device, dict) or "id" not in device or "ip_address" not in device:
            raise ValueError("API returned an invalid device entry")
        result = ping(str(device["ip_address"]), timeout)
        result.update({"device_id": int(device["id"]), "checked_at": checked_at})
        results.append(result)
    if results:
        send_results(config, results)
    print(f"Checked {len(results)} device(s).")


def main() -> None:
    parser = argparse.ArgumentParser(description="NetWatch monitoring agent")
    parser.add_argument("--config", type=Path, default=Path("config.json"))
    parser.add_argument("--once", action="store_true", help="Run one monitoring cycle and exit")
    args = parser.parse_args()
    config = load_config(args.config)
    while True:
        try:
            run_once(config)
        except (HTTPError, URLError, OSError, ValueError, RuntimeError) as error:
            print(f"Monitoring cycle failed: {error}")
        if args.once:
            return
        time.sleep(max(5, int(config.get("poll_seconds", 30))))


if __name__ == "__main__":
    main()
