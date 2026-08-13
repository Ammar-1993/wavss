<?php

set_time_limit(0);
date_default_timezone_set('Asia/Riyadh');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$currentDir = './';

// Inculde the phpcrawl-mainclass
require_once($currentDir . "../crawler/PHPCrawl_2024/classes/phpcrawler.class.php");
require_once($currentDir . "../crawler/PHPCrawl_2024/classes/mycrawler.php");

//Include parsing class and http library
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/simplehtmldom/simplehtmldom/simple_html_dom.php';
require_once($currentDir . 'classes/httpclient-2024/http.php');

//Include Entity Classes
require_once($currentDir . 'classes/Form.php');
require_once($currentDir . 'classes/InputField.php');
require_once($currentDir . 'classes/Logger.php');
require_once($currentDir . 'classes/PostOrGetObject.php');
require_once($currentDir . 'classes/Vulnerability.php');

//Include Function Scripts
require_once($currentDir . 'functions/commonFunctions.php');
require_once($currentDir . 'functions/databaseFunctions.php');
require_once($currentDir . 'functions/createPdfReport.php');
require_once($currentDir . 'functions/emailPdfToUser.php');
require_once($currentDir . 'functions/aiTriage.php');

//Include test scripts
require_once($currentDir . 'tests/testForReflectedXSS.php');
require_once($currentDir . 'tests/testForStoredXSS.php');
require_once($currentDir . 'tests/testForSQLi.php');
require_once($currentDir . 'tests/testDirectObjectRefs.php');
require_once($currentDir . 'tests/testAuthenticationSQLi.php');
require_once($currentDir . 'tests/testUnvalidatedRedirects.php');
require_once($currentDir . 'tests/testDirectoryListingEnabled.php');
require_once($currentDir . 'tests/testHttpBannerDisclosure.php');
require_once($currentDir . 'tests/testAutoComplete.php');
require_once($currentDir . 'tests/testSslCertificate.php');

//Include PDF generator
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf_autoconfig.php';

$log = new Logger();
$log->lfile($currentDir . 'logs/eventlogs');

$log->lwrite('Connecting to database');

$connectionFlag = connectToDb($db);

isset($_POST['specifiedUrl']) ? $urlToScan = $_POST['specifiedUrl'] : $urlToScan = '';
isset($_POST['testId']) ? $testId = (int)$_POST['testId'] : $testId = 0;
isset($_POST['username']) ? $username = $_POST['username'] : $username = 'User';
isset($_POST['email']) ? $email = $_POST['email'] : $email = 'wavss@gmail.com';//admin address
isset($_POST['testCases']) ? $testCases = $_POST['testCases'] : $testCases = '';//admin address

if(empty($urlToScan))
{
	echo 'urlToScan is empty';
	$log->lfile('urlToScan is empty');
	return;
}

if(stripos($urlToScan, 'http') !== 0)
	$urlToScan = 'http://' . $urlToScan;

$log->lwrite("URL to scan: $urlToScan");

$query = "UPDATE tests SET status = ? WHERE id = ?";
$stmt = $db->prepare($query);
$statusMsg = "Preparing Crawl for $urlToScan";
$stmt->bind_param('si', $statusMsg, $testId);
$stmt->execute();

//Check if crawling is enabled
$crawlUrlFlag = false;
if(stristr($testCases,' crawlurl ') !== false)
	$crawlUrlFlag = true;

if($crawlUrlFlag)
{
	$log->lwrite('Instantiating crawler');
	$crawler = new MyCrawler();
	$crawler->setURL($urlToScan);
	$crawler->setTestId($testId);
	$crawler->addReceiveContentType("/text\/html/");
	$crawler->addNonFollowMatch("/.(jpg|jpeg|gif|png|bmp|css|js)$/ i");
	$crawler->setCookieHandling(true);
	$crawler->setFirstCrawl(true);
	$crawler->setTestId($testId);
	//$crawler->setFollowMode(0);
	//$crawler->setFollowMode(1);
	//$crawler->setFollowMode(2);//default
	//$crawler->setFollowMode(3);//use this for testing localhost site, otherwise it may start testing xampp, phpmyadmin, etc.

	updateStatus($db, "Crawling $urlToScan...", $testId);
	$log->lwrite('Starting crawler');

	$crawler->go();
	$urlsFound = $crawler->urlsFound;
}
else
	$urlsFound = array($urlToScan);

$logStr = sizeof($urlsFound) . ' URLs found for test: ' . $testId;

$log->lwrite("All URLs found excluding exceptions:");
foreach($urlsFound as $currentUrl)
	$log->lwrite($currentUrl);

$siteBeingTested = getSiteBeingTested($urlToScan);

if(stristr($testCases,' bannerdis ') !== false)
{
	//Test domain for HTTP Banner Disclouse
	$log->lwrite("Beginning testing $urlToScan for HTTP Banner Disclosure");
	if(!$crawlUrlFlag)
		testHttpBannerDisclosure($urlsFound[0], $testId); 
	else
		testHttpBannerDisclosure($siteBeingTested, $testId); 
	$log->lwrite("Finished testing $urlToScan for HTTP Banner Disclosure for test: $testId");
	updateStatus($db, "Finished testing $urlToScan for HTTP Banner Disclosure...", $testId);
}

if(stristr($testCases,' autoc ') !== false)
{
	//Test domain for autocomplete not disabled on input fields of type password
	$log->lwrite('Beginning testing each of the URLs for autocomplete not disabled on sensitive input fields');
	for($i=0; $i<sizeof($urlsFound); $i++)
	{
		testAutoComplete($urlsFound[$i], $testId);
	}
	$log->lwrite('Finished testing each of the URLs for autocomplete not disabled on sensitive input fields for test: ' . $testId);
	updateStatus($db, "Finished testing each of the URLs for autocomplete not disabled on sensitive input fields...", $testId);
}

if(stristr($testCases,' dirlist ') !== false)
{
	//Test domain for Directory Listing enabled
	$log->lwrite("Beginning testing $urlToScan for Directory Listing enabled");
	testDirectoryListingEnabled($urlsFound[0], $siteBeingTested, $testId, $crawlUrlFlag); //The first URL in the array is always the full domain name e.g. http://www.abc.com
	$log->lwrite("Finished testing $urlToScan for Directory Listing enabled for test: $testId");
	updateStatus($db, "Finished testing $urlToScan for Directory Listing enabled...", $testId);
}

if(stristr($testCases,' idor ') !== false)
{
	//Test all URLs for Insecure Direct Object References
	$log->lwrite('Beginning testing each of the URLs for Insecure Direct Object References');
	testDirectObjectRefs($urlsFound, $testId);
	$log->lwrite('Finished testing each of the URLs for Insecure Direct Object References for test: ' . $testId);
	updateStatus($db, "Finished testing each of the URLs for Insecure Direct Object References...", $testId);
}

if(stristr($testCases,' unredir ') !== false)
{
	//Test all URLs for Unvalidated Redirects
	$log->lwrite('Beginning testing each of the URLs for Unvalidated Redirects');
	testUnvalidatedRedirects($urlsFound, $testId);
	$log->lwrite('Finished testing each of the URLs for Unvalidated Redirects for test: ' . $testId);
	updateStatus($db, "Finished testing each of the URLs for Unvalidated Redirects...", $testId);
}

if(stristr($testCases,' sslcert ') !== false)
{
	//Test URLs for untrustworthy SSL certificates
	$log->lwrite('Beginning testing URLs for untrustworthy SSL certificates');
	testSslCertificate($urlsFound, $testId);
	$log->lwrite('Finished testing each of the URLs for untrustworthy SSL certificates for test: ' . $testId);
	updateStatus($db, "Finished testing each of the URLs for untrustworthy SSL certificates...", $testId);
}
	
if(stristr($testCases,' rxss ') !== false)
{
	//Test all URLs for Reflected Cross-Site Scripting
	$log->lwrite('Beginning Reflected XSS testing on each of the URLs');
	for($i=0; $i<sizeof($urlsFound); $i++)
	{
		testForReflectedXSS($urlsFound[$i], $siteBeingTested, $testId);
	}
	$log->lwrite('Finished Reflected XSS testing of all URLS for test: ' . $testId);
	updateStatus($db, "Finished Reflected Cross-Site Scripting testing...", $testId);
}

if(stristr($testCases,' sqli ') !== false)
{
	//Test all URLs for SQL Injection
	$log->lwrite('Beginning SQL Injection testing on each of the URLs');
	for($i=0; $i<sizeof($urlsFound); $i++)
	{
		testForSQLi($urlsFound[$i], $siteBeingTested, $testId);
	}
	$log->lwrite('Finished SQL Injection testing of all URLS for test: ' . $testId);
	updateStatus($db, "Finished SQL Injection testing...", $testId);
}

if(stristr($testCases,' basqli ') !== false)
{
	//Test all URLs for Broken Authentication using SQL Injection
	$log->lwrite('Beginning testing each of the URLs for Broken Authentication using SQL Injection');
	for($i=0; $i<sizeof($urlsFound); $i++)
	{
		testAuthenticationSQLi($urlsFound[$i], $siteBeingTested, $testId);
	}
	$log->lwrite('Finished testing each of the URLs for Broken Authentication using SQL Injection for test: ' . $testId);
	updateStatus($db, "Finished testing each of the URLs for Broken Authenticaton using SQL Injection...", $testId);
}

if(stristr($testCases,' sxss ') !== false)
{
	$log->lwrite('Beginning Stored XSS testing on each of the URLs');
	for($i=0; $i<sizeof($urlsFound); $i++)
	{
		testForStoredXSS($urlsFound[$i], $siteBeingTested, $testId, $urlsFound);		
	}
	$log->lwrite('Finished Stored XSS testing of all URLS for test: ' . $testId);
	updateStatus($db, "Finished Stored Cross-Site Scripting testing...", $testId);
}

// Optional AI Triage Loop
if (!empty(getenv('AI_API_KEY'))) {
	$log->lwrite('Beginning AI triage on findings for test: ' . $testId);
	updateStatus($db, "Running AI triage on findings...", $testId);

	$res = $db->query("SELECT id, type, method, url, attack_str FROM test_results WHERE test_id = $testId AND ai_note IS NULL");
	if ($res && $res->num_rows > 0) {
		$updateStmt = $db->prepare("UPDATE test_results SET ai_note = ? WHERE id = ?");
		while ($row = $res->fetch_object()) {
			$aiNote = triageResult($row->type, $row->url, $row->attack_str);
			if ($aiNote !== null) {
				$updateStmt->bind_param('si', $aiNote, $row->id);
				$updateStmt->execute();
			}
		}
		$updateStmt->close();
	}
	$log->lwrite('Finished AI triage for test: ' . $testId);
}

//Create PDF report
$log->lwrite('Beginning creating PDF report for test: ' . $testId);
createPdfReport($testId, $fileName);
$log->lwrite('Finished creating PDF report for test: ' . $testId);
updateStatus($db, "Finished creating PDF report...", $testId);

if(stristr($testCases,' emailpdf ') !== false)
{
	//Email PDF report
	$log->lwrite('Beginning emailing PDF report to $email for test: ' . $testId);
	emailPdfToUser($fileName, $username, $email, $testId);
	$log->lwrite('Finished emailing PDF report to $email for test: ' . $testId);
	updateStatus($db, "Finished emailing PDF report...", $testId);
}

$query = "UPDATE tests SET scan_finished = 1 WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param('i', $testId);
$result = $stmt->execute();

if(stristr($testCases,' emailpdf ') !== false)
	updateStatus($db, "Scan is complete! The report has been emailed to you and is also in your scan history.", $testId);
else
	updateStatus($db, "Scan is complete! The report is in your scan history.", $testId);
	
$db->close();
?>
