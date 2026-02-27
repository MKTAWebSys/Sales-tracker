Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

$runDir = Join-Path $projectRoot '.run'

function Show-ManagedProcess {
    param(
        [string]$Name,
        [string]$PidFile
    )

    if (-not (Test-Path $PidFile)) {
        Write-Host "${Name}: not running"
        return
    }

    $rawPid = Get-Content $PidFile -ErrorAction SilentlyContinue | Select-Object -First 1
    $procId = 0
    if (-not [int]::TryParse(($rawPid | ForEach-Object { $_.ToString().Trim() }), [ref]$procId)) {
        Write-Host "${Name}: pid file invalid"
        return
    }

    try {
        $proc = Get-Process -Id $procId -ErrorAction Stop
        Write-Host "${Name}: running (PID $($proc.Id))"
    } catch {
        Write-Host "${Name}: not running (stale pid $procId)"
    }
}

if (-not (Test-Path $runDir)) {
    Write-Host 'No .run directory found.'
    exit 0
}

Show-ManagedProcess -Name 'Backend' -PidFile (Join-Path $runDir 'backend.pid')
Show-ManagedProcess -Name 'Frontend' -PidFile (Join-Path $runDir 'frontend.pid')

Write-Host ''
Write-Host 'App URL: http://127.0.0.1:9000'
Write-Host 'Vite URL: http://localhost:5173'
