# MySQL 8.0 Installation Script for Windows
# Run this script as Administrator

$ErrorActionPreference = "Stop"

Write-Host "MySQL 8.0 Installation Script" -ForegroundColor Green
Write-Host "==============================" -ForegroundColor Green

# Configuration
$mysqlVersion = "8.0.40"
$mysqlPath = "C:\mysql"
$mysqlDataPath = "C:\mysql\data"
$downloadUrl = "https://dev.mysql.com/get/Downloads/MySQL-8.0/mysql-8.0.40-winx64.zip"
$zipFile = "$env:TEMP\mysql-8.0.40-winx64.zip"

# Check if running as Administrator
if (-NOT ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Host "This script requires Administrator privileges." -ForegroundColor Red
    Write-Host "Please run PowerShell as Administrator and try again." -ForegroundColor Yellow
    exit 1
}

# Step 1: Check if MySQL is already installed
Write-Host "`nStep 1: Checking for existing MySQL installation..." -ForegroundColor Cyan
$existingService = Get-Service -Name "MySQL*" -ErrorAction SilentlyContinue
if ($existingService) {
    Write-Host "MySQL service already exists: $($existingService.Name)" -ForegroundColor Yellow
    $response = Read-Host "Do you want to continue anyway? (y/n)"
    if ($response -ne 'y') {
        Write-Host "Installation cancelled." -ForegroundColor Yellow
        exit 0
    }
}

# Step 2: Download MySQL
Write-Host "`nStep 2: Downloading MySQL 8.0..." -ForegroundColor Cyan
Write-Host "This may take several minutes (400+ MB)..." -ForegroundColor Yellow
try {
    if (Test-Path $zipFile) {
        Write-Host "Using existing download at $zipFile" -ForegroundColor Yellow
    } else {
        $ProgressPreference = 'SilentlyContinue'
        Invoke-WebRequest -Uri $downloadUrl -OutFile $zipFile
        $ProgressPreference = 'Continue'
        Write-Host "Download complete!" -ForegroundColor Green
    }
} catch {
    Write-Host "Failed to download MySQL. Please check your internet connection." -ForegroundColor Red
    Write-Host "Error: $_" -ForegroundColor Red
    exit 1
}

# Step 3: Create MySQL directory
Write-Host "`nStep 3: Creating MySQL directory..." -ForegroundColor Cyan
if (Test-Path $mysqlPath) {
    Write-Host "MySQL directory already exists at $mysqlPath" -ForegroundColor Yellow
    $response = Read-Host "Do you want to overwrite it? (y/n)"
    if ($response -ne 'y') {
        Write-Host "Installation cancelled." -ForegroundColor Yellow
        exit 0
    }
    
    # Stop MySQL service if running
    $service = Get-Service -Name "MySQL" -ErrorAction SilentlyContinue
    if ($service -and $service.Status -eq 'Running') {
        Write-Host "Stopping existing MySQL service..." -ForegroundColor Yellow
        Stop-Service -Name "MySQL" -Force
        Start-Sleep -Seconds 2
    }
    
    Remove-Item -Path $mysqlPath -Recurse -Force
}

# Step 4: Extract MySQL
Write-Host "`nStep 4: Extracting MySQL files..." -ForegroundColor Cyan
Write-Host "This may take a few minutes..." -ForegroundColor Yellow
try {
    # Create temporary extraction directory
    $tempExtract = "$env:TEMP\mysql-extract"
    if (Test-Path $tempExtract) {
        Remove-Item -Path $tempExtract -Recurse -Force
    }
    
    # Extract the archive
    Expand-Archive -Path $zipFile -DestinationPath $tempExtract -Force
    
    # Find the extracted folder (it has version in name)
    $extractedFolder = Get-ChildItem -Path $tempExtract -Directory | Select-Object -First 1
    
    # Move to final location
    Move-Item -Path $extractedFolder.FullName -Destination $mysqlPath -Force
    
    # Cleanup temp directory
    Remove-Item -Path $tempExtract -Recurse -Force -ErrorAction SilentlyContinue
    
    Write-Host "Extraction complete!" -ForegroundColor Green
} catch {
    Write-Host "Failed to extract MySQL files." -ForegroundColor Red
    Write-Host "Error: $_" -ForegroundColor Red
    exit 1
}

