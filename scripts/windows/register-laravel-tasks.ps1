param(
    [Parameter(Mandatory = $true)]
    [string]$AppPath,

    [Parameter(Mandatory = $true)]
    [string]$PhpPath
)

$ErrorActionPreference = 'Stop'

if (!(Test-Path $AppPath)) {
    throw "AppPath tidak ditemukan: $AppPath"
}

if (!(Test-Path $PhpPath)) {
    throw "PhpPath tidak ditemukan: $PhpPath"
}

$schedulerTaskName = 'ERP Laravel Scheduler'
$workerTaskName = 'ERP Laravel Queue Worker'

$schedulerCommand = "`"$PhpPath`" `"$AppPath\artisan`" schedule:run"
$workerCommand = "`"$PhpPath`" `"$AppPath\artisan`" queue:work --tries=3 --timeout=120 --sleep=3"

schtasks /Create /F /TN $schedulerTaskName /SC MINUTE /MO 1 /TR $schedulerCommand
schtasks /Create /F /TN $workerTaskName /SC ONSTART /TR $workerCommand

Write-Output "Task Scheduler berhasil dibuat:"
Write-Output "- $schedulerTaskName"
Write-Output "- $workerTaskName"
