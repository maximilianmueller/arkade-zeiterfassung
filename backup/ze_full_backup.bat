@echo off
set fileName=ze_full_backup_%date:~6,4%_%date:~3,2%_%date:~0,2%.sql 
c:\xampp\mysql\bin\mysqldump -u wolfgang -palanish5 --database zeiterfassung > c:\xampp\htdocs\arkade-zeiterfassung\backup\full_backup\%fileName%
xcopy c:\xampp\htdocs\arkade-zeiterfassung\backup\full_backup\%fileName% g:\zeiterfassung\backup\full_backup





