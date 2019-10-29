<#
This script takes the large filename.txt file sent by Client each morning.
Instead of cutlocs, we use a csv to split it into smaller files by acct ID.
These files can then be parsed concurrently by our system for speed.
#>


#File Parameters
$vendor_file = '\\place\sshroot\tst016\filename.txt'

#Read csv of locs/accts
$acctsHeader = "EpicLocID","Acct"
$acct = Import-Csv -Path "\\place\sshroot\tst016\accts.csv" -header $acctsHeader

#Read Appts File
$fileHeaders = "A","CSN","F","Date","Time","Duration","Proc","1","2","3","4","5","6","Loc","Doc","ZNum","7","Last","First","Primary","Lang","Cell","Birth","Record","Pref","8","9"
$file = Import-Csv -Path $vendor_file -header $fileHeaders


#Append records to the prerequesite files
ForEach($files in $file){
    ForEach($accts in $acct){
        #Write-Host $files
        #Write-Host $acct_check.EpicLocID
        if($files.Loc -eq $accts.EpicLocID){
            $record = $files | Convertto-Csv -NoType | Select-Object -Skip 1 | %{$_-replace '"',""}
            $addedFile = '\\place\sshroot\tst016\' + $accts.Acct + '_filename.txt'
            Add-Content $addedFile $record
         }
   }
}

#Write-Host -NoNewLine 'Press any key to continue...';
#$null = $Host.UI.RawUI.ReadKey('NoEcho,IncludeKeyDown');
