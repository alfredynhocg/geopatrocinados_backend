param(
    [Parameter(Mandatory = $true)][string]$Port,
    [Parameter(Mandatory = $true)][int]$BaudRate,
    [Parameter(Mandatory = $true)][string]$FilePath
)

$bytes = [System.IO.File]::ReadAllBytes($FilePath)
$sp = New-Object System.IO.Ports.SerialPort $Port, $BaudRate, 'None', 8, 'One'
$sp.Open()
$sp.Write($bytes, 0, $bytes.Length)
Start-Sleep -Milliseconds 300
$sp.Close()
