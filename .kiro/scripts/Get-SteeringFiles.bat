@echo off
REM Batch wrapper for PowerShell steering files script
REM Usage: Get-SteeringFiles.bat <task-category-or-spec-name> [--list] [--all]

setlocal enabledelayedexpansion

REM Get the directory where this batch file is located
set "SCRIPT_DIR=%~dp0"
set "PS_SCRIPT=%SCRIPT_DIR%Get-SteeringFiles.ps1"

REM Check if PowerShell script exists
if not exist "%PS_SCRIPT%" (
    echo Error: PowerShell script not found: %PS_SCRIPT%
    exit /b 1
)

REM Parse arguments
set "TASK_INPUT=%1"
set "EXTRA_ARGS="

:parse_args
if "%2"=="" goto :run_script
if "%2"=="--list" set "EXTRA_ARGS=%EXTRA_ARGS% -ListOnly"
if "%2"=="--all" set "EXTRA_ARGS=%EXTRA_ARGS% -ShowAll"
shift
goto :parse_args

:run_script
REM Check if task input is provided (unless --all is specified)
if "%TASK_INPUT%"=="" (
    if "%EXTRA_ARGS%"=="-ShowAll" goto :execute
    echo Usage: %~nx0 ^<task-category-or-spec-name^> [--list] [--all]
    echo.
    echo Examples:
    echo   %~nx0 authentication
    echo   %~nx0 filament
    echo   %~nx0 authentication-testing
    echo   %~nx0 universal-utility-management
    echo   %~nx0 --all
    echo.
    exit /b 1
)

:execute
REM Execute PowerShell script with appropriate execution policy
if "%TASK_INPUT%"=="" (
    powershell.exe -ExecutionPolicy Bypass -File "%PS_SCRIPT%" %EXTRA_ARGS%
) else (
    powershell.exe -ExecutionPolicy Bypass -File "%PS_SCRIPT%" -TaskInput "%TASK_INPUT%" %EXTRA_ARGS%
)

REM Pass through the exit code
exit /b %ERRORLEVEL%