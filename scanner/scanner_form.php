<?php
date_default_timezone_set('Asia/Riyadh');
?>

<script type="text/javascript">



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
		<form id="form1" name="form1" method="post" class="mb-4">
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
			<div class="mb-3">
				<label for="urlToScan" class="form-label">Enter URL to scan:</label>
				<input type="text" class="form-control" name="urlToScan" id="urlToScan" />
				<div class="mt-2">
					<a href="javascript:sizeTbl('block')" class="text-decoration-none">Options</a>
				</div>
			</div>
			<div id="tbl" name="tbl" style="display:none" class="mb-4 p-3 border rounded bg-light">
				<div class="mb-3">
					<a href="javascript:checkedAll(form1)" class="btn btn-outline-secondary btn-sm">Check/Uncheck All</a>
				</div>
				<p class="fw-bold mb-2">Please select which vulnerabilities to test for:</p>
				<div class="row mb-3">
					<div class="col-md-6">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="rxss" value="rxss" checked id="chk_rxss">
							<label class="form-check-label" for="chk_rxss">Reflected Cross-Site Scripting</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="sxss" value="sxss" checked id="chk_sxss">
							<label class="form-check-label" for="chk_sxss">Stored Cross-Site Scripting</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="sqli" value="sqli" checked id="chk_sqli">
							<label class="form-check-label" for="chk_sqli">Standard SQL Injection</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="basqli" value="basqli" checked id="chk_basqli">
							<label class="form-check-label" for="chk_basqli">Broken Authentication using SQL Injection</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="autoc" value="autoc" checked id="chk_autoc">
							<label class="form-check-label" for="chk_autoc">Autocomplete enabled on sensitive input fields</label>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="idor" value="idor" checked id="chk_idor">
							<label class="form-check-label" for="chk_idor">(Potientially Insecure) Direct Object References</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="dirlist" value="dirlist" checked id="chk_dirlist">
							<label class="form-check-label" for="chk_dirlist">Directory Listing Enabled</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="bannerdis" value="bannerdis" checked id="chk_bannerdis">
							<label class="form-check-label" for="chk_bannerdis">HTTP Banner Disclosure</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="sslcert" value="sslcert" checked id="chk_sslcert">
							<label class="form-check-label" for="chk_sslcert">SSL Certificate not trusted</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="unredir" value="unredir" checked id="chk_unredir">
							<label class="form-check-label" for="chk_unredir">Unvalidated Redirects</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="secheaders" value="secheaders" checked id="chk_secheaders">
							<label class="form-check-label" for="chk_secheaders">Missing Security Headers</label>
						</div>
						<div class="form-check">
							<input class="form-check-input" type="checkbox" name="fileexposure" value="fileexposure" checked id="chk_fileexposure">
							<label class="form-check-label" for="chk_fileexposure">Sensitive File Exposure</label>
						</div>
					</div>
				</div>
				<p class="fw-bold mb-2">Other Options:</p>
				<div class="form-check">
					<input class="form-check-input" type="checkbox" name="emailpdf" value="emailpdf" checked id="chk_emailpdf">
					<label class="form-check-label" for="chk_emailpdf">Email PDF Report</label>
				</div>
				<div class="form-check">
					<input class="form-check-input" type="checkbox" name="crawlurl" value="crawlurl" checked id="chk_crawlurl">
					<label class="form-check-label" for="chk_crawlurl">Crawl Website</label>
				</div>
			</div>
			<button type="submit" class="btn btn-primary" name="submit" id="submit" value="Start Scan">Start Scan</button>
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
		if (isset($_POST['secheaders'])) $testCases .= $_POST['secheaders'] . ' ';
		if (isset($_POST['fileexposure'])) $testCases .= $_POST['fileexposure'] . ' ';
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

			$scanInit = initializeNewScan($db, $username, $urlToScan);
			
			if (!$scanInit['success']) {
				$log->lwrite("Scan init failed: " . $scanInit['error']);
				echo "<p style='color:red;'>Error: " . $scanInit['error'] . "</p>";
				if (strpos($scanInit['error'], 'ownership') !== false) {
					echo "<p><a href='verify_domain.php'>Click here to verify domain ownership</a></p>";
				}
				return;
			}
			
			$testId = $scanInit['testId'];
			$log->lwrite("Next ID generated is $testId");

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

			$log->lwrite('Queueing scan job in database');
			$stmt = $db->prepare("INSERT INTO jobs (test_id, url, username, email, test_cases) VALUES (?, ?, ?, ?, ?)");
			$stmt->bind_param("issss", $testId, $urlToScan, $username, $email, $testCases);
			$stmt->execute();
			$stmt->close();
		} else
			echo 'Please enter the URL first.';
	}

	echo '<div id="status" class="mt-4"></div>';
	echo '<div id="scanstatus" class="mt-3"></div>';
} else
	echo 'You are not logged in. Please log in to use this feature.';
	?>