# NetWatch

NetWatch is a PHP 8.2+/MariaDB network monitoring application with a Python
monitoring agent.

## Current implementation

- MySQL/MariaDB schema with users, devices, monitoring history, incidents,
  alerts, topology connections, settings, and audit logs.
- Session authentication with `password_hash()`, `password_verify()`, CSRF
  tokens, session ID regeneration, logout, and admin/operator/viewer roles.
- Device CRUD with IP/MAC validation, status/type filters, search, and detail
  pages.
- Authenticated Python agent API using the `X-NetWatch-Agent-Key` header.
- Real OS ping checks with measured latency and packet loss.
- Dashboard summary and protected AJAX polling endpoint.
- Explicit topology connection CRUD and Cytoscape visualization.
- Stateful offline, latency, packet-loss, and recovery incidents with
  deduplicated alerts and acknowledgement.
- Optional Discord webhook notifications with failure logging.
- Date-filtered availability/latency/packet-loss reports with CSV export.
- Administrator settings for monitoring intervals, thresholds, time zone, and
  Discord notifications.
- Audit records for device, topology, alert, and settings changes.
- Signed, timestamped monitoring-agent requests with bounded payload
  validation and replay-window protection.
- Automated PowerShell setup, validation, and agent launch scripts.
- Responsive dark enterprise dashboard shell with icon navigation, status
  indicators, live metric cards, latency chart, and mobile off-canvas menu.

Runtime integration tests require a running XAMPP MySQL service. PHP syntax and
Python compilation validation are included below.

## XAMPP setup

1. Start Apache and MySQL from the XAMPP Control Panel.
2. Create the database and tables:

   ```powershell
   & 'C:\xampp\mysql\bin\mysql.exe' -u root -p < 'C:\xampp\htdocs\Netwatch\database\schema.sql'
   ```

3. Configure the application environment before starting Apache. For a local
   XAMPP installation, the defaults connect to `netwatch` as `root` with an
   empty password. Prefer environment variables for non-default credentials:

   ```powershell
   $env:NETWATCH_DB_DSN = 'mysql:host=127.0.0.1;dbname=netwatch;charset=utf8mb4'
   $env:NETWATCH_DB_USER = 'netwatch'
   $env:NETWATCH_DB_PASSWORD = 'replace-this-password'
   $env:NETWATCH_AGENT_KEY = 'generate-a-long-random-secret'
   ```

4. Create the first administrator from a terminal:

   ```powershell
   & 'C:\xampp\php\php.exe' 'C:\xampp\htdocs\Netwatch\database\create_admin.php' admin admin@example.test 'use-a-12-character-password'
   ```

5. Open `http://localhost/Netwatch/public/login`.

The application uses a single front controller with these routes:

```text
/login
/dashboard
/devices
/devices/create
/devices/{id}
/devices/{id}/edit
/network
/alerts
/reports
/settings
```

The legacy `.php` entry points remain available for migration compatibility,
but all application navigation uses the clean routes above. Apache
`mod_rewrite` must be enabled and `AllowOverride All` must be permitted for
`C:\xampp\htdocs\Netwatch\public`.

Automated setup from PowerShell:

```powershell
& 'C:\xampp\htdocs\Netwatch\scripts\setup.ps1'
```

For production, configure an Apache virtual host whose document root is
`C:\xampp\htdocs\Netwatch\public` so configuration and application source
directories are not web-accessible.

## Monitoring agent

Copy `monitoring-agent/config.example.json` to `monitoring-agent/config.json`,
set the same API URL and secret as `NETWATCH_AGENT_KEY`, then run:

```powershell
python 'C:\xampp\htdocs\Netwatch\monitoring-agent\agent.py' --config 'C:\xampp\htdocs\Netwatch\monitoring-agent\config.json'
```

Use `--once` to execute one check cycle. The agent uses the host operating
system's `ping` command; ICMP may require firewall permissions and does not
measure interface traffic throughput.

Run the long-lived agent with:

```powershell
& 'C:\xampp\htdocs\Netwatch\scripts\run-agent.ps1'
```

The API requires `X-NetWatch-Agent-Key`,
`X-NetWatch-Agent-Timestamp`, and an HMAC-SHA256
`X-NetWatch-Agent-Signature` over `timestamp + newline + request body`.

## API endpoints

- `GET /public/api/monitoring.php?action=devices` — signed agent device/settings fetch.
- `POST /public/api/monitoring.php?action=results` — signed monitoring result ingestion.
- `GET /public/api/dashboard.php` — authenticated dashboard summary and chart data.

The monitoring API accepts at most 500 results per request, validates timestamps
and measured numeric ranges, and rejects signatures older than five minutes.

## Production checklist

- Use an Apache virtual host with `public` as the document root.
- Set a long random `NETWATCH_AGENT_KEY`; never commit `config.json`.
- Use a dedicated MariaDB user with only the `netwatch` database privileges.
- Run the agent using Windows Task Scheduler with `scripts\run-agent.ps1`.
- Back up the `netwatch` database and `storage/logs` on a scheduled basis.
- Keep PHP, MariaDB, Apache, and browser dependencies patched.

## Validation

PHP syntax validation:

```powershell
Get-ChildItem -Recurse -Filter '*.php' | ForEach-Object { & 'C:\xampp\php\php.exe' -l $_.FullName }
```

Or use the automated validator:

```powershell
& 'C:\xampp\htdocs\Netwatch\scripts\validate.ps1'
```

Python validation:

```powershell
python -m py_compile monitoring-agent/agent.py
```

Database lifecycle smoke test (requires MySQL running and the schema imported):

```powershell
$env:NETWATCH_DB_DSN = 'mysql:host=127.0.0.1;dbname=netwatch;charset=utf8mb4'
$env:NETWATCH_DB_USER = 'root'
$env:NETWATCH_DB_PASSWORD = ''
& 'C:\xampp\php\php.exe' 'C:\xampp\htdocs\Netwatch\tests\smoke.php'
```
