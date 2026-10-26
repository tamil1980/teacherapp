$process = Start-Process -FilePath "D:\XAMP\php\php.exe" -ArgumentList "artisan serve --host=127.0.0.1 --port=8000" -WorkingDirectory "D:\Teacherapp" -PassThru
Start-Sleep -Seconds 3
Invoke-WebRequest -Uri "http://127.0.0.1:8000" -UseBasicParsing