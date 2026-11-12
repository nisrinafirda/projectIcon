$path = 'd:\\projectIcon\\projectIcon\\storage\\app\\imports\\Project Monitoring PPB 2026(6 Verification Contract) (1).csv'
$header = Get-Content -Path $path -Encoding utf8 -TotalCount 1
$cols = $header -split ','
for ($i = 0; $i -lt $cols.Length; $i++) {
    Write-Output "[$i] $($cols[$i])"
}
