@echo off
call ze_backup_paths.bat
xcopy %localPath%\binary_logs\* %backupPath%\binary_logs /d /y /c /q






