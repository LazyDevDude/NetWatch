param(
    [string]$Config = "$PSScriptRoot\..\monitoring-agent\config.json"
)

$ErrorActionPreference = "Stop"
$agent = Join-Path (Split-Path -Parent $PSScriptRoot) "monitoring-agent\agent.py"
if (!(Test-Path $Config)) {
    throw "Missing agent config: $Config. Copy config.example.json to config.json and set the API key."
}
python $agent --config $Config
