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

	echo '<form id="form1" name="form1" method="post" class="mb-4">
			  <input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">
			  <div class="mb-3">
				<label for="urlToCrawl" class="form-label">Enter URL to crawl:</label>
				<input type="text" class="form-control" name="urlToCrawl" id="urlToCrawl" />
			  </div>
			  <button type="submit" class="btn btn-primary" name="submit" id="submit" value="Start Crawl">Start Crawl</button>
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

			$now = time();
			$query = "INSERT into tests(status,numUrlsFound,type,num_requests_sent,start_timestamp,finish_timestamp,scan_finished,url,username,urls_found,duration) VALUES('Creating profile for new crawl...', 0, 'crawl', 0, ?, ?, 0, ?, ?, '', 0)";
			$stmt = $db->prepare($query);
			$stmt->bind_param('iiss', $now, $now, $urlToCrawl, $username);
			$result = $stmt->execute();
			
			if (!$result) {
				$log->lwrite("Problem executing query for new crawl.");
				echo 'Problem inserting a new test into the database. Please try again.';
				$stmt->close();
				return;
			} else {
				$testId = $stmt->insert_id;
				$stmt->close();
				$log->lwrite("Successfully executed query for new crawl. ID: $testId");
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

	echo '<div id="status" class="mt-4"></div>';
	echo '<div id="urlsFound" class="mt-3"></div>';
} else
	echo 'You are not logged in. Please log in to use this feature.';
?>