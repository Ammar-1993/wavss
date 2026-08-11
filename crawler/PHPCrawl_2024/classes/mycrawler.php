<?php
$currentDir = './';
require_once($currentDir . '../scanner/functions/databaseFunctions.php');

class MyCrawler extends PHPCrawler 
{ 
  function handlePageData(&$page_data) 
  { 	
	array_push($this->urlsFound, $page_data["url"]);
	if($this->firstCrawl)
	{
		$testId = $this->testId;
		
		$newUrl = $page_data['url'];
		if(connectToDb($db))
		{
			$statusMsg = "Found URL " . $newUrl;
			$stmt = $db->prepare("UPDATE tests SET status = ? WHERE id = ?");
			$stmt->bind_param("si", $statusMsg, $testId);
			$stmt->execute();
			$stmt->close();
			
			$stmt = $db->prepare("UPDATE tests SET numUrlsFound = numUrlsFound + 1 WHERE id = ?");
			$stmt->bind_param("i", $testId);
			$stmt->execute();
			$stmt->close();
			
			$stmt = $db->prepare("UPDATE tests SET urls_found=CONCAT(urls_found, ?) WHERE id = ?");
			$appendUrl = $newUrl . "<br>";
			$stmt->bind_param("si", $appendUrl, $testId);
			$stmt->execute();
			$stmt->close();
		}
	}
	
  }
}
?>
