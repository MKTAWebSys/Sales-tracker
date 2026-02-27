Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

$runDir = Join-Path $projectRoot '.run'
if (-not (Test-Path $runDir)) {
    Write-Host 'No .run directory found. Nothing to stop.'
    exit 0
}

function Stop-ManagedProcess {
    param(
        [string]$Name,
        [string]$PidFile
    )

    if (-not (Test-Path $PidFile)) {
        Write-Host "${Name} not running (no pid file)."
        return
    }

    $rawPid = Get-Content $PidFile -ErrorAction SilentlyContinue | Select-Object -First 1
    $procId = 0
    if (-not [int]::TryParse(($rawPid | ForEach-Object { $_.ToString().Trim() }), [ref]$procId)) {
        Remove-Item $PidFile -ErrorAction SilentlyContinue
        Write-Host "${Name} pid file invalid; cleaned up."
        return
    }

    try {
        $proc = Get-Process -Id $procId -ErrorAction Stop
        Stop-Process -Id $proc.Id -Force
        Write-Host "${Name} stopped (PID $procId)."
    } catch {
        Write-Host "${Name} process already stopped (PID $procId)."
    }

    Remove-Item $PidFile -ErrorAction SilentlyContinue
}

Stop-ManagedProcess -Name 'Backend' -PidFile (Join-Path $runDir 'backend.pid')
Stop-ManagedProcess -Name 'Frontend' -PidFile (Join-Path $runDir 'frontend.pid')
