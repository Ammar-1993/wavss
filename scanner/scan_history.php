<?php


require_once('functions/databaseFunctions.php');
date_default_timezone_set('Asia/Riyadh');


global $user;
	
if(isset($_SESSION['username']))
{
	//Get the user's username and email address
	$username = $_SESSION['username'];
		
	if(!connectToDb($db))
	{
		echo 'There was a problem connecting to the database';
		return;
	}
	
	$stmt = $db->prepare("SELECT * FROM tests WHERE type = 'scan' AND username = ?");
	$stmt->bind_param("s", $username);
	$stmt->execute();
	$result = $stmt->get_result();
	$stmt->close();
	if($result)
	{
		$numRows = $result->num_rows;
		if($numRows == 0)
			echo 'You have not performed any previous scans';
		else
		{
			echo '<table border="1" width="900"><tr><th>ID</th><th>Start Time</th><th>URL</th><th>No. Vulnerabilities</th><th>Report</th></tr>';
			for($i=0; $i<$numRows; $i++)
			{
				$row = $result->fetch_object();
				$id = $row->id;
				$startTime = $row->start_timestamp;
				$startTimeFormatted = date('l jS F Y h:i:s A', $startTime);
				$url = $row->url;
				
				$numVulns = 'Unknown';
				$stmtTwo = $db->prepare("SELECT * FROM test_results WHERE test_id = ?");
				$stmtTwo->bind_param("i", $id);
				$stmtTwo->execute();
				$resultTwo = $stmtTwo->get_result();
				$stmtTwo->close();
				if($resultTwo)
					$numVulns = $resultTwo->num_rows;
			
				$report = '<a href="scanner/reports/Test_' . $id . '.pdf" target="_blank">View</a>';
				
				echo '<tr>';
				echo "<td align='center'>$id</td>";
				echo "<td align='left'>$startTimeFormatted</td>";
				echo "<td align='left'>$url</td>";
				echo "<td align='center'>$numVulns</td>";
				echo "<td align='center'>$report</td>";
				echo '</tr>';
			
			}
			echo '</table>';

		}
	
	}
	else
		echo 'There was a problem retrieving your data from the database';
}
else
	echo 'You are not logged in. Please log in to use this feature.';





?>