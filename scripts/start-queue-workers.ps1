param(
    [int]$Workers = 4,
    [int]$MaxTime = 3600
)

$ErrorActionPreference = 'Stop'

$php = Join-Path $env:ProgramFilesX86 'php\php.exe'
if (-not (Test-Path $php)) {
    $php = (Get-Command php.exe -ErrorAction SilentlyContinue).Source
}

if (-not $php) {
    Write-Host 'php.exe not found. Set your PHP path in $php.' -ForegroundColor Red
    exit 1
}

$base = Split-Path -Parent $PSScriptRoot

Write-Host "Starting $Workers queue worker(s) with php: $php" -ForegroundColor Green

for ($i = 1; $i -le $Workers; $i++) {
    $proc = Start-Process -FilePath $php -ArgumentList @(
        'artisan',
        'queue:work',
        'database',
        "--max-time=$MaxTime",
        '--tries=3',
        '--backoff=10'
    ) -WorkingDirectory $base -WindowStyle Hidden -PassThru

    Write-Host "  Worker #$i started (PID $($proc.Id))" -ForegroundColor Cyan
}

Write-Host 'Done. To run on boot, create a Task Scheduler task that runs this script.' -ForegroundColor Yellow
