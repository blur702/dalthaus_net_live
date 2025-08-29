@echo off
echo ==========================================
echo MySQL Installation - Run as Administrator
echo ==========================================
echo.
echo Please ensure you're running this as Administrator.
echo Press Ctrl+C to cancel, or
pause

cd /d "C:\Users\kevin\PhpstormProjects\dalthaus_net_live"
powershell -ExecutionPolicy Bypass -File ".\install-mysql.ps1"
pause