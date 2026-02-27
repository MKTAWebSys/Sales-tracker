param(
    [int]$BackendPort = 9000,
    [string]$BackendHost = '127.0.0.1',
    [string]$FrontendCommand = 'run dev'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

$runDir = Join-Path $projectRoot '.run'
if (-not (Test-Path $runDir)) {
    New-Item -ItemType Directory -Path $runDir | Out-Null
}

function Get-RunningProcessFromPidFile {
    param([string]$PidFile)

    if (-not (Test-Path $PidFile)) { return $null }

    $rawPid = Get-Content $PidFile -ErrorAction SilentlyContinue | Select-Object -First 1
    if ([string]::IsNullOrWhiteSpace($rawPid)) { return $null }

    $procId = 0
    if (-not [int]::TryParse($rawPid.Trim(), [ref]$procId)) { return $null }

    try {
        return Get-Process -Id $procId -ErrorAction Stop
    } catch {
        return $null
    }
}

function Start-ManagedProcess {
    param(
        [string]$Name,
        [string]$FilePath,
        [string[]]$Arguments,
        [string]$PidFile,
        [string]$StdOutFile,
        [string]$StdErrFile
    )

    $existing = Get-RunningProcessFromPidFile -PidFile $PidFile
    if ($existing) {
        Write-Host "$Name already running (PID $($existing.Id))."
        return $existing
    }

    $proc = Start-Process -FilePath $FilePath -ArgumentList $Arguments -WorkingDirectory $projectRoot -RedirectStandardOutput $StdOutFile -RedirectStandardError $StdErrFile -PassThru
    Set-Content -Path $PidFile -Value $proc.Id
    Write-Host "$Name started (PID $($proc.Id))."
    return $proc
}

$backendPidFile = Join-Path $runDir 'backend.pid'
$frontendPidFile = Join-Path $runDir 'frontend.pid'

$backendOut = Join-Path $runDir 'backend.out.log'
$backendErr = Join-Path $runDir 'backend.err.log'
$frontendOut = Join-Path $runDir 'frontend.out.log'
$frontendErr = Join-Path $runDir 'frontend.err.log'

Start-ManagedProcess -Name 'Backend' -FilePath 'php' -Arguments @('-S', "$BackendHost`:$BackendPort", '-t', 'public') -PidFile $backendPidFile -StdOutFile $backendOut -StdErrFile $backendErr | Out-Null
Start-ManagedProcess -Name 'Frontend' -FilePath 'cmd.exe' -Arguments @('/c', "npm $FrontendCommand") -PidFile $frontendPidFile -StdOutFile $frontendOut -StdErrFile $frontendErr | Out-Null

Start-Sleep -Seconds 2

Write-Host ""
Write-Host "App URL: http://$BackendHost`:$BackendPort"
Write-Host "Vite URL: http://localhost:5173"
Write-Host ""
Write-Host "Use: powershell -ExecutionPolicy Bypass -File .\\scripts\\dev-status.ps1"
Write-Host "Use: powershell -ExecutionPolicy Bypass -File .\\scripts\\dev-down.ps1"