# Step 5: Create my.ini configuration file
Write-Host "`nStep 5: Creating MySQL configuration..." -ForegroundColor Cyan
$myIniContent = @"
[mysqld]
# Set basedir to your installation path
basedir=$mysqlPath
# Set datadir to the location of your data directory
datadir=$mysqlDataPath
# Port number
port=3306
# Maximum allowed packet size
max_allowed_packet=64M
# Default authentication plugin (compatible with PHP)
default_authentication_plugin=mysql_native_password
# Character set
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci
# InnoDB settings
innodb_buffer_pool_size=128M
innodb_log_file_size=48M
# Skip name resolve for faster connections
skip-name-resolve
# Allow connections from any host (for development)
bind-address=0.0.0.0

[mysql]
default-character-set=utf8mb4

[client]
port=3306
default-character-set=utf8mb4
"@

$myIniPath = "$mysqlPath\my.ini"
Set-Content -Path $myIniPath -Value $myIniContent
Write-Host "Configuration file created at $myIniPath" -ForegroundColor Green

# Step 6: Initialize MySQL data directory
Write-Host "`nStep 6: Initializing MySQL data directory..." -ForegroundColor Cyan
Write-Host "This will create a temporary root password..." -ForegroundColor Yellow

$initLogFile = "$mysqlPath\data_init.log"
$initCommand = "$mysqlPath\bin\mysqld.exe --initialize --console 2>&1"

# Initialize and capture output
$initOutput = cmd /c $initCommand

# Save output to log file
Set-Content -Path $initLogFile -Value $initOutput

# Extract temporary password from output
$tempPassword = $initOutput | Select-String "temporary password is generated for root@localhost: (.+)$" | ForEach-Object { $_.Matches[0].Groups[1].Value }

if ($tempPassword) {
    Write-Host "MySQL initialized successfully!" -ForegroundColor Green
    Write-Host "`nTEMPORARY ROOT PASSWORD: $tempPassword" -ForegroundColor Yellow
    Write-Host "Save this password! You'll need it for first login." -ForegroundColor Yellow
    
    # Save password to file for reference
    $passFile = "$mysqlPath\initial_password.txt"
    Set-Content -Path $passFile -Value "Initial root password: $tempPassword"
    Write-Host "Password also saved to: $passFile" -ForegroundColor Cyan
} else {
    Write-Host "MySQL initialized but couldn't extract password." -ForegroundColor Yellow
    Write-Host "Check the log file at: $initLogFile" -ForegroundColor Yellow
}

