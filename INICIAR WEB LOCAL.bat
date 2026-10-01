@echo off
rem Levanta la web de Santa Fe Propiedades en esta PC: base de datos + servidor PHP.
rem Web: http://localhost:8080   Panel: http://localhost:8080/panel
rem Para apagar: cerrar las dos ventanas que se abren.
title SFP Web local
cd /d "%~dp0"
set PHP="G:\Herramientas\php-8.4\php.exe"

tasklist /fi "imagename eq mariadbd.exe" | findstr /i "mariadbd.exe" >nul
if errorlevel 1 (
  start "SFP - Base de datos" /min "G:\Herramientas\mariadb-11.4\bin\mariadbd.exe" --defaults-file="G:\Herramientas\mariadb-data\my.ini" --console
)

rem Espera hasta 20 segundos a que la base acepte conexiones (si se apago mal, tarda mas en arrancar).
%PHP% -r "for ($i = 0; $i < 80; $i++) { if (@fsockopen('127.0.0.1', 3306)) { exit(0); } usleep(250000); } exit(1);"
if errorlevel 1 (
  echo La base de datos no arranco. Revisa la ventana "SFP - Base de datos".
  pause
  exit /b 1
)

rem Si la web ya estaba levantada, no se abre otro servidor.
netstat -ano | findstr /c:":8080 " | findstr "LISTENING" >nul
if errorlevel 1 (
  start "SFP - Servidor web" /min %PHP% -S localhost:8080 -t public public\index.php
  timeout /t 2 /nobreak >nul
)
start "" http://localhost:8080
