<?php
// Set the default timezone to Saudi Arabia (Riyadh)
date_default_timezone_set('Asia/Riyadh');
?>
<script type="text/javascript">
	function beginCrawl(value, valueTwo) {
		fetch("crawler/begin_crawl.php", {
			method: 'POST',
			body: new URLSearchParams({
				specifiedUrl: value,
				testId: valueTwo
			}),
			cache: 'no-store'
		});
	}
</script>
<?php
$currentDir = './';
require_once($currentDir . 'scanner/functions/databaseFunctions.php');
require_once($currentDir . 'scanner/classes/Logger.php');

if (isset($_SESSION['username'])) {
	$username = $_SESSION['username'];

	echo '<body>
			<form id="form1" name="form1" method="post" >
			  <input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">
			  <p>Enter URL to crawl:</p>
			  <p>
				<label for="urlToCrawl"></label>
				<input type="text" size="40" name="urlToCrawl" id="urlToCrawl" />
			  </p>
			  <p>
				<input type="submit" class="button" name="submit" id="submit" value="Start Crawl" />
			  </p>
			</form>';

	if (isset($_POST['urlToCrawl'])) {
		if (!csrf_verify($_POST['csrf_token'] ?? '')) {
			die('CSRF token validation failed');
		}
		$urlToCrawl = trim($_POST['urlToCrawl']);
		if (!empty($urlToCrawl)) {
			$log = new Logger();
			$log->lfile('crawler/logs/eventlogs');

			$log->lwrite('Connecting to database');

			$connectionFlag = connectToDb($db);

			if (!$connectionFlag) {
				$log->lwrite('Error connecting to database');
				echo 'Error connecting to database';
				return;
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
				$query = "INSERT into tests(id,status,numUrlsFound,type,num_requests_sent,start_timestamp,finish_timestamp,scan_finished,url,username,urls_found) VALUES(?, 'Creating profile for new crawl...', 0, 'crawl', 0, ?, ?, 0, ?, ?, '')";
				$stmt = $db->prepare($query);
				$stmt->bind_param('iiiss', $nextId, $now, $now, $urlToCrawl, $username);
				$result = $stmt->execute();
				$stmt->close();
				if (!$result) {
					$log->lwrite("Problem executing query for new crawl.");
					echo 'Problem inserting a new test into the database. Please try again.';
					return;
				} else {
					$log->lwrite("Successfully executed query for new crawl.");
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
			const updateStatus = function() {
				fetch("crawler/getStatus.php", {
					method: "POST",
					body: new URLSearchParams({testId: ' . "$testId" . '}),
					cache: "no-store"
				}).then(res => res.text()).then(data => {
					document.getElementById("status").innerHTML = data;
				});
			};
			updateStatus();
			setInterval(updateStatus, 500);
		});</script>';

			echo '<script type="text/javascript">
		document.addEventListener("DOMContentLoaded", function() {
			const updateUrlsFound = function() {
				fetch("crawler/getUrlsFound.php", {
					method: "POST",
					body: new URLSearchParams({testId: ' . "$testId" . '}),
					cache: "no-store"
				}).then(res => res.text()).then(data => {
					document.getElementById("urlsFound").innerHTML = data;
				});
			};
			updateUrlsFound();
			setInterval(updateUrlsFound, 500);
		});</script>';

			$log->lwrite('Calling AJAX function beginCrawl()');
			echo '<script type="text/javascript">';
			echo "beginCrawl(" . json_encode($urlToCrawl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . "," . json_encode($testId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ");";
			echo '</script>';
		} else
			echo 'Please enter the URL first.';
	}

	echo '<div id="status"></div><br>';
	echo '<div id="urlsFound"></div><br>';
} else
	echo 'You are not logged in. Please log in to use this feature.';
?>