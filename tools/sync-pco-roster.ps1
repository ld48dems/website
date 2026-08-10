$ErrorActionPreference = 'Stop'

$source = 'https://ld48dems.org/wp/pcos/'
$destination = Join-Path $PSScriptRoot '..\assets\pco-roster.json'
$html = (Invoke-WebRequest -UseBasicParsing "${source}?migration=20260731").Content
Add-Type -AssemblyName System.Web

$records = foreach ($row in [regex]::Matches($html, '(?is)<tr[^>]*>(.*?)</tr>')) {
    $cells = @([regex]::Matches($row.Groups[1].Value, '(?is)<td[^>]*>(.*?)</td>') | ForEach-Object {
        [System.Web.HttpUtility]::HtmlDecode(
            ([regex]::Replace($_.Groups[1].Value, '<[^>]+>', ' ') -replace '\s+', ' ').Trim()
        )
    })

    if ($cells.Count -lt 3 -or $cells[0] -notmatch '^(BEAR|BEL|BRIDLE|C-H|EVANS|HPT|KIR|MARY|MED|RED|SILVER|YPT)') {
        continue
    }

    $email = [regex]::Match($row.Groups[1].Value, '(?is)mailto:([^''" >]+)').Groups[1].Value
    [ordered]@{
        precinct = $cells[0]
        name = if ($cells[1] -match '^vacant$') { 'VACANT' } else { $cells[1] }
        email = [System.Web.HttpUtility]::HtmlDecode($email)
    }
}

if ($records.Count -ne 165) {
    throw "Expected 165 roster records, found $($records.Count)."
}

$payload = [ordered]@{
    updated = '2026-07-31'
    source = $source
    records = $records
}

$payload | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $destination -Encoding utf8
Write-Output "Saved $($records.Count) records to $destination"