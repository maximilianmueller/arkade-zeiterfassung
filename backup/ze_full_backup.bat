@echo off
call ze_backup_paths.bat
set fileName=ze_full_backup_%date:~6,4%_%date:~3,2%_%date:~0,2%.sql 
%mySQLBinPath%\mysqldump -u wolfgang -palanish5 --database zeiterfassung > %localPath%\full_backup\%fileName%
xcopy %localPath%\full_backup\%fileName% %backupPath%\full_backup /y /c /q





