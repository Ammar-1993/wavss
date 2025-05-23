<?php

set_time_limit(0);
date_default_timezone_set('Asia/Riyadh');
error_reporting(E_ALL);

$currentDir = './';

// Inculde the phpcrawl-mainclass
require_once($currentDir . "PHPCrawl_2024/classes/phpcrawler.class.php");
require_once($currentDir . "PHPCrawl_2024/classes/mycrawler.php");

//Include parsing class and http library
require_once($currentDir . '../scanner/classes/simplehtmldom/simple_html_dom.php');
require_once($currentDir . '../scanner/classes/httpclient-2024/http.php');

//Include Entity Classes
require_once($currentDir . '../scanner/classes/Form.php');
require_once($currentDir . '../scanner/classes/InputField.php');
require_once($currentDir . '../scanner/classes/Logger.php');
require_once($currentDir . '../scanner/classes/PostOrGetObject.php');

//Include Function Scripts
require_once($currentDir . '../scanner/functions/commonFunctions.php');
require_once($currentDir . '../scanner/functions/databaseFunctions.php');

$log = new Logger();
$log->lfile($currentDir . 'logs/eventlogs');

$log->lwrite('Connecting to database');

connectToDb($db);

$log->lwrite('Instantiating crawler');

$crawler = new MyCrawler();

isset($_POST['specifiedUrl']) ? $urlToScan = $_POST['specifiedUrl'] : $urlToScan = '';
isset($_POST['testId']) ? $testId = $_POST['testId'] : $testId = 0;

if(empty($urlToScan))
{
	echo 'urlToScan is empty';
	$log->lfile('urlToScan is empty');
	return;
}

$log->lwrite("URL to crawler: $urlToScan");

$query = "UPDATE tests SET status = 'Preparing Crawl for $urlToScan' WHERE id = $testId;"; 
$db->query($query);

$crawler->setURL($urlToScan);
$crawler->setTestId($testId);
//$crawler->setFollowMode(3);//Follow mode can be set to 0,1,2 or 3. See class reference online

$crawler->addReceiveContentType("/text\/html/");

$crawler->addNonFollowMatch("/.(jpg|jpeg|gif|png|bmp|css|js)$/ i");

$crawler->setCookieHandling(true);

$crawler->setFirstCrawl(true);

updateStatus($db, "Crawling $urlToScan...", $testId);
$log->lwrite('Starting crawler');
$crawler->go();

$query = "UPDATE tests SET scan_finished = 1 WHERE id = $testId;"; 
$result = $db->query($query);

$urlsFound = $crawler->urlsFound;

$logStr = sizeof($urlsFound) . ' URLs found for test: ' . $testId;

$log->lwrite("All URLs found excluding exceptions:");
foreach($urlsFound as $currentUrl)
	$log->lwrite($currentUrl);


?> 
