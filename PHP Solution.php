<?/* CSV Conversion */

require_once('Net/SFTP.php');

$testing = true;
$testing = false;
//$manualfiles = true;

$debug = false;

$callASAP = false; 
$textASAP = false; 
$emailASAP = false;
$ftpmailer = false; 

$client_id = '3966';
$output_filename = '_filename.txt';

$accts = $this->loadMappingFileXLS('accts.xlsx');
$unknownOffices = array();
$fileList = array();


//Create the list of filenames for format #####_filename.txt
$fileprefixes = array();
$output = array();
$unknownOffices = array();

foreach($accts as $acct){
	array_push($fileprefixes,$acct[1]);
}

unset($fileprefixes[0]);
$fileprefixes = array_unique($fileprefixes);

//var_dump($fileprefixes);


//$run_date = "20191011";
$run_date = date("Ymd");
$files = $this->getFiles("####-*filename*", $run_date, 1);

if($files){
	foreach($files as $file){
		$lines = explode("\n",$file);
	
		echo "We see there's files\n\n";
			
		$total_count = count($lines);
		print "This file has $total_count lines\n\n";
		
		// SPLIT BY ACCT NUM IN MAPPINGS
		foreach($lines as $Key => $line){
			
			if(strlen(trim($line))==0) { continue; }
			
			$data = array_map('trim',getCSVValues($line));
			$acct_key = '';
			
			$office_id = $data[13];
			//echo "Line 1 is {$data[1]}\n\n";
			
			//Office Mappings
			$OfficeLookup = array(); 
			$OfficeResult = array();
			
			$OfficeLookup[0] = $office_id;
			$OfficeResult = $this->searchMappingArray($accts, $OfficeLookup);

			if($OfficeResult){
				$acct_key = $OfficeResult[1];
				$output[$acct_key] .= $line . "\r\n";
				$fileListEntry = $acct_key . $output_filename;
				if(!in_array($fileListEntry,$fileList)){
					$fileList[] = $fileListEntry;
				}
			}else{
				if(!in_array($office_id,$unknownOffices)){
					$unknownOffices[] = $office_id;
				}
			}
			
			//print "For $office_id we will use account $acct_key\n\n";
			
			
		}
		//END SPLIT
		//ksort($output);

		if($testing){
			//print "-------- Test Array ----------\n\n";
			//var_dump($output);
			//print "---------End Array------------\n\n";
		}
		
		foreach($output as $acct_num => $data_file){
			$output_file = $acct_num . $output_filename;
			
			
			//$file_data_str = implode("\n",$data_file);
			
			if($testing){
				echo "FILE - $output_file \n";
				//var_dump($data_file);
			}elseif($manualfiles){
				$local_file = '/var/tmp/'.$client_id.$output_file;
				file_put_contents($local_file,$data_file);
				
				$sftp = new Net_SFTP('files.place.net');
				$login_result = $sftp->login('username1', '1234');

				if (!$login_result) {
				   echo "FTP connection has failed!";
				   echo "Please try again later and report this error to Support";
				   exit;
				}	
				$folder = "folder1";
				$sftp->chdir($folder);
				$upload = $sftp->put($output_file, $local_file, NET_SFTP_LOCAL_FILE);
				$sftp->disconnect();
				unlink($local_file);
				
			}else{
				$local_file = '/var/tmp/'.$client_id.$output_file;
				file_put_contents($local_file,$data_file);
				
				$sftp = new Net_SFTP('ssh2.otherplace.com');
				$login_result = $sftp->login('username2', '1234');

				if (!$login_result) {
				   echo "FTP connection has failed!";
				   echo "Please try again later and report this error to Support";
				   $ftpmailer = true;
				   exit;
				}	
				$folder = "folder2";
				$sftp->chdir($folder);
				$upload = $sftp->put($output_file, $local_file, NET_SFTP_LOCAL_FILE);
				$sftp->disconnect();
				unlink($local_file);
				
			}
			
		}
		
	}
	
            
}

if(!empty($unkownOffices) and !$testing){
	echo "Creating Unknown Office Email\n\n";
	$offices_string = implode("\n\n", $unknownOffices);
	
	$alertName = "Unknown Offices"; // UNIQUE ALERT NAME
	$alertEmailTitle = "Unknown Offices"; // EMAIL TITLE
	$alertDestination = 'noreply@company.com'; // SEND TO
	$alertFrom = "From: noreply@company.com"; // SEND FROM
	$alertEmailBody = // EMAIL BODY BELOW
	
"
The following office ID's were sent today; however, we do not have a corresponding record in our mappings. If these offices are live, please send them back to us with their account numbers:

$offices_string

If these are already live, please contact Support after hours (if not during business hours) with the location ID(s) in this email and the corresponding account number(s) they should pair with to add this to our config and re-run any files needed for today's import.

Thank You,
Support
";

mail($alertDestination, $alertEmailTitle, $alertEmailBody, $alertFrom);
}

if(!empty($fileList) and !$testing){
	echo "Creating File List Email\n\n";
	$file_strings = implode("\n\n", $fileList);
	
	$alertName = "Files Created"; // UNIQUE ALERT NAME
	$alertEmailTitle = "Files Created"; // EMAIL TITLE
	$alertDestination = 'noreply@company.com'; // SEND TO
	$alertFrom = "From: noreply@company.com"; // SEND FROM
	$alertEmailBody = // EMAIL BODY BELOW
	
"
The following The following files were generated and sent for import today:

$file_strings

Please use this to reconcile with parsed file during the morning check. If there is a missing file contact After Hours Support (if not during business hours)) to ensure all files are run and place any missing ones into the SSH.

Thank You,
Support
";

mail($alertDestination, $alertEmailTitle, $alertEmailBody, $alertFrom);
}

if($ftpmailer and !$testing){
	echo "Creating FTP Alert Mailer\n\n";
	
	$alertName = "PFTP Issue"; // UNIQUE ALERT NAME
	$alertEmailTitle = "SSH Connection Failure"; // EMAIL TITLE
	$alertDestination = 'noreply@company.com'; // SEND TO
	$alertFrom = "From: noreply@company.com"; // SEND FROM
	$alertEmailBody = // EMAIL BODY BELOW
	
"
While trying to connect to SSH, we encounted a failure connecting. While we were able to pick-up the file, we have been unable to drop the individual files into the folder. 

Please contact After Hours Support (if not during business hours)) to ensure all files are run and place any missing ones into the SSH.

Thank You,
Support
";

mail($alertDestination, $alertEmailTitle, $alertEmailBody, $alertFrom);
}