$ws = New-Object -ComObject WScript.Shell
$desktop = [Environment]::GetFolderPath("Desktop")
$s = $ws.CreateShortcut("$desktop\SEO Ассистент.lnk")
$s.TargetPath = "powershell.exe"
$s.Arguments = '-ExecutionPolicy Bypass -WindowStyle Hidden -File "C:\Users\KOS\seoassis\launch.ps1"'
$s.WorkingDirectory = "C:\Users\KOS\seoassis"
$s.WindowStyle = 1
$s.IconLocation = "shell32.dll,14"
$s.Save()
Write-Host "Ярлык создан на рабочем столе."
pause
