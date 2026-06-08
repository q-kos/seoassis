$port = 8080
$dir  = $PSScriptRoot
$url  = "http://localhost:$port"

$listening = netstat -ano | Select-String ":$port\s.*LISTENING"
if (-not $listening) {
    Start-Process "php" "-S localhost:$port -t `"$dir`"" -WorkingDirectory $dir -WindowStyle Hidden
    Start-Sleep -Seconds 2
}

Start-Process $url
