@echo off
rem Levanta la web de Santa Fe Propiedades en esta PC: base de datos + servidor PHP.
rem Web: http://localhost:8080   Panel: http://localhost:8080/panel
rem Para apagar: cerrar las dos ventanas que se abren.
title SFP Web local
cd /d "%~dp0"

tasklist /fi "imagename eq mariadbd.exe" | find /i "mariadbd.exe" >nul
if errorlevel 1 (
  start "SFP - Base de datos" /min "G:\Herramientas\mariadb-11.4\bin\mariadbd.exe" --defaults-file="G:\Herramientas\mariadb-data\my.ini" --console
  timeout /t 3 /nobreak >nul
)

start "SFP - Servidor web" /min "G:\Herramientas\php-8.4\php.exe" -S localhost:8080 -t public public\index.php
timeout /t 2 /nobreak >nul
start "" http://localhost:8080
