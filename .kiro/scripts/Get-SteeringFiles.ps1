# PowerShell script to determine which steering files should be loaded for a given task
# Usage: .\Get-SteeringFiles.ps1 -TaskInput "authentication"

param(
    [Parameter(Mandatory=$true)]
    [string]$TaskInput,
    
    [Parameter(Mandatory=$false)]
    [switch]$ListOnly,
    
    [Parameter(Mandatory=$false)]
    [switch]$ShowAll
)

# Get the script directory and construct paths
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$KiroDir = Split-Path -Parent $ScriptDir
$MappingPath = Join-Path $KiroDir "system\task-steering-mapping.json"
$SteeringDir = Join-Path $KiroDir "steering"

# Check if mapping file exists
if (-not (Test-Path $MappingPath)) {
    Write-Error "Task-steering mapping file not found: $MappingPath"
    exit 1
}

# Load the task-steering mapping
try {
    $Mapping = Get-Content $MappingPath -Raw | ConvertFrom-Json
} catch {
    Write-Error "Failed to parse mapping file: $_"
    exit 1
}

function Get-SteeringFiles {
    param([string]$Input)
    
    $Result = @{
        Common = $Mapping.common_files
        Specific = @()
        Categories = @()
        Description = ""
    }

    # Check if input is a spec name
    if ($Mapping.spec_mappings.PSObject.Properties.Name -contains $Input) {
        $Categories = $Mapping.spec_mappings.$Input
        $Result.Categories = $Categories
        
        # Collect all steering files for these categories
        $SteeringFiles = @()
        foreach ($Category in $Categories) {
            if ($Mapping.task_categories.PSObject.Properties.Name -contains $Category) {
                $SteeringFiles += $Mapping.task_categories.$Category.steering_files
            }
        }
        $Result.Specific = $SteeringFiles | Sort-Object -Unique
        $Result.Description = "Multi-category spec: $($Categories -join ', ')"
    }
    # Check if input is a task category
    elseif ($Mapping.task_categories.PSObject.Properties.Name -contains $Input) {
        $Result.Categories = @($Input)
        $Result.Specific = $Mapping.task_categories.$Input.steering_files
        $Result.Description = $Mapping.task_categories.$Input.description
    }
    # Try to find partial matches
    else {
        $Matches = $Mapping.task_categories.PSObject.Properties.Name | Where-Object { 
            $_ -like "*$Input*" -or $Input -like "*$_*" 
        }
        
        if ($Matches.Count -gt 0) {
            $Result.Categories = $Matches
            $SteeringFiles = @()
            foreach ($Category in $Matches) {
                $SteeringFiles += $Mapping.task_categories.$Category.steering_files
            }
            $Result.Specific = $SteeringFiles | Sort-Object -Unique
            $Result.Description = "Partial match for: $($Matches -join ', ')"
        }
    }

    return $Result
}

function Show-Results {
    param($Input, $Result)
    
    Write-Host ""
    Write-Host "Target Steering Files for: $Input" -ForegroundColor Cyan
    Write-Host "=================================================" -ForegroundColor Gray
    
    if ($Result.Categories.Count -eq 0) {
        Write-Host "No matching categories found" -ForegroundColor Red
        Write-Host ""
        Write-Host "Available categories:" -ForegroundColor Yellow
        foreach ($Category in $Mapping.task_categories.PSObject.Properties.Name) {
            $Desc = $Mapping.task_categories.$Category.description
            Write-Host "  - $Category : $Desc" -ForegroundColor White
        }
        Write-Host ""
        Write-Host "Available specs:" -ForegroundColor Yellow
        foreach ($Spec in $Mapping.spec_mappings.PSObject.Properties.Name) {
            Write-Host "  - $Spec" -ForegroundColor White
        }
        return
    }

    Write-Host ""
    Write-Host "Categories: $($Result.Categories -join ', ')" -ForegroundColor Green
    if ($Result.Description) {
        Write-Host "Description: $($Result.Description)" -ForegroundColor Gray
    }
    
    Write-Host ""
    Write-Host "Common Files (always load):" -ForegroundColor Yellow
    foreach ($File in $Result.Common) {
        $FilePath = Join-Path $SteeringDir $File
        $Exists = Test-Path $FilePath
        $Status = if ($Exists) { "OK" } else { "MISSING" }
        Write-Host "  [$Status] $File" -ForegroundColor $(if ($Exists) { "Green" } else { "Red" })
    }
    
    Write-Host ""
    Write-Host "Task-Specific Files:" -ForegroundColor Yellow
    foreach ($File in $Result.Specific) {
        $FilePath = Join-Path $SteeringDir $File
        $Exists = Test-Path $FilePath
        $Status = if ($Exists) { "OK" } else { "MISSING" }
        Write-Host "  [$Status] $File" -ForegroundColor $(if ($Exists) { "Green" } else { "Red" })
    }
    
    $TotalFiles = $Result.Common.Count + $Result.Specific.Count
    $AllSteeringFiles = 0
    foreach ($Category in $Mapping.task_categories.PSObject.Properties.Name) {
        $AllSteeringFiles += $Mapping.task_categories.$Category.steering_files.Count
    }
    $SkippedFiles = $AllSteeringFiles - $Result.Specific.Count
    
    Write-Host ""
    Write-Host "Summary:" -ForegroundColor Cyan
    Write-Host "  Total files to load: $TotalFiles" -ForegroundColor White
    Write-Host "  Files skipped: $SkippedFiles" -ForegroundColor White
    $EfficiencyGain = [math]::Round((1 - $TotalFiles / ($AllSteeringFiles + $Result.Common.Count)) * 100, 1)
    Write-Host "  Efficiency gain: $EfficiencyGain%" -ForegroundColor Green
}

function Show-AllCategories {
    Write-Host ""
    Write-Host "All Available Task Categories:" -ForegroundColor Cyan
    Write-Host "============================================================" -ForegroundColor Gray
    
    foreach ($Category in $Mapping.task_categories.PSObject.Properties.Name | Sort-Object) {
        $CategoryData = $Mapping.task_categories.$Category
        Write-Host ""
        Write-Host "Category: $Category" -ForegroundColor Yellow
        Write-Host "   Description: $($CategoryData.description)" -ForegroundColor Gray
        Write-Host "   Files ($($CategoryData.steering_files.Count)):" -ForegroundColor Gray
        foreach ($File in $CategoryData.steering_files) {
            Write-Host "     - $File" -ForegroundColor White
        }
    }
    
    Write-Host ""
    Write-Host "Spec Mappings:" -ForegroundColor Cyan
    Write-Host "==============================" -ForegroundColor Gray
    foreach ($Spec in $Mapping.spec_mappings.PSObject.Properties.Name | Sort-Object) {
        $Categories = $Mapping.spec_mappings.$Spec -join ", "
        Write-Host "  $Spec -> $Categories" -ForegroundColor White
    }
}

# Main execution
if ($ShowAll) {
    Show-AllCategories
    exit 0
}

if ($ListOnly) {
    $Result = Get-SteeringFiles -Input $TaskInput
    if ($Result.Categories.Count -gt 0) {
        $AllFiles = $Result.Common + $Result.Specific | Sort-Object -Unique
        foreach ($File in $AllFiles) {
            Write-Output $File
        }
    }
    exit 0
}

$Result = Get-SteeringFiles -Input $TaskInput
Show-Results -Input $TaskInput -Result $Result

# Return exit code based on success
if ($Result.Categories.Count -eq 0) {
    exit 1
} else {
    exit 0
}