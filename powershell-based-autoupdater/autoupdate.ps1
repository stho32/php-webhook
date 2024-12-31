#!/snap/bin/pwsh
Set-Location $PSScriptRoot
$ErrorActionPreference = "Stop"

# Force TLS 1.2
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

# Ensure config file is available
$config = New-Object -TypeName PSObject -Property @{
    RepositoryInformationUrl = ""
    WaitTimeInSeconds = 30
    GithubUsername = ""
    GithubRepository = ""
    ExecuteOnChange = ""
    ApiKey = ""
    AllowedCommands = @()
    LogFile = "autoupdate.log"
}
$configFilename = "config.json"

# Helper function for logging
function Write-Log {
    param($Message)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] $Message"
    Write-Host $logMessage
    Add-Content -Path $config.LogFile -Value $logMessage
}

if (-not (Test-Path $configFilename)) {
    $config | ConvertTo-Json | Out-File $configFilename
    Write-Log "Created new config file. Please complete the configuration in $configFilename"
    exit
} else {
    try {
        $config = Get-Content $configFilename | ConvertFrom-Json
    } catch {
        Write-Log "Error reading config file: $_"
        exit 1
    }
}

# Validate configuration
$requiredFields = @('GithubUsername', 'GithubRepository', 'RepositoryInformationUrl', 'ApiKey')
foreach ($field in $requiredFields) {
    if (-not ($config.$field)) {
        Write-Log "$field is not configured yet. Please update the configuration."
        exit 1
    }
}

# Start the process
Write-Log "Auto-Updater started"
Write-Log "Monitoring $($config.GithubUsername)/$($config.GithubRepository)"

$previousStateFilename = ($config.GithubUsername + "_" + $config.GithubRepository) + ".state"

while ($true) {
    try {
        Start-Sleep -Seconds $config.WaitTimeInSeconds
        Write-Log "Checking for changes..."
        
        # Prepare web request with API key
        $headers = @{
            'X-API-Key' = $config.ApiKey
        }
        
        $infoUrl = "$($config.RepositoryInformationUrl)?user=$($config.GithubUsername)&repository=$($config.GithubRepository)"
        
        try {
            $response = Invoke-WebRequest -Uri $infoUrl -Headers $headers -UseBasicParsing
            $lastPushInfoFromTheWeb = $response.Content
        } catch {
            Write-Log "Error fetching update information: $_"
            continue
        }

        $previousState = ""
        if (Test-Path $previousStateFilename) {
            $previousState = Get-Content $previousStateFilename
        }

        if ($lastPushInfoFromTheWeb -ne $previousState) {
            Write-Log "Change detected!"
            $lastPushInfoFromTheWeb | Out-File $previousStateFilename

            if ($config.ExecuteOnChange) {
                Write-Log "Executing configured command..."
                try {
                    # Validate command against allowed list
                    $commandName = ($config.ExecuteOnChange -split ' ')[0]
                    if ($config.AllowedCommands -contains $commandName) {
                        # Execute in a new scope to prevent variable pollution
                        & {
                            Invoke-Expression $config.ExecuteOnChange
                        }
                        Write-Log "Command executed successfully"
                    } else {
                        Write-Log "Command '$commandName' is not in the allowed commands list"
                    }
                } catch {
                    Write-Log "Error executing command: $_"
                }
            }
        }
    } catch {
        Write-Log "Error in main loop: $_"
        Start-Sleep -Seconds 10  # Prevent rapid retries on persistent errors
    }
}
