Param(
    [string]$MysqlPath = "C:\xampp\mysql\bin\mysql.exe",
    [string]$DbName = "blog_ci",
    [string]$SchemaPath = "$PSScriptRoot\..\sql\schema_seed.sql",
    [string]$User = "root"
)

$ErrorActionPreference = "Stop"

if (!(Test-Path $MysqlPath)) {
    Write-Error "MySQL client not found at $MysqlPath"
    exit 1
}

if (!(Test-Path $SchemaPath)) {
    Write-Error "Schema file not found at $SchemaPath"
    exit 1
}

Write-Host "Using MySQL client: $MysqlPath"
Write-Host "Creating database if not exists: $DbName"

# Create database with utf8mb4
& $MysqlPath -u $User -e "CREATE DATABASE IF NOT EXISTS $DbName CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# Import schema/seed using cmd redirection to avoid PowerShell quoting issues
Write-Host "Importing schema: $SchemaPath"
$importCmd = "`"$MysqlPath`" -u $User $DbName < `"$SchemaPath`""
cmd.exe /c $importCmd

# Verify tables
Write-Host "Verifying tables in $DbName ..."
& $MysqlPath -u $User -D $DbName -e "SHOW TABLES;"