# Step 7: Install MySQL as Windows Service
Write-Host "`nStep 7: Installing MySQL as Windows Service..." -ForegroundColor Cyan
try {
    $installService = "$mysqlPath\bin\mysqld.exe --install MySQL --defaults-file=`"$myIniPath`""
    $result = cmd /c $installService 2>&1
    Write-Host $result
    Write-Host "MySQL service installed successfully!" -ForegroundColor Green
} catch {
    Write-Host "Failed to install MySQL service." -ForegroundColor Red
    Write-Host "Error: $_" -ForegroundColor Red
}

# Step 8: Start MySQL Service
Write-Host "`nStep 8: Starting MySQL service..." -ForegroundColor Cyan
try {
    Start-Service -Name "MySQL"
    Start-Sleep -Seconds 3
    $service = Get-Service -Name "MySQL"
    if ($service.Status -eq 'Running') {
        Write-Host "MySQL service is running!" -ForegroundColor Green
    } else {
        Write-Host "MySQL service failed to start. Status: $($service.Status)" -ForegroundColor Red
    }
} catch {
    Write-Host "Failed to start MySQL service." -ForegroundColor Red
    Write-Host "Error: $_" -ForegroundColor Red
}

# Step 9: Add MySQL to PATH
Write-Host "`nStep 9: Adding MySQL to system PATH..." -ForegroundColor Cyan
$mysqlBinPath = "$mysqlPath\bin"
$currentPath = [Environment]::GetEnvironmentVariable("Path", [EnvironmentVariableTarget]::Machine)

if ($currentPath -notlike "*$mysqlBinPath*") {
    $newPath = "$currentPath;$mysqlBinPath"
    [Environment]::SetEnvironmentVariable("Path", $newPath, [EnvironmentVariableTarget]::Machine)
    Write-Host "MySQL added to system PATH" -ForegroundColor Green
    
    # Update current session PATH
    $env:Path = "$env:Path;$mysqlBinPath"
} else {
    Write-Host "MySQL is already in PATH" -ForegroundColor Yellow
}

# Step 10: Create setup script for database
Write-Host "`nStep 10: Creating database setup script..." -ForegroundColor Cyan
$setupSqlFile = "$mysqlPath\setup_database.sql"
$setupSqlContent = @"
-- Change root password (run this after logging in with temporary password)
-- ALTER USER 'root'@'localhost' IDENTIFIED BY 'your_new_password';

-- Create database for the CMS
CREATE DATABASE IF NOT EXISTS dalthaus_cms;

-- Create a user for the application (optional, more secure than using root)
-- CREATE USER 'dalthaus_user'@'localhost' IDENTIFIED BY 'password123';
-- GRANT ALL PRIVILEGES ON dalthaus_cms.* TO 'dalthaus_user'@'localhost';
-- FLUSH PRIVILEGES;

-- Show databases to confirm
SHOW DATABASES;
"@

Set-Content -Path $setupSqlFile -Value $setupSqlContent
Write-Host "Database setup script created at: $setupSqlFile" -ForegroundColor Green

# Cleanup
Write-Host "`nCleaning up..." -ForegroundColor Cyan
Remove-Item $zipFile -Force -ErrorAction SilentlyContinue

# Final instructions
Write-Host "`n========================================" -ForegroundColor Green
Write-Host "MySQL Installation Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "`nIMPORTANT NEXT STEPS:" -ForegroundColor Yellow
Write-Host "1. The temporary root password is: $tempPassword" -ForegroundColor White
Write-Host "   (Also saved in $mysqlPath\initial_password.txt)" -ForegroundColor White
Write-Host "`n2. Change the root password:" -ForegroundColor White
Write-Host "   mysql -u root -p" -ForegroundColor Cyan
Write-Host "   (Enter temporary password when prompted)" -ForegroundColor White
Write-Host "   ALTER USER 'root'@'localhost' IDENTIFIED BY 'your_new_password';" -ForegroundColor Cyan
Write-Host "`n3. Create the database for your CMS:" -ForegroundColor White
Write-Host "   CREATE DATABASE dalthaus_cms;" -ForegroundColor Cyan
Write-Host "`n4. Update your PHP application config:" -ForegroundColor White
Write-Host "   DB_HOST = '127.0.0.1'" -ForegroundColor Cyan
Write-Host "   DB_NAME = 'dalthaus_cms'" -ForegroundColor Cyan
Write-Host "   DB_USER = 'root'" -ForegroundColor Cyan
Write-Host "   DB_PASS = 'your_new_password'" -ForegroundColor Cyan
Write-Host "`n5. Test the connection:" -ForegroundColor White
Write-Host "   mysql -u root -p -e 'SHOW DATABASES;'" -ForegroundColor Cyan
Write-Host "`nMySQL is installed at: $mysqlPath" -ForegroundColor Green
Write-Host "MySQL service name: MySQL" -ForegroundColor Green
Write-Host "`nTo manage the service:" -ForegroundColor Yellow
Write-Host "  Start:   net start MySQL" -ForegroundColor White
Write-Host "  Stop:    net stop MySQL" -ForegroundColor White
Write-Host "  Status:  sc query MySQL" -ForegroundColor White