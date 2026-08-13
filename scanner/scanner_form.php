<?php
date_default_timezone_set('Asia/Riyadh');
?>

<script type="text/javascript">
	function beginScan(value, valueTwo, valueThree, valueFour, valueFive) {
		fetch("scanner/begin_scan.php", {
			method: 'POST',
			body: new URLSearchParams({
				specifiedUrl: value,
				testId: valueTwo,
				username: valueThree,
				email: valueFour,
				testCases: valueFive
			}),
			cache: 'no-store'
		});
	}


	function sizeTbl(h) {
		var tbl = document.getElementById('tbl');
		tbl.style.display = h;
	}

	checked = true;

	function checkedAll(form1) {
		var aa = document.getElementById('form1');
		if (checked == true) {
			checked = false
		} else {
			checked = true
		}
		for (var i = 0; i < aa.elements.length; i++) {
			aa.elements[i].checked = checked;
		}
	}
</script>

<?php

require_once('functions/databaseFunctions.php');
require_once('classes/Logger.php');

if (isset($_SESSION['username'])) {
	//Get the user's username and email address
	$username = $_SESSION['username'];

	if (isset($_SESSION['email']))
		$email = $_SESSION['email'];
	else
		$email = ''; //maybe email to administrator
?>

	<body>
		<form id="form1" name="form1" method="post">
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
			<p>Enter URL to scan:</p>
			<p>
				<label for="urlToScan"></label>
				<input type="text" size="40" name="urlToScan" id="urlToScan" />
				<br>
				<a href="javascript:sizeTbl('block')">
					<font size="3">Options</font>
				</a>
			</p>
			<div id=tbl name=tbl style="overflow:hidden;display:none">
				<a href="javascript:checkedAll(form1)">
					<font size="3">Check/Uncheck All</font>
				</a><br><br>
				Please select which vulnerabilities to test for:<br>
				<table border="0">
					<tr>
						<td><input type="checkbox" name="rxss" value="rxss" checked /></td>
						<td>Reflected Cross-Site Scripting</td>
					</tr>
					<tr>
						<td><input type="checkbox" name="sxss" value="sxss" checked /></td>
						<td>Stored Cross-Site Scripting </td>
					</tr>
					<tr>
						<td><input type="checkbox" name="sqli" value="sqli" checked /></td>
						<td>Standard SQL Injection</td>
					</tr>
					<tr>
						<td><input type="checkbox" name="basqli" value="basqli" checked /></td>
						<td>Broken Authentication using SQL Injection</td>
					</tr>
					<tr>
						<td><input type="checkbox" name="autoc" value="autoc" checked /></td>
						<td>Autocomplete enabled on sensitive input fields</td>
					</tr>
					<tr>
						<td><input type="checkbox" name="idor" value="idor" checked /></td>
						<td>(Potientially Insecure) Direct Object References</td>
					</tr>
					<tr>
						<td><input type="checkbox" name="dirlist" value="dirlist" checked /></td>
						<td>Directory Listing Enabled</td>
					</tr>
					<tr>
						<td><input type="checkbox" name="bannerdis" value="bannerdis" checked /></td>
						<td>HTTP Banner Disclosure</td>
					</tr>
					<tr>
						<td><input type="checkbox" name="sslcert" value="sslcert" checked /></td>
						<td>SSL Certificate not trusted</td>
					</tr>
					<tr>
						<td><input type="checkbox" name="unredir" value="unredir" checked /></td>
						<td>Unvalidated Redirects</td>
					</tr>
				</table>
				<br>
				<br>Other Options:<br>
				<table border="0">
					<tr>
						<td><input type="checkbox" name="emailpdf" value="emailpdf" checked /></td>
						<td>Email PDF Report</td>
					</tr>
					<tr>
						<td><input type="checkbox" name="crawlurl" value="crawlurl" checked /></td>
						<td>Crawl Website</td>
					</tr>
				</table>
			</div>
			<p>
				<input type="submit" class="button" name="submit" id="submit" value="Start Scan" />
			</p>
		</form>

	<?php

	if (isset($_POST['urlToScan'])) {
		if (!csrf_verify($_POST['csrf_token'] ?? '')) {
			die('CSRF token validation failed');
		}
		$testCases = ' '; //options
		if (isset($_POST['rxss'])) $testCases .= $_POST['rxss'] . ' ';
		if (isset($_POST['sxss'])) $testCases .= $_POST['sxss'] . ' ';
		if (isset($_POST['sqli'])) $testCases .= $_POST['sqli'] . ' ';
		if (isset($_POST['basqli'])) $testCases .= $_POST['basqli'] . ' ';
		if (isset($_POST['autoc'])) $testCases .= $_POST['autoc'] . ' ';
		if (isset($_POST['idor'])) $testCases .= $_POST['idor'] . ' ';
		if (isset($_POST['dirlist'])) $testCases .= $_POST['dirlist'] . ' ';
		if (isset($_POST['bannerdis'])) $testCases .= $_POST['bannerdis'] . ' ';
		if (isset($_POST['sslcert'])) $testCases .= $_POST['sslcert'] . ' ';
		if (isset($_POST['unredir'])) $testCases .= $_POST['unredir'] . ' ';
		if (isset($_POST['emailpdf'])) $testCases .= $_POST['emailpdf'] . ' ';
		if (isset($_POST['crawlurl'])) $testCases .= $_POST['crawlurl'] . ' ';

		$urlToScan = trim($_POST['urlToScan']);
		if (!empty($urlToScan)) {
			$log = new Logger();
			$log->lfile('scanner/logs/eventlogs');

			$log->lwrite('Connecting to database');

			$connectionFlag = connectToDb($db);

			if (!$connectionFlag) {
				$log->lwrite('Error connecting to database');
				echo 'Error connecting to database';
				return;
			}

			// Domain Ownership Verification Check
			$parsedHost = parse_url($urlToScan, PHP_URL_HOST);
			if (!$parsedHost) {
				$parsedHost = parse_url("http://" . $urlToScan, PHP_URL_HOST);
			}
			
			$isLocal = in_array(strtolower($parsedHost), ['localhost', '127.0.0.1', '::1', '[::1]', 'dvwa']);
			
			if (!$isLocal) {
				$verifyQuery = "SELECT id FROM domain_verifications WHERE username = ? AND domain = ? AND verified = 1";
				$verifyStmt = $db->prepare($verifyQuery);
				$verifyStmt->bind_param('ss', $username, $parsedHost);
				$verifyStmt->execute();
				$verifyRes = $verifyStmt->get_result();
				
				if ($verifyRes->num_rows == 0) {
					echo "<p style='color:red;'>Error: You must prove ownership of the domain <b>" . htmlspecialchars($parsedHost) . "</b> before scanning it.</p>";
					echo "<p><a href='verify_domain.php'>Click here to verify domain ownership</a></p>";
					return;
				}
			}

			// Rate Limit: Concurrency (max 1 active scan per user)
			$activeScanQuery = "SELECT id FROM tests WHERE username = ? AND type = 'scan' AND scan_finished = 0";
			$activeScanStmt = $db->prepare($activeScanQuery);
			$activeScanStmt->bind_param('s', $username);
			$activeScanStmt->execute();
			if ($activeScanStmt->get_result()->num_rows > 0) {
				echo "<p style='color:red;'>Error: You already have an active scan running. Please wait for it to finish before starting a new one.</p>";
				return;
			}

			// Rate Limit: Frequency (max 1 scan per 5 minutes per URL)
			$recentScanQuery = "SELECT start_timestamp FROM tests WHERE username = ? AND url = ? ORDER BY start_timestamp DESC LIMIT 1";
			$recentScanStmt = $db->prepare($recentScanQuery);
			$recentScanStmt->bind_param('ss', $username, $urlToScan);
			$recentScanStmt->execute();
			$recentScanRes = $recentScanStmt->get_result();
			if ($recentScanRes->num_rows > 0) {
				$row = $recentScanRes->fetch_object();
				$timeSince = time() - $row->start_timestamp;
				if ($timeSince < 300) {
					$remaining = 300 - $timeSince;
					echo "<p style='color:red;'>Error: You scanned this URL recently. Please wait $remaining seconds before scanning it again.</p>";
					return;
				}
			}

			$log->lwrite('Generating next test ID');
			$nextId = generateNextTestId($db);

			if (!$nextId) {
				$log->lwrite('Next ID generated is null');
				echo 'Next ID generated is null';
				return;
			} else {
				$log->lwrite("Next ID generated is $nextId");
				$testId = $nextId;
				$now = time();
				$query = "INSERT into tests(id,status,numUrlsFound,type,num_requests_sent,start_timestamp,finish_timestamp,scan_finished,url,username,urls_found) VALUES(?, 'Creating profile for new scan...', 0, 'scan', 0, ?, ?, 0, ?, ?, '')";
				$stmt = $db->prepare($query);
				$stmt->bind_param('iiiss', $nextId, $now, $now, $urlToScan, $username);
				$result = $stmt->execute();
				$stmt->close();
				if (!$result) {
					$log->lwrite("Problem executing query for new scan.");
					echo 'Problem inserting a new test into the database. Please try again.';
					return;
				} else {
					$log->lwrite("Successfully executed query for new scan.");
				}
			}

			updateStatus($db, 'Pending...', $testId);

			$stmt = $db->prepare("UPDATE tests SET numUrlsFound = 0 WHERE id = ?");
			$stmt->bind_param("i", $testId);
			$stmt->execute();
			$stmt->close();
			
			$stmt = $db->prepare("UPDATE tests SET duration = 0 WHERE id = ?");
			$stmt->bind_param("i", $testId);
			$stmt->execute();
			$stmt->close();

			echo '<script type="text/javascript">
				document.addEventListener("DOMContentLoaded", function() {
					const evtSource = new EventSource("scanner/scanStream.php?testId=' . "$testId" . '");
					evtSource.addEventListener("status", function(e) {
						document.getElementById("status").innerHTML = JSON.parse(e.data);
					});
					evtSource.addEventListener("vulnerability", function(e) {
						document.getElementById("scanstatus").innerHTML = JSON.parse(e.data);
					});
					evtSource.addEventListener("done", function(e) {
						evtSource.close();
					});
				});</script>';

			$urlToScan = $_POST['urlToScan'];

			$log->lwrite('Calling AJAX function beginCrawl()');
			echo '<script type="text/javascript">';
			echo "beginScan(" . json_encode($urlToScan, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . "," . json_encode($testId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . "," . json_encode($username, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . "," . json_encode($email, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ", " . json_encode($testCases, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ");";
			echo '</script>';
		} else
			echo 'Please enter the URL first.';
	}

	echo '<div id="status"></div><br>';
	echo '<div id="scanstatus"></div><br>';
} else
	echo 'You are not logged in. Please log in to use this feature.';
	?>