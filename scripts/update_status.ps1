$inputPath = 'd:\projectIcon\projectIcon\storage\app\imports\Project Monitoring PPB 2026(6 Verification Contract) (1).csv'
$outputPath = "$inputPath.tmp"
# Read header line
$header = Get-Content -Path $inputPath -Encoding utf8 -TotalCount 1
$columns = $header -split ','
$statusIndex = [Array]::IndexOf($columns, 'Status')
if ($statusIndex -lt 0) {
    Write-Error "Status column not found"
    exit 1
}
# Write header to output
$header | Out-File -FilePath $outputPath -Encoding utf8
# Process remaining lines in chunks to avoid loading whole file into memory
Get-Content -Path $inputPath -Encoding utf8 -ReadCount 1000 | Select-Object -Skip 1 | ForEach-Object {
    $line = $_
    $fields = $line -split ','
    if ($fields.Count -gt $statusIndex) {
        if ([string]::IsNullOrWhiteSpace($fields[$statusIndex])) {
            $fields[$statusIndex] = 'On Process'
        }
    }
    ($fields -join ',') | Add-Content -Path $outputPath -Encoding utf8
}
# Replace original file
Move-Item -Force $outputPath $inputPath
