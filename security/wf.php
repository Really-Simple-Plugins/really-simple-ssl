<?php

require_once(dirname(__FILE__) . '/wordfenceConstants.php');
require_once(dirname(__FILE__) . '/wfScanEngine.php');
require_once(dirname(__FILE__) . '/wfScan.php');
require_once(dirname(__FILE__) . '/wfScanMonitor.php');
require_once(dirname(__FILE__) . '/wfCrawl.php');
require_once(dirname(__FILE__) . '/Diff.php');
require_once(dirname(__FILE__) . '/Diff/Renderer/Html/SideBySide.php');
require_once(dirname(__FILE__) . '/wfAPI.php');
require_once(dirname(__FILE__) . '/wfIssues.php');
require_once(dirname(__FILE__) . '/wfDB.php');
require_once(dirname(__FILE__) . '/wfUtils.php');
require_once(dirname(__FILE__) . '/wfLog.php');
require_once(dirname(__FILE__) . '/wfConfig.php');
require_once(dirname(__FILE__) . '/wfSchema.php');
require_once(dirname(__FILE__) . '/wfCache.php');
require_once(dirname(__FILE__) . '/wfCrypt.php');
require_once(dirname(__FILE__) . '/wfMD5BloomFilter.php');
require_once(dirname(__FILE__) . '/wfView.php');
require_once(dirname(__FILE__) . '/wfHelperString.php');
require_once(dirname(__FILE__) . '/wfDirectoryIterator.php');
require_once(dirname(__FILE__) . '/wfUpdateCheck.php');
require_once(dirname(__FILE__) . '/wfActivityReport.php');
require_once(dirname(__FILE__) . '/wfHelperBin.php');
require_once(dirname(__FILE__) . '/wfDiagnostic.php');
require_once(dirname(__FILE__) . '/wfStyle.php');
require_once(dirname(__FILE__) . '/wfDashboard.php');
require_once(dirname(__FILE__) . '/wfNotification.php');

require_once(dirname(__FILE__) . '/../models/page/wfPage.php');
require_once(dirname(__FILE__) . '/../models/common/wfTab.php');
require_once(dirname(__FILE__) . '/../models/block/wfBlock.php');
require_once(dirname(__FILE__) . '/../models/block/wfRateLimit.php');
require_once(dirname(__FILE__) . '/../models/firewall/wfFirewall.php');
require_once(dirname(__FILE__) . '/../models/scanner/wfScanner.php');
require_once(dirname(__FILE__) . '/wfPersistenceController.php');
require_once(dirname(__FILE__) . '/wfImportExportController.php');
require_once(dirname(__FILE__) . '/wfOnboardingController.php');
require_once(dirname(__FILE__) . '/wfSupportController.php');
require_once(dirname(__FILE__) . '/wfCredentialsController.php');
require_once(dirname(__FILE__) . '/wfVersionCheckController.php');
require_once(dirname(__FILE__) . '/wfDateLocalization.php');
require_once(dirname(__FILE__) . '/wfAdminNoticeQueue.php');
require_once(dirname(__FILE__) . '/wfModuleController.php');
require_once(dirname(__FILE__) . '/wfAlerts.php');
require_once(dirname(__FILE__) . '/wfDeactivationOption.php');

if (version_compare(phpversion(), '5.3', '>=')) {
	require_once(dirname(__FILE__) . '/WFLSPHP52Compatability.php');
	define('WORDFENCE_USE_LEGACY_2FA', wfCredentialsController::useLegacy2FA());
	$wfCoreLoading = true;
	require(dirname(__FILE__) . '/../modules/login-security/wordfence-login-security.php');
}

require_once(dirname(__FILE__) . '/wfJWT.php');
require_once(dirname(__FILE__) . '/wfCentralAPI.php');

if (class_exists('WP_REST_Users_Controller')) { //WP 4.7+
	require_once(dirname(__FILE__) . '/wfRESTAPI.php');
}
if (wfCentral::isSupported()) { //WP 4.4.0+
	require_once(dirname(__FILE__) . '/rest-api/wfRESTAuthenticationController.php');
	require_once(dirname(__FILE__) . '/rest-api/wfRESTConfigController.php');
	require_once(dirname(__FILE__) . '/rest-api/wfRESTScanController.php');
}

class wordfence {
	public static $printStatus = false;
	public static $wordfence_wp_version = false;
	/**
	 * @var WP_Error
	 */
	public static $authError;
	private static $passwordCodePattern = '/\s+wf([a-z0-9 ]+)$/i';
	protected static $lastURLError = false;
	protected static $curlContent = "";
	protected static $curlDataWritten = 0;
	protected static $hasher = '';
	protected static $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	protected static $ignoreList = false;
	private static $wfLog = false;
	private static $hitID = 0;
	private static $debugOn = null;
	private static $runInstallCalled = false;
	private static $userDat = false;

	const ATTACK_DATA_BODY_LIMIT=41943040; //40MB

	public static function installPlugin(){
		self::runInstall();

		if (get_current_user_id() > 0) {
			wfConfig::set('activatingIP', wfUtils::getIP());
		}

		//Used by MU code below
		update_option('wordfenceActivated', 1);

		if (defined('WORDFENCE_LS_FROM_CORE') && WORDFENCE_LS_FROM_CORE) {
			WFLSPHP52Compatability::install_plugin();
		}
	}
	public static function runInstall(){
		if(self::$runInstallCalled){ return; }
		self::$runInstallCalled = true;
		if (function_exists('ignore_user_abort')) {
			@ignore_user_abort(true);
		}
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		$previous_version = ((is_multisite() && function_exists('get_network_option')) ? get_network_option(null, 'wordfence_version', '0.0.0') : get_option('wordfence_version', '0.0.0'));
		if (is_multisite() && function_exists('update_network_option')) {
			update_network_option(null, 'wordfence_version', WORDFENCE_VERSION); //In case we have a fatal error we don't want to keep running install.
		}
		else {
			update_option('wordfence_version', WORDFENCE_VERSION); //In case we have a fatal error we don't want to keep running install.
		}

		wordfence::status(4, 'info', sprintf(/* translators: Wordfence version. */ __('`runInstall` called with previous version = %s', 'wordfence'), $previous_version));

		//EVERYTHING HERE MUST BE IDEMPOTENT

		//Remove old legacy cron job if exists
		wp_clear_scheduled_hook('wordfence_scheduled_scan');

		wfSchema::updateTableCase();
		$schema = new wfSchema();
		$schema->createAll(); //if not exists
		wfConfig::updateTableExists(true);

		/** @var wpdb $wpdb */
		global $wpdb;

		//6.1.15
		$configTable = wfDB::networkTable('wfConfig');
		$hasAutoload = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT * FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME='autoload'
AND TABLE_NAME=%s
SQL
			, $configTable));
		if (!$hasAutoload) {
			$wpdb->query("ALTER TABLE {$configTable} ADD COLUMN autoload ENUM('no', 'yes') NOT NULL DEFAULT 'yes'");
			$wpdb->query("UPDATE {$configTable} SET autoload = 'no' WHERE name = 'wfsd_engine' OR name LIKE 'wordfence_chunked_%'");
		}

		$wpdb->query("DELETE FROM $configTable WHERE `name` = 'emailedIssuesList' AND LENGTH(`val`) > 2 * 1024 * 1024");
		wfConfig::setDefaults(); //If not set

		$restOfSite = wfConfig::get('cbl_restOfSiteBlocked', 'notset');
		if($restOfSite == 'notset'){
			wfConfig::set('cbl_restOfSiteBlocked', '1');
		}

		if(wfConfig::get('autoUpdate') == '1'){
			wfConfig::enableAutoUpdate(); //Sets up the cron
		}

		$freshAPIKey = !wfConfig::get('apiKey');
		if ($freshAPIKey) {
			wfConfig::set('touppPromptNeeded', true);
		}

		self::scheduleCrons(15);

		$db = new wfDB();

		// IPv6 schema changes for 6.0.1
		$tables_with_ips = array(
			'wfCrawlers',
			'wfBadLeechers',
			'wfBlockedIPLog',
			'wfBlocks', //Removed in 7.0.1 but left in in case migrating from really old
			'wfHits',
			'wfLocs',
			'wfLogins',
			'wfReverseCache',
		);

		foreach ($tables_with_ips as $ip_table) {
			$ptable = wfDB::networkTable($ip_table);
			$tableExists = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT TABLE_NAME FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE()
AND TABLE_NAME=%s
SQL
				, $ptable));
			if (!$tableExists) {
				continue;
			}

			$result = $wpdb->get_row("SHOW FIELDS FROM {$ptable} where field = 'IP'");
			if (!$result || strtolower($result->Type) == 'binary(16)') {
				continue;
			}

			$db->queryWriteIgnoreError("ALTER TABLE {$ptable} MODIFY IP BINARY(16)");

			// Just to be sure we don't corrupt the data if the alter fails.
			$result = $wpdb->get_row("SHOW FIELDS FROM {$ptable} where field = 'IP'");
			if (!$result || strtolower($result->Type) != 'binary(16)') {
				continue;
			}
			$db->queryWriteIgnoreError("UPDATE {$ptable} SET IP = CONCAT(LPAD(CHAR(0xff, 0xff), 12, CHAR(0)), LPAD(
	CHAR(
		CAST(IP as UNSIGNED) >> 24 & 0xFF,
		CAST(IP as UNSIGNED) >> 16 & 0xFF,
		CAST(IP as UNSIGNED) >> 8 & 0xFF,
		CAST(IP as UNSIGNED) & 0xFF
	),
	4,
	CHAR(0)
))");
		}

		//Country reassignment moved to the GeoIP file sync segment

		if (wfConfig::get('other_hideWPVersion')) {
			wfUtils::hideReadme();
		}

		$colsFor610 = array(
			'attackLogTime'     => '`attackLogTime` double(17,6) unsigned NOT NULL AFTER `id`',
			'statusCode'        => '`statusCode` int(11) NOT NULL DEFAULT 0 AFTER `jsRun`',
			'action'            => "`action` varchar(64) NOT NULL DEFAULT '' AFTER `UA`",
			'actionDescription' => '`actionDescription` text AFTER `action`',
			'actionData'        => '`actionData` text AFTER `actionDescription`',
		);

		$hitTable = wfDB::networkTable('wfHits');
		foreach ($colsFor610 as $col => $colDefintion) {
			$count = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT * FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME=%s
AND TABLE_NAME=%s
SQL
				, $col, $hitTable));
			if (!$count) {
				$wpdb->query("ALTER TABLE $hitTable ADD COLUMN $colDefintion");
			}
		}

		$has404 = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT * FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME='is404'
AND TABLE_NAME=%s
SQL
			, $hitTable));
		if ($has404) {
			$wpdb->query(<<<SQL
UPDATE $hitTable
SET statusCode= CASE
WHEN is404=1 THEN 404
ELSE 200
END
SQL
			);

			$wpdb->query("ALTER TABLE $hitTable DROP COLUMN `is404`");
		}

		$loginsTable = wfDB::networkTable('wfLogins');
		$hasHitID = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT * FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME='hitID'
AND TABLE_NAME=%s
SQL
			, $loginsTable));
		if (!$hasHitID) {
			$wpdb->query("ALTER TABLE $loginsTable ADD COLUMN hitID int(11) DEFAULT NULL AFTER `id`, ADD INDEX(hitID)");
		}

		if (!WFWAF_SUBDIRECTORY_INSTALL) {
			wfWAFConfig::set('wafDisabled', false);
		}

		// Call this before creating the index in cases where the wp-cron isn't running.
		self::trimWfHits(true);
		$hitsTable = wfDB::networkTable('wfHits');
		$hasAttackLogTimeIndex = $wpdb->get_var($wpdb->prepare(<<<SQL
SELECT COLUMN_KEY FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = %s
AND COLUMN_NAME = 'attackLogTime'
SQL
			, $hitsTable));

		if (!$hasAttackLogTimeIndex) {
			$wpdb->query("ALTER TABLE $hitsTable ADD INDEX `attackLogTime` (`attackLogTime`)");
		}

		//6.1.16
		$allowed404s = wfConfig::get('allowed404s', '');
		if (!wfConfig::get('allowed404s6116Migration', false)) {
			if (!preg_match('/(?:^|\b)browserconfig\.xml(?:\b|$)/i', $allowed404s)) {
				if (strlen($allowed404s) > 0) {
					$allowed404s .= "\n";
				}
				$allowed404s .= "/browserconfig.xml";
				wfConfig::set('allowed404s', $allowed404s);
			}

			wfConfig::set('allowed404s6116Migration', 1);
		}
		if (wfConfig::get('email_summary_interval') == 'biweekly') {
			wfConfig::set('email_summary_interval', 'weekly');
		}

		//6.2.0
		wfConfig::migrateCodeExecutionForUploadsPHP7();

		//6.2.3
		if (!WFWAF_SUBDIRECTORY_INSTALL && class_exists('wfWAFIPBlocksController')) {
			wfWAFIPBlocksController::setNeedsSynchronizeConfigSettings(); //changed slightly for 7.0.1
		}

		//6.2.8
		wfCache::removeCaching();

		//6.2.10
		$snipCacheTable = wfDB::networkTable('wfSNIPCache');
		$hasType = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT * FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME='type'
AND TABLE_NAME=%s
SQL
			, $snipCacheTable));
		if (!$hasType) {
			$wpdb->query("ALTER TABLE `{$snipCacheTable}` ADD `type` INT  UNSIGNED  NOT NULL  DEFAULT '0'");
			$wpdb->query("ALTER TABLE `{$snipCacheTable}` ADD INDEX (`type`)");
		}

		//6.3.5
		$fileModsTable = wfDB::networkTable('wfFileMods');
		$hasStoppedOn = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT * FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME='stoppedOnSignature'
AND TABLE_NAME=%s
SQL
			, $fileModsTable));
		if (!$hasStoppedOn) {
			$wpdb->query("ALTER TABLE {$fileModsTable} ADD COLUMN stoppedOnSignature VARCHAR(255) NOT NULL DEFAULT ''");
			$wpdb->query("ALTER TABLE {$fileModsTable} ADD COLUMN stoppedOnPosition INT UNSIGNED NOT NULL DEFAULT '0'");
		}

		$blockedIPLogTable = wfDB::networkTable('wfBlockedIPLog');
		$hasType = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT * FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME='blockType'
AND TABLE_NAME=%s
SQL
			, $blockedIPLogTable));
		if (!$hasType) {
			$wpdb->query("ALTER TABLE {$blockedIPLogTable} ADD blockType VARCHAR(50) NOT NULL DEFAULT 'generic'");
			$wpdb->query("ALTER TABLE {$blockedIPLogTable} DROP PRIMARY KEY");
			$wpdb->query("ALTER TABLE {$blockedIPLogTable} ADD PRIMARY KEY (IP, unixday, blockType)");
		}

		//6.3.6
		if (!wfConfig::get('migration636_email_summary_excluded_directories')) {
			$excluded_directories = explode(',', (string) wfConfig::get('email_summary_excluded_directories'));
			$key = array_search('wp-content/plugins/wordfence/tmp', $excluded_directories); if ($key !== false) { unset($excluded_directories[$key]); }
			$key = array_search('wp-content/wflogs', $excluded_directories); if ($key === false) { $excluded_directories[] = 'wp-content/wflogs'; }
			wfConfig::set('email_summary_excluded_directories', implode(',', $excluded_directories));
			wfConfig::set('migration636_email_summary_excluded_directories', 1, wfConfig::DONT_AUTOLOAD);
		}

		$fileModsTable = wfDB::networkTable('wfFileMods');
		$hasSHAC = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT * FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME='SHAC'
AND TABLE_NAME=%s
SQL
			, $fileModsTable));
		if (!$hasSHAC) {
			$wpdb->query("ALTER TABLE {$fileModsTable} ADD COLUMN `SHAC` BINARY(32) NOT NULL DEFAULT '' AFTER `newMD5`");
			$wpdb->query("ALTER TABLE {$fileModsTable} ADD COLUMN `isSafeFile` VARCHAR(1) NOT NULL  DEFAULT '?' AFTER `stoppedOnPosition`");
		}

		//6.3.7
		$hooverTable = wfDB::networkTable('wfHoover');
		$hostKeySize = $wpdb->get_var($wpdb->prepare(<<<SQL
SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME='hostKey'
AND TABLE_NAME=%s
SQL
			, $hooverTable));
		if ($hostKeySize < 124) {
			$wpdb->query("ALTER TABLE {$hooverTable} CHANGE `hostKey` `hostKey` VARBINARY(124) NULL DEFAULT NULL");
		}

		//6.3.15
		$scanFileContents = wfConfig::get('scansEnabled_fileContents', false);
		if (!wfConfig::get('fileContentsGSB6315Migration', false)) {
			if (!$scanFileContents) {
				wfConfig::set('scansEnabled_fileContentsGSB', false);
			}
			wfConfig::set('fileContentsGSB6315Migration', 1);
		}

		//6.3.20
		$lastBlockAggregation = wfConfig::get('lastBlockAggregation', 0);
		if ($lastBlockAggregation == 0) {
			wfConfig::set('lastBlockAggregation', time());
		}

		//7.0.1
		//---- Config Migration
		if (!wfConfig::get('config701Migration', false)) {
			//loginSec_strongPasswds gains a toggle
			if (wfConfig::get('loginSec_strongPasswds') == '') {
				wfConfig::set('loginSec_strongPasswds', 'pubs');
				wfConfig::set('loginSec_strongPasswds_enabled', false);
			}

			$limitedOptions = wfScanner::limitedScanTypeOptions();
			$standardOptions = wfScanner::standardScanTypeOptions();
			$highSensitivityOptions = wfScanner::highSensitivityScanTypeOptions();
			$settings = wfScanner::customScanTypeOptions();
			if ($settings == $limitedOptions) { wfConfig::set('scanType', wfScanner::SCAN_TYPE_LIMITED); }
			else if ($settings == $standardOptions) { wfConfig::set('scanType', wfScanner::SCAN_TYPE_STANDARD); }
			else if ($settings == $highSensitivityOptions) { wfConfig::set('scanType', wfScanner::SCAN_TYPE_HIGH_SENSITIVITY); }
			else { wfConfig::set('scanType', wfScanner::SCAN_TYPE_CUSTOM); }

			if (wfConfig::get('isPaid')) {
				wfConfig::set('keyType', wfLicense::KEY_TYPE_PAID_CURRENT);
			}

			wfConfig::remove('premiumAutoRenew');
			wfConfig::remove('premiumNextRenew');
			wfConfig::remove('premiumPaymentExpiring');
			wfConfig::remove('premiumPaymentExpired');
			wfConfig::remove('premiumPaymentMissing');
			wfConfig::remove('premiumPaymentHold');

			wfConfig::set('config701Migration', 1);
		}

		//---- wfBlocks migration
		$oldBlocksTable = wfDB::networkTable('wfBlocks');
		$blocksTable = wfBlock::blocksTable();
		$oldBlocksExist = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT TABLE_NAME FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE()
AND TABLE_NAME=%s
SQL
			, $oldBlocksTable));
		if ($oldBlocksExist && !wfConfig::get('blocks701Migration', false)) {
			//wfBlocks migration
			$query = $wpdb->prepare("INSERT INTO `{$blocksTable}` (`type`, `IP`, `blockedTime`, `reason`, `lastAttempt`, `blockedHits`, `expiration`) SELECT CASE 
WHEN wfsn = 1 AND permanent = 0 THEN %d
WHEN wfsn = 0 AND permanent = 0 THEN %d
WHEN wfsn = 0 AND permanent = 1 THEN %d
END AS `type`, `IP`, `blockedTime`, `reason`, `lastAttempt`, `blockedHits`, CASE 
WHEN wfsn = 1 AND permanent = 0 THEN (`blockedTime` + 600)
WHEN wfsn = 0 AND permanent = 0 THEN (`blockedTime` + %d)
WHEN wfsn = 0 AND permanent = 1 THEN 0
END AS `expiration` FROM `{$oldBlocksTable}`", wfBlock::TYPE_WFSN_TEMPORARY, wfBlock::TYPE_RATE_BLOCK, wfBlock::TYPE_IP_AUTOMATIC_PERMANENT, wfConfig::get('blockedTime'));
			$wpdb->query($query);

			//wfBlocksAdv migration
			$advancedBlocksTable = wfDB::networkTable('wfBlocksAdv');
			$advancedBlocks = $wpdb->get_results("SELECT * FROM {$advancedBlocksTable}", ARRAY_A);
			foreach ($advancedBlocks as $b) {
				$blockType = $b['blockType']; //unused
				$blockString = $b['blockString'];
				$ctime = (int) $b['ctime'];
				$reason = $b['reason'];
				$totalBlocked = (int) $b['totalBlocked'];
				$lastBlocked = (int) $b['lastBlocked'];

				list($ipRange, $uaRange, $referrer, $hostname) = explode('|', $blockString);

				wfBlock::createPattern($reason, $ipRange, $hostname, $uaRange, $referrer, wfBlock::DURATION_FOREVER, $ctime, $lastBlocked, $totalBlocked);
			}

			//throttle migration
			$throttleTable = wfDB::networkTable('wfThrottleLog');
			$throttles = $wpdb->get_results("SELECT * FROM {$throttleTable}", ARRAY_A);
			foreach ($throttles as $t) {
				$ip = wfUtils::inet_ntop($t['IP']);
				$startTime = (int) $t['startTime'];
				$endTime = (int) $t['endTime'];
				$timesThrottled = (int) $t['timesThrottled'];
				$reason = $t['lastReason'];

				wfBlock::createRateThrottle($reason, $ip, wfBlock::rateLimitThrottleDuration(), $startTime, $endTime, $timesThrottled);
			}

			//lockout migration
			$lockoutTable = wfDB::networkTable('wfLockedOut');
			$lockouts = $wpdb->get_results("SELECT * FROM {$lockoutTable}", ARRAY_A);
			foreach ($lockouts as $l) {
				$ip = wfUtils::inet_ntop($l['IP']);
				$blockedTime = (int) $l['blockedTime'];
				$reason = $l['reason'];
				$lastAttempt = (int) $l['lastAttempt'];
				$blockedHits = (int) $l['blockedHits'];

				wfBlock::createLockout($reason, $ip, wfBlock::lockoutDuration(), $blockedTime, $lastAttempt, $blockedHits);
			}

			//country blocking migration
			$countries = wfConfig::get('cbl_countries', false);
			if ($countries) {
				$countries = explode(',', $countries);
				wfBlock::createCountry(__('Automatically generated from previous country blocking settings', 'wordfence'), wfConfig::get('cbl_loginFormBlocked', false), wfConfig::get('cbl_restOfSiteBlocked', false), $countries);
			}

			wfConfig::set('blocks701Migration', 1);
		}

		//---- wfIssues/wfPendingIssues Schema Change
		$issuesTable = wfDB::networkTable('wfIssues');
		$pendingIssuesTable = wfDB::networkTable('wfPendingIssues');
		$hasLastUpdated = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT * FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
AND COLUMN_NAME='lastUpdated'
AND TABLE_NAME=%s
SQL
			, $issuesTable));
		if (!$hasLastUpdated) {
			$wpdb->query("ALTER TABLE `{$issuesTable}` ADD `lastUpdated` INT UNSIGNED NOT NULL AFTER `time`");
			$wpdb->query("ALTER TABLE `{$issuesTable}` ADD INDEX (`lastUpdated`)");
			$wpdb->query("ALTER TABLE `{$issuesTable}` ADD INDEX (`status`)");
			$wpdb->query("ALTER TABLE `{$issuesTable}` ADD INDEX (`ignoreP`)");
			$wpdb->query("ALTER TABLE `{$issuesTable}` ADD INDEX (`ignoreC`)");
			$wpdb->query("UPDATE `{$issuesTable}` SET `lastUpdated` = `time` WHERE `lastUpdated` = 0");

			$wpdb->query("ALTER TABLE `{$pendingIssuesTable}` ADD `lastUpdated` INT UNSIGNED NOT NULL AFTER `time`");
			$wpdb->query("ALTER TABLE `{$pendingIssuesTable}` ADD INDEX (`lastUpdated`)");
			$wpdb->query("ALTER TABLE `{$pendingIssuesTable}` ADD INDEX (`status`)");
			$wpdb->query("ALTER TABLE `{$pendingIssuesTable}` ADD INDEX (`ignoreP`)");
			$wpdb->query("ALTER TABLE `{$pendingIssuesTable}` ADD INDEX (`ignoreC`)");
		}

		//---- Scheduled scan start hour and manual type
		if (wfConfig::get('schedStartHour') < 0) {
			wfConfig::set('schedStartHour', wfWAFUtils::random_int(0, 23));

			if (wfConfig::get('schedMode') == 'manual') {
				$sched = wfConfig::get_ser('scanSched', array());
				if (is_array($sched) && is_array($sched[0])) { //Try to determine the closest matching value for manualScanType
					$hours = array_fill(0, 24, 0);
					$distinctHours = array();
					$days = array_fill(0, 7, 0);
					$distinctDays = array();
					foreach ($sched as $dayIndex => $day) {
						foreach ($day as $h => $enabled) {
							if ($enabled) {
								if (in_array($h, $distinctHours)) {
									$distinctHours[] = $h;
								}
								$hours[$h]++;
								if (in_array($dayIndex, $distinctDays)) {
									$distinctDays[] = $dayIndex;
								}
								$days[$dayIndex]++;
							}
						}
					}

					sort($distinctHours, SORT_NUMERIC);
					sort($distinctDays, SORT_NUMERIC);
					if (count($distinctDays) == 7) {
						if (count($distinctHours) == 1) {
							wfConfig::set('manualScanType', wfScanner::MANUAL_SCHEDULING_ONCE_DAILY);
							wfConfig::set('schedStartHour', $distinctHours[0]);
						}
						else if (count($distinctHours) == 2) {
							$matchesTwiceDaily = false;
							if ($distinctHours[0] + 12 == $distinctHours[1]) {
								$matchesTwiceDaily = true;
								foreach ($sched as $dayIndex => $day) {
									if (!$day[$distinctHours[0]] || !$day[$distinctHours[1]]) {
										$matchesTwiceDaily = false;
									}
								}
							}

							if ($matchesTwiceDaily) {
								wfConfig::set('manualScanType', wfScanner::MANUAL_SCHEDULING_TWICE_DAILY);
								wfConfig::set('schedStartHour', $distinctHours[0]);
							}
							else {
								wfConfig::set('manualScanType', wfScanner::MANUAL_SCHEDULING_CUSTOM);
							}
						}
						else {
							wfConfig::set('manualScanType', wfScanner::MANUAL_SCHEDULING_CUSTOM);
						}
					}
					else if (count($distinctDays) == 5 && count($distinctHours) == 1) {
						if ($days[2] == 0 && $days[4] == 0 && $hours[$distinctHours[0]] == 5) {
							wfConfig::set('manualScanType', wfScanner::MANUAL_SCHEDULING_ODD_DAYS_WEEKENDS);
							wfConfig::set('schedStartHour', $distinctHours[0]);
						}
						else if ($days[0] == 0 && $days[6] == 0 && $hours[$distinctHours[0]] == 5) {
							wfConfig::set('manualScanType', wfScanner::MANUAL_SCHEDULING_WEEKDAYS);
							wfConfig::set('schedStartHour', $distinctHours[0]);
						}
						else {
							wfConfig::set('manualScanType', wfScanner::MANUAL_SCHEDULING_CUSTOM);
						}
					}
					else if (count($distinctDays) == 2 && count($distinctHours) == 1) {
						if ($distinctDays[0] == 0 && $distinctDays[1] == 6 && $hours[$distinctHours[0]] == 2) {
							wfConfig::set('manualScanType', wfScanner::MANUAL_SCHEDULING_WEEKENDS);
							wfConfig::set('schedStartHour', $distinctHours[0]);
						}
						else {
							wfConfig::set('manualScanType', wfScanner::MANUAL_SCHEDULING_CUSTOM);
						}
					}
					else {
						wfConfig::set('manualScanType', wfScanner::MANUAL_SCHEDULING_CUSTOM);
					}
				}
				//manualScanType
			}
		}

		//---- Onboarding
		if (!$freshAPIKey) {
			wfOnboardingController::migrateOnboarding();
		}

		//7.0.2
		if (!wfConfig::get('blocks702Migration')) {
			$blocksTable = wfBlock::blocksTable();

			$query = "UPDATE `{$blocksTable}` SET `type` = %d WHERE `type` = %d AND `parameters` IS NOT NULL AND `parameters` LIKE '%\"ipRange\"%'";
			$wpdb->query($wpdb->prepare($query, wfBlock::TYPE_PATTERN, wfBlock::TYPE_IP_AUTOMATIC_PERMANENT));

			$countryBlock = wfBlock::countryBlocks();
			if (!count($countryBlock)) {
				$query = "UPDATE `{$blocksTable}` SET `type` = %d WHERE `type` = %d AND `parameters` IS NOT NULL AND `parameters` LIKE '%\"blockLogin\"%' LIMIT 1";
				$wpdb->query($wpdb->prepare($query, wfBlock::TYPE_COUNTRY, wfBlock::TYPE_IP_AUTOMATIC_PERMANENT));
			}

			$query = "DELETE FROM `{$blocksTable}` WHERE `type` = %d AND `parameters` IS NOT NULL AND `parameters` LIKE '%\"blockLogin\"%'";
			$wpdb->query($wpdb->prepare($query, wfBlock::TYPE_IP_AUTOMATIC_PERMANENT));

			wfConfig::set('blocks702Migration', 1);
		}

		//7.0.3
		/*if (!wfConfig::get('generateAllOptionsNotification')) {
			new wfNotification(null, wfNotification::PRIORITY_HIGH_WARNING, '<p>Developers: If you prefer to edit all Wordfence options on one page, you can enable the "All Options" page here:</p>
<p><a href="javascript:WFAD.enableAllOptionsPage();" class="wf-btn wf-btn-primary wf-btn-callout-subtle">Enable "All Options" Page</a></p>', 'wfplugin_devalloptions');
			wfConfig::set('generateAllOptionsNotification', 1);
		}*/

		//7.1.9
		if (wfConfig::get('loginSec_maxFailures') == 1) {
			wfConfig::set('loginSec_maxFailures', 2);
		}

		$blocksTable = wfBlock::blocksTable();
		$patternBlocks = wfBlock::patternBlocks();
		foreach ($patternBlocks as $b) {
			if (!empty($b->ipRange) && preg_match('/^\d+\-\d+$/', $b->ipRange)) { //Old-style range block using long2ip
				$ipRange = new wfUserIPRange($b->ipRange);
				$ipRange = $ipRange->getIPString();

				$parameters = $b->parameters;
				$parameters['ipRange'] = $ipRange;
				$wpdb->query($wpdb->prepare("UPDATE `{$blocksTable}` SET `parameters` = %s WHERE `id` = %d", json_encode($parameters), $b->id));
			}
		}

		wfConfig::set('needsGeoIPSync', true, wfConfig::DONT_AUTOLOAD);

		// Set the default scan options based on scan type.
		if (!wfConfig::get('config720Migration', false)) {
			// Replace critical/warning checkboxes with setting based on numeric severity value.
			if (wfConfig::hasCachedOption('alertOn_critical') && wfConfig::hasCachedOption('alertOn_warnings')) {
				$alertOnCritical = wfConfig::get('alertOn_critical');
				$alertOnWarnings = wfConfig::get('alertOn_warnings');
				wfConfig::set('alertOn_scanIssues', $alertOnCritical || $alertOnWarnings);
				if ($alertOnCritical && ! $alertOnWarnings) {
					wfConfig::set('alertOn_severityLevel', wfIssues::SEVERITY_HIGH);
				} else {
					wfConfig::set('alertOn_severityLevel', wfIssues::SEVERITY_LOW);
				}
			}

			// Update severity for existing issues where they are still using the old severity values.
			foreach (wfIssues::$issueSeverities as $issueType => $severity) {
				$wpdb->query($wpdb->prepare("UPDATE $issuesTable SET severity = %d 
				WHERE `type` = %s
				AND severity in (0,1,2)
				", $severity, $issueType));
			}

			$syncedOptions = array();
			switch (wfConfig::get('scanType')) {
				case wfScanner::SCAN_TYPE_LIMITED:
					$syncedOptions = wfScanner::limitedScanTypeOptions();
					break;
				case wfScanner::SCAN_TYPE_STANDARD:
					$syncedOptions = wfScanner::standardScanTypeOptions();
					break;
				case wfScanner::SCAN_TYPE_HIGH_SENSITIVITY:
					$syncedOptions = wfScanner::highSensitivityScanTypeOptions();
					break;
			}
			if ($syncedOptions) {
				foreach ($syncedOptions as $key => $value) {
					if (is_bool($value)) {
						wfConfig::set($key, $value ? 1 : 0);
					}
				}
			}

			wfConfig::set('config720Migration', true);
		}

		//7.2.3
		if (wfConfig::get('waf_status') === false) {
			$firewall = new wfFirewall();
			$firewall->syncStatus(true);
		}

		//7.3.1
		//---- drop long deprecated tables
		$tables = array('wfBadLeechers', 'wfBlockedCommentLog', 'wfBlocks', 'wfBlocksAdv', 'wfLeechers', 'wfLockedOut', 'wfNet404s', 'wfScanners', 'wfThrottleLog', 'wfVulnScanners');
		foreach ($tables as $t) {
			$schema->drop($t);
		}

		//7.5.10
		$knownFilesTable = wfDB::networkTable('wfKnownFileList');
		$wordpressPathColumn = $wpdb->get_row($wpdb->prepare("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'wordpress_path'", $knownFilesTable));
		if ($wordpressPathColumn === null) {
			$wpdb->query("DELETE FROM `{$knownFilesTable}`");
			$wpdb->query("ALTER TABLE `{$knownFilesTable}` ADD COLUMN wordpress_path TEXT NOT NULL");
		}

		$realPathColumn = $wpdb->get_row($wpdb->prepare("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'real_path'", $fileModsTable));
		if ($realPathColumn === null) {
			$wpdb->query("DELETE FROM `{$fileModsTable}`");
			$wpdb->query("ALTER TABLE `{$fileModsTable}` ADD COLUMN real_path TEXT NOT NULL AFTER filename");
		}

		//---- enable legacy 2fa if applicable
		if (wfConfig::get('isPaid') && (wfCredentialsController::hasOld2FARecords() || version_compare(phpversion(), '5.3', '<'))) {
			wfConfig::set(wfCredentialsController::ALLOW_LEGACY_2FA_OPTION, true);
		}

		//Check the How does Wordfence get IPs setting
		wfUtils::requestDetectProxyCallback();

		//Install new schedule. If schedule config is blank it will install the default 'auto' schedule.
		wfScanner::shared()->scheduleScans();

		//Check our minimum versions and generate the necessary warnings
		if (!wp_next_scheduled('wordfence_version_check')) {
			wp_schedule_single_event(time(), 'wordfence_version_check');
		}

		//Must be the final line
	}


	public static function initProtection(){ //Basic protection during WAF learning period
		// Infinite WP Client - Authentication Bypass < 1.9.4.5
		// https://wpvulndb.com/vulnerabilities/10011
		$iwpRule = new wfWAFRule(wfWAF::getInstance(), 0x80000000, null, 'auth-bypass', 100, 'Infinite WP Client - Authentication Bypass < 1.9.4.5', 0, 'block', null);
		wfWAF::getInstance()->setRules(wfWAF::getInstance()->getRules() + array(0x80000000 => $iwpRule));

		if (strrpos(wfWAF::getInstance()->getRequest()->getRawBody(), '_IWP_JSON_PREFIX_') !== false) {
			$iwpRequestDataArray = explode('_IWP_JSON_PREFIX_', wfWAF::getInstance()->getRequest()->getRawBody());
			$iwpRequest = json_decode(trim(base64_decode($iwpRequestDataArray[1])), true);
			if (is_array($iwpRequest)) {
				if (array_key_exists('iwp_action', $iwpRequest) &&
				    ($iwpRequest['iwp_action'] === 'add_site' || $iwpRequest['iwp_action'] === 'readd_site')
				) {
					require_once ABSPATH . '/wp-admin/includes/plugin.php';
					if (is_plugin_active('iwp-client/init.php')) {
						$iwpPluginData = get_plugin_data(WP_PLUGIN_DIR . '/iwp-client/init.php');
						if (version_compare('1.9.4.5', $iwpPluginData['Version'], '>')) {
							remove_action('setup_theme', 'iwp_mmb_set_request');
						}
					}

					if ((is_multisite() ? get_site_option('iwp_client_action_message_id') : get_option('iwp_client_action_message_id')) &&
					    (is_multisite() ? get_site_option('iwp_client_public_key') : get_option('iwp_client_public_key'))
					) {
						wfWAF::getInstance()->getStorageEngine()->logAttack(array($iwpRule), 'request.rawBody',
							wfWAF::getInstance()->getRequest()->getRawBody(),
							wfWAF::getInstance()->getRequest(),
							wfWAF::getInstance()->getRequest()->getMetadata()
						);
					}
				}
			}
		}
	}

	public static function install_actions(){
		register_activation_hook(WORDFENCE_FCPATH, 'wordfence::installPlugin');
		register_deactivation_hook(WORDFENCE_FCPATH, 'wordfence::uninstallPlugin');

		$versionInOptions = ((is_multisite() && function_exists('get_network_option')) ? get_network_option(null, 'wordfence_version', false) : get_option('wordfence_version', false));
		if( (! $versionInOptions) || version_compare(WORDFENCE_VERSION, $versionInOptions, '>')){
			//Either there is no version in options or the version in options is greater and we need to run the upgrade
			self::runInstall();
		}

		self::getLog()->initLogRequest();

		//Fix wp_mail bug when $_SERVER['SERVER_NAME'] is undefined
		add_filter('wp_mail_from', 'wordfence::fixWPMailFromAddress');

		//These access wfConfig::get('apiKey') and will fail if runInstall hasn't executed.
		if(defined('MULTISITE') && MULTISITE === true){
			global $blog_id;
			if($blog_id == 1 && get_option('wordfenceActivated') != 1){ return; } //Because the plugin is active once installed, even before it's network activated, for site 1 (WordPress team, why?!)
		}
		//User may be logged in or not, so register both handlers
		add_action('wp_ajax_nopriv_wordfence_lh', 'wordfence::ajax_lh_callback');
		add_action('wp_ajax_nopriv_wordfence_doScan', 'wordfence::ajax_doScan_callback');
		add_action('wp_ajax_nopriv_wordfence_testAjax', 'wordfence::ajax_testAjax_callback');
		if(wfUtils::hasLoginCookie()){ //may be logged in. Fast way to check. These aren't secure functions, this is just a perf optimization, along with every other use of hasLoginCookie()
			add_action('wp_ajax_wordfence_lh', 'wordfence::ajax_lh_callback');
			add_action('wp_ajax_wordfence_doScan', 'wordfence::ajax_doScan_callback');
			add_action('wp_ajax_wordfence_testAjax', 'wordfence::ajax_testAjax_callback');

			if (is_multisite()) {
				add_action('wp_network_dashboard_setup', 'wordfence::addDashboardWidget');
			} else {
				add_action('wp_dashboard_setup', 'wordfence::addDashboardWidget');
			}
		}

		add_action('wp_ajax_wordfence_wafStatus', 'wordfence::ajax_wafStatus_callback');
		add_action('wp_ajax_nopriv_wordfence_wafStatus', 'wordfence::ajax_wafStatus_callback');

		add_action('wp_ajax_nopriv_wordfence_remoteVerifySwitchTo2FANew', 'wordfence::ajax_remoteVerifySwitchTo2FANew_callback');

		add_action('wordfence_start_scheduled_scan', 'wordfence::wordfenceStartScheduledScan');
		add_action('wordfence_daily_cron', 'wordfence::dailyCron');
		add_action('wordfence_daily_autoUpdate', 'wfConfig::autoUpdate');
		add_action('wordfence_hourly_cron', 'wordfence::hourlyCron');
		add_action('wordfence_version_check', array(wfVersionCheckController::shared(), 'checkVersionsAndWarn'));
		add_action('plugins_loaded', 'wordfence::veryFirstAction');
		add_action('init', 'wordfence::initAction');
		//add_action('admin_bar_menu', 'wordfence::admin_bar_menu', 99);
		add_action('template_redirect', 'wordfence::templateRedir', 1001);
		add_action('shutdown', 'wordfence::shutdownAction');

		if (!wfConfig::get('ajaxWatcherDisabled_front')) {
			add_action('wp_enqueue_scripts', 'wordfence::enqueueAJAXWatcher');
		}
		if (!wfConfig::get('ajaxWatcherDisabled_admin')) {
			add_action('admin_enqueue_scripts', 'wordfence::enqueueAJAXWatcher');
		}

		//add_action('wp_enqueue_scripts', 'wordfence::enqueueDashboard');
		add_action('admin_enqueue_scripts', 'wordfence::enqueueDashboard');

		if(version_compare(PHP_VERSION, '5.4.0') >= 0){
			add_action('wp_authenticate','wordfence::authActionNew', 1, 2);
		} else {
			add_action('wp_authenticate','wordfence::authActionOld', 1, 2);
		}
		add_filter('authenticate', 'wordfence::authenticateFilter', 99, 3);

		$lockout = wfBlock::lockoutForIP(wfUtils::getIP());
		if ($lockout !== false) {
			add_filter('xmlrpc_enabled', '__return_false');
		}

		add_action('login_init','wordfence::loginInitAction');
		add_action('wp_login','wordfence::loginAction');
		add_action('wp_logout','wordfence::logoutAction');
		add_action('lostpassword_post', 'wordfence::lostPasswordPost', 1, 2);

		$allowSeparatePrompt = ini_get('output_buffering') > 0;
		if (wfConfig::get('loginSec_enableSeparateTwoFactor') && $allowSeparatePrompt) {
			add_action('login_form', 'wordfence::showTwoFactorField');
		}

		if(wfUtils::hasLoginCookie()){
			add_action('user_profile_update_errors', 'wordfence::validateProfileUpdate', 0, 3 );
			add_action('profile_update', 'wordfence::profileUpdateAction', 99, 2);
		}

		add_action('validate_password_reset', 'wordfence::validatePassword', 10, 2);

		// Add actions for the email summary
		add_action('wordfence_email_activity_report', array('wfActivityReport', 'executeCronJob'));

		//For debugging
		//add_filter( 'cron_schedules', 'wordfence::cronAddSchedules' );

		add_filter('wp_redirect', 'wordfence::wpRedirectFilter', 99, 2);
		add_filter('wp_redirect_status', 'wordfence::wpRedirectStatusFilter', 99, 2);
		//html|xhtml|atom|rss2|rdf|comment|export
		if(wfConfig::get('other_hideWPVersion')){
			add_filter('style_loader_src', 'wordfence::replaceVersion');
			add_filter('script_loader_src', 'wordfence::replaceVersion');

			add_action('upgrader_process_complete', 'wordfence::hideReadme');
		}
		add_filter('get_the_generator_html', 'wordfence::genFilter', 99, 2);
		add_filter('get_the_generator_xhtml', 'wordfence::genFilter', 99, 2);
		add_filter('get_the_generator_atom', 'wordfence::genFilter', 99, 2);
		add_filter('get_the_generator_rss2', 'wordfence::genFilter', 99, 2);
		add_filter('get_the_generator_rdf', 'wordfence::genFilter', 99, 2);
		add_filter('get_the_generator_comment', 'wordfence::genFilter', 99, 2);
		add_filter('get_the_generator_export', 'wordfence::genFilter', 99, 2);
		add_filter('registration_errors', 'wordfence::registrationFilter', 99, 3);
		add_filter('woocommerce_new_customer_data', 'wordfence::wooRegistrationFilter', 99, 1);

		if (wfConfig::get('loginSec_disableAuthorScan')) {
			add_filter('oembed_response_data', 'wordfence::oembedAuthorFilter', 99, 4);
			add_filter('rest_request_before_callbacks', 'wordfence::jsonAPIAuthorFilter', 99, 3);
			add_filter('rest_post_dispatch', 'wordfence::jsonAPIAdjustHeaders', 99, 3);
			add_filter('wp_sitemaps_users_pre_url_list', '__return_false', 99, 0);
			add_filter('wp_sitemaps_add_provider', 'wordfence::wpSitemapUserProviderFilter', 99, 2);
		}

		if (wfConfig::get('loginSec_disableApplicationPasswords')) {
			add_filter('wp_is_application_passwords_available', '__return_false');
			add_action('edit_user_profile', 'wordfence::showDisabledApplicationPasswordsMessage', -1);
			add_action('show_user_profile', 'wordfence::showDisabledApplicationPasswordsMessage', -1);

			// Override the wp_die handler to let the user know app passwords were disabled by the Wordfence option.
			if (!empty($_SERVER['SCRIPT_FILENAME']) && $_SERVER['SCRIPT_FILENAME'] === ABSPATH . 'wp-admin/authorize-application.php') {
				add_filter('wp_die_handler', function ($handler = null) {
					return function ($message, $title, $args) {
						if ($message === 'Application passwords are not available.') {
							$message = __('Application passwords have been disabled by Wordfence.', 'wordfence');
						}
						_default_wp_die_handler($message, $title, $args);
					};
				}, 10, 1);
			}
		}

		add_filter('rest_dispatch_request', 'wordfence::_filterCentralFromLiveTraffic', 99, 4);

		// Change GoDaddy's limit login mu-plugin since it can interfere with the two factor auth message.
		if (self::hasGDLimitLoginsMUPlugin()) {
			add_action('login_errors', array('wordfence', 'fixGDLimitLoginsErrors'), 11);
		}

		add_action('upgrader_process_complete', 'wordfence::_refreshVulnerabilityCache', 10, 2);
		add_action('upgrader_process_complete', 'wfUpdateCheck::syncAllVersionInfo');
		add_action('upgrader_process_complete', 'wordfence::_scheduleRefreshUpdateNotification', 99, 2);
		add_action('automatic_updates_complete', 'wordfence::_scheduleRefreshUpdateNotification', 99, 0);
		add_action('wordfence_refreshUpdateNotification', 'wordfence::_refreshUpdateNotification', 99, 0);
		add_action('wordfence_completeCoreUpdateNotification', 'wordfence::_completeCoreUpdateNotification', 99, 0);

		add_action('wfls_xml_rpc_blocked', 'wordfence::checkSecurityNetwork');
		add_action('wfls_registration_blocked', 'wordfence::checkSecurityNetwork');
		add_action('wfls_activation_page_footer', 'wordfence::_outputLoginSecurityTour');
		add_action('wfls_settings_set', 'wordfence::queueCentralConfigurationSync');

		if(is_admin()){
			add_action('admin_init', 'wordfence::admin_init');
			add_action('admin_head', 'wordfence::_retargetWordfenceSubmenuCallout');
			if(is_multisite()){
				if(wfUtils::isAdminPageMU()){
					add_action('network_admin_menu', 'wordfence::admin_menus', 10);
					add_action('network_admin_menu', 'wordfence::admin_menus_20', 20);
					add_action('network_admin_menu', 'wordfence::admin_menus_30', 30);
					add_action('network_admin_menu', 'wordfence::admin_menus_40', 40);
					add_action('network_admin_menu', 'wordfence::admin_menus_50', 50);
					add_action('network_admin_menu', 'wordfence::admin_menus_60', 60);
					add_action('network_admin_menu', 'wordfence::admin_menus_70', 70);
					add_action('network_admin_menu', 'wordfence::admin_menus_80', 80);
					add_action('network_admin_menu', 'wordfence::admin_menus_85', 85);
					add_action('network_admin_menu', 'wordfence::admin_menus_90', 90);
				} //else don't show menu
			} else {
				add_action('admin_menu', 'wordfence::admin_menus', 10);
				add_action('admin_menu', 'wordfence::admin_menus_20', 20);
				add_action('admin_menu', 'wordfence::admin_menus_30', 30);
				add_action('admin_menu', 'wordfence::admin_menus_40', 40);
				add_action('admin_menu', 'wordfence::admin_menus_50', 50);
				add_action('admin_menu', 'wordfence::admin_menus_60', 60);
				add_action('admin_menu', 'wordfence::admin_menus_70', 70);
				add_action('admin_menu', 'wordfence::admin_menus_80', 80);
				add_action('admin_menu', 'wordfence::admin_menus_85', 85);
				add_action('admin_menu', 'wordfence::admin_menus_90', 90);
			}
			add_filter('plugin_action_links_' . plugin_basename(realpath(dirname(__FILE__) . '/../wordfence.php')), 'wordfence::_pluginPageActionLinks');
			add_filter('pre_current_active_plugins', 'wordfence::registerDeactivationPrompt');
		}

		add_action('request', 'wordfence::preventAuthorNScans');
		add_action('password_reset', 'wordfence::actionPasswordReset');

		$adminUsers = new wfAdminUserMonitor();
		if ($adminUsers->isEnabled()) {
			add_action('set_user_role', array($adminUsers, 'updateToUserRole'), 10, 3);
			add_action('grant_super_admin', array($adminUsers, 'grantSuperAdmin'), 10, 1);
			add_action('revoke_super_admin', array($adminUsers, 'revokeSuperAdmin'), 10, 1);
		} else if (wfConfig::get_ser('adminUserList', false)) {
			// reset this in the event it's disabled or the network is too large
			wfConfig::set_ser('adminUserList', false);
		}

		if (wfConfig::liveTrafficEnabled()) {
			add_action('wp_head', 'wordfence::wfLogHumanHeader');
			add_action('login_head', 'wordfence::wfLogHumanHeader');
		}

		add_action('wordfence_processAttackData', 'wordfence::processAttackData');
		if (!empty($_GET['wordfence_syncAttackData']) && get_site_option('wordfence_syncingAttackData') <= time() - 60 && get_site_option('wordfence_lastSyncAttackData', 0) < time() - 8) {
			@ignore_user_abort(true);
			update_site_option('wordfence_syncingAttackData', time());
			header('Content-Type: text/javascript');
			define('WORDFENCE_SYNCING_ATTACK_DATA', true);
			add_action('init', 'wordfence::syncAttackData', 10, 0);
			add_filter('woocommerce_unforce_ssl_checkout', '__return_false');
		}

		add_action('wordfence_batchReportBlockedAttempts', 'wordfence::wfsnBatchReportBlockedAttempts');
		add_action('wordfence_batchReportFailedAttempts', 'wordfence::wfsnBatchReportFailedAttempts');

		add_action('wordfence_batchSendSecurityEvents', 'wfCentral::sendPendingSecurityEvents');

		if (wfConfig::get('other_hideWPVersion')) {
			add_filter('update_feedback', 'wordfence::restoreReadmeForUpgrade');
		}

		if (wfCentral::isConnected()) {
			add_action('wordfence_security_event', 'wfCentral::sendSecurityEvent', 10, 3);
		} else {
			add_action('wordfence_security_event', 'wfCentral::sendAlertCallback', 10, 3);
		}

		if (!wfConfig::get('wordfenceI18n', true)) {
			add_filter('gettext', function ($translation, $text, $domain) {
				if ($domain === 'wordfence') {
					return $text;
				}
				return $translation;
			}, 10, 3);
		}

		wfScanMonitor::registerActions();
		wfUpdateCheck::installPluginAPIFixer();
	}

	public static function registerDeactivationPrompt() {
		$deleteMain = (bool) wfConfig::get('deleteTablesOnDeact');
		$deleteLoginSecurity = (bool) \WordfenceLS\Controller_Settings::shared()->get('delete-deactivation');
		echo wfView::create(
			'offboarding/deactivation-prompt',
			array(
				'deactivationOption' => wfDeactivationOption::forState($deleteMain, $deleteLoginSecurity),
				'wafOptimized' => defined('WFWAF_AUTO_PREPEND') && WFWAF_AUTO_PREPEND && (!defined('WFWAF_SUBDIRECTORY_INSTALL') || !WFWAF_SUBDIRECTORY_INSTALL),
				'deactivate' => array_key_exists('wf_deactivate', $_GET)
			)
		)->render();
	}

	public static function fixWPMailFromAddress($from_email) {
		if ($from_email == 'wordpress@') { //$_SERVER['SERVER_NAME'] is undefined so we get an incomplete email address
			wordfence::status(4, 'info', __("wp_mail from address is incomplete, attempting to fix", 'wordfence'));
			$urls = array(get_site_url(), get_home_url());
			foreach ($urls as $u) {
				if (!empty($u)) {
					$u = preg_replace('#^[^/]*//+([^/]+).*$#', '\1', $u);
					if (substr($u, 0, 4) == 'www.') {
						$u = substr($u, 4);
					}

					if (!empty($u)) {
						wordfence::status(4, 'info', sprintf(/* translators: Email address. */ __("Fixing wp_mail from address: %s", 'wordfence'), $from_email . $u));
						return $from_email . $u;
					}
				}
			}

			//Can't fix it, return it as it was
		}
		return $from_email;
	}

	private static function isWordfencePage($includeWfls = true) {
		return (isset($_GET['page']) && (preg_match('/^Wordfence/', @$_GET['page']) || ($includeWfls && $_GET['page'] == 'WFLS' && wfOnboardingController::shouldShowNewTour(wfOnboardingController::TOUR_LOGIN_SECURITY))));
	}

	private static function isWordfenceSubpage($page, $subpage) {
		return array_key_exists('page', $_GET) && $_GET['page'] == ('Wordfence' . ucfirst($page)) && array_key_exists('subpage', $_GET) && $_GET['subpage'] == $subpage;
	}
	public static function ajax_testAjax_callback(){
		die("WFSCANTESTOK");
	}
	public static function ajax_doScan_callback(){
		@ignore_user_abort(true);
		self::$wordfence_wp_version = false;
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		//This is messy, but not sure of a better way to do this without guaranteeing we get $wp_version
		require(ABSPATH . 'wp-includes/version.php'); /** @var string $wp_version */
		self::$wordfence_wp_version = $wp_version;
		require_once(dirname(__FILE__) . '/wfScan.php');
		wfScan::wfScanMain();

	} //END doScan
	public static function ajax_lh_callback(){
		self::getLog()->canLogHit = false;
		$UA = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$isCrawler = empty($UA);
		if ($UA) {
			if (wfCrawl::isCrawler($UA) || wfCrawl::isGoogleCrawler()) {
				$isCrawler = true;
			}
		}

		@ob_end_clean();
		if(! headers_sent()){
			header('Content-type: text/javascript');
			header("Connection: close");
			header("Content-Length: 0");
			header("X-Robots-Tag: noindex");
			if (!$isCrawler) {
				wfLog::cacheHumanRequester(wfUtils::getIP(), $UA);
			}
		}
		flush();
		if(!$isCrawler && array_key_exists('hid', $_GET)){
			$hid = $_GET['hid'];
			$hid = wfUtils::decrypt($hid);
			if(! preg_match('/^\d+$/', $hid)){ exit(); }
			$db = new wfDB();
			$table_wfHits = wfDB::networkTable('wfHits');
			$db->queryWrite("update {$table_wfHits} set jsRun=1 where id=%d", $hid);
		}
		die("");
	}
	public static function ajaxReceiver(){
		if(! wfUtils::isAdmin()){
			wfUtils::send_json(array('errorMsg' => __("You appear to have logged out or you are not an admin. Please sign-out and sign-in again.", 'wordfence')));
		}
		$func = (isset($_POST['action']) && $_POST['action']) ? $_POST['action'] : $_GET['action'];
		$nonce = (isset($_POST['nonce']) && $_POST['nonce']) ? $_POST['nonce'] : $_GET['nonce'];
		if(! wp_verify_nonce($nonce, 'wp-ajax')){
			wfUtils::send_json(array('errorMsg' => __("Your browser sent an invalid security token to Wordfence. Please try reloading this page or signing out and in again.", 'wordfence'), 'tokenInvalid' => 1));
		}
		//func is e.g. wordfence_ticker so need to munge it
		$func = str_replace('wordfence_', '', $func);
		$returnArr = call_user_func('wordfence::ajax_' . $func . '_callback');
		if($returnArr === false){
			$returnArr = array('errorMsg' => __("Wordfence encountered an internal error executing that request.", 'wordfence'));
		}

		if(! is_array($returnArr)){
			error_log("Function " . wp_kses($func, array()) . " did not return an array and did not generate an error.");
			$returnArr = array();
		}
		if(isset($returnArr['nonce'])){
			error_log("Wordfence ajax function return an array with 'nonce' already set. This could be a bug.");
		}
		$returnArr['nonce'] = wp_create_nonce('wp-ajax');
		wfUtils::send_json($returnArr);
	}

	public static function getWPFileContent($file, $cType, $cName, $cVersion){
		if ($cType == 'plugin') {
			if (preg_match('#^/?wp-content/plugins/[^/]+/#', $file)) {
				$file = preg_replace('#^/?wp-content/plugins/[^/]+/#', '', $file);
			}
			else {
				//If user is using non-standard wp-content dir, then use /plugins/ in pattern to figure out what to strip off
				$file = preg_replace('#^.*[^/]+/plugins/[^/]+/#', '', $file);
			}
		}
		else if ($cType == 'theme') {
			if (preg_match('#/?wp-content/themes/[^/]+/#', $file)) {
				$file = preg_replace('#/?wp-content/themes/[^/]+/#', '', $file);
			}
			else {
				$file = preg_replace('#^.*[^/]+/themes/[^/]+/#', '', $file);
			}
		}
		else if ($cType == 'core') {
			//No special processing
		}
		else {
			return array('errorMsg' => __('An invalid type was specified to get file.', 'wordfence'));
		}

		$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		try {
			$contResult = $api->binCall('get_wp_file_content', array(
				'v' => wfUtils::getWPVersion(),
				'file' => $file,
				'cType' => $cType,
				'cName' => $cName,
				'cVersion' => $cVersion
			));
			if ($contResult['data']) {
				return array('fileContent' => $contResult['data']);
			}

			throw new Exception(__('We could not fetch a core WordPress file from the Wordfence API.', 'wordfence'));
		}
		catch (Exception $e) {
			return array('errorMsg' => wp_kses($e->getMessage(), array()));
		}
	}
	public static function ajax_checkHtaccess_callback(){
		if(wfUtils::isNginx()){
			return array('nginx' => 1);
		}
		$file = wfCache::getHtaccessPath();
		if(! $file){
			return array('err' => __("We could not find your .htaccess file to modify it.", 'wordfence'));
		}
		$fh = @fopen($file, 'r+');
		if(! $fh){
			$err = error_get_last();
			return array('err' => sprintf(/* translators: Error message. */ __("We found your .htaccess file but could not open it for writing: %s", 'wordfence'), $err['message']));
		}
		return array('ok' => 1);
	}
	public static function ajax_downloadHtaccess_callback(){
		$url = site_url();
		$url = preg_replace('/^https?:\/\//i', '', $url);
		$url = preg_replace('/[^a-zA-Z0-9\.]+/', '_', $url);
		$url = preg_replace('/^_+/', '', $url);
		$url = preg_replace('/_+$/', '', $url);
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="htaccess_Backup_for_' . $url . '.txt"');
		$file = wfCache::getHtaccessPath();
		readfile($file);
		die();
	}
	public static function ajax_downloadLogFile_callback() {
		if (!isset($_GET['logfile'])) {
			status_header(400);
			nocache_headers();
			exit;
		}

		wfErrorLogHandler::outputErrorLog(stripslashes($_GET['logfile'])); //exits
	}
	public static function ajax_createBlock_callback() {
		$offset = 0;
		if (isset($_POST['offset'])) {
			$offset = (int) $_POST['offset'];
		}

		$sortColumn = 'type';
		if (isset($_POST['sortColumn']) && in_array($_POST['sortColumn'], array('type', 'detail', 'ruleAdded', 'reason', 'expiration', 'blockCount', 'lastAttempt'))) {
			$sortColumn = $_POST['sortColumn'];
		}

		$sortDirection = 'ascending';
		if (isset($_POST['sortDirection']) && in_array($_POST['sortDirection'], array('ascending', 'descending'))) {
			$sortDirection = $_POST['sortDirection'];
		}

		$filter = '';
		if (isset($_POST['blocksFilter'])) {
			$filter = $_POST['blocksFilter'];
		}

		if (!empty($_POST['payload']) && ($payload = json_decode(stripslashes($_POST['payload']), true)) !== false) {
			try {
				$error = wfBlock::validate($payload);
				if ($error !== true) {
					return array(
						'error' => $error,
					);
				}

				wfBlock::create($payload);
				$hasCountryBlock = false;
				$blocks = self::_blocksAJAXReponse($hasCountryBlock, $offset, $sortColumn, $sortDirection, $filter);
				return array('success' => true, 'blocks' => $blocks, 'hasCountryBlock' => $hasCountryBlock);
			}
			catch (Exception $e) {
				return array(
					'error' => __('An error occurred while creating the block.', 'wordfence'),
				);
			}
		}

		return array(
			'error' => __('No block parameters were provided.', 'wordfence'),
		);
	}
	public static function ajax_deleteBlocks_callback() {
		$offset = 0;
		if (isset($_POST['offset'])) {
			$offset = (int) $_POST['offset'];
		}

		$sortColumn = 'type';
		if (isset($_POST['sortColumn']) && in_array($_POST['sortColumn'], array('type', 'detail', 'ruleAdded', 'reason', 'expiration', 'blockCount', 'lastAttempt'))) {
			$sortColumn = $_POST['sortColumn'];
		}

		$sortDirection = 'ascending';
		if (isset($_POST['sortDirection']) && in_array($_POST['sortDirection'], array('ascending', 'descending'))) {
			$sortDirection = $_POST['sortDirection'];
		}

		$filter = '';
		if (isset($_POST['blocksFilter'])) {
			$filter = $_POST['blocksFilter'];
		}

		if (!empty($_POST['blocks']) && ($blocks = json_decode(stripslashes($_POST['blocks']), true)) !== false && is_array($blocks)) {
			$removed = wfBlock::removeBlockIDs($blocks, true); //wfBlock::removeBlockIDs sanitizes the array
			if($removed!==false) {
				foreach($removed as $block) {
					self::clearLockoutCounters(wfUtils::inet_ntop($block->IP));
				}
			}
			$hasCountryBlock = false;
			$blocks = self::_blocksAJAXReponse($hasCountryBlock, $offset, $sortColumn, $sortDirection, $filter);
			return array('success' => true, 'blocks' => $blocks, 'hasCountryBlock' => $hasCountryBlock);
		}

		return array(
			'error' => __('No blocks were provided.', 'wordfence'),
		);
	}
	public static function ajax_makePermanentBlocks_callback() {
		$offset = 0;
		if (isset($_POST['offset'])) {
			$offset = (int) $_POST['offset'];
		}

		$sortColumn = 'type';
		if (isset($_POST['sortColumn']) && in_array($_POST['sortColumn'], array('type', 'detail', 'ruleAdded', 'reason', 'expiration', 'blockCount', 'lastAttempt'))) {
			$sortColumn = $_POST['sortColumn'];
		}

		$sortDirection = 'ascending';
		if (isset($_POST['sortDirection']) && in_array($_POST['sortDirection'], array('ascending', 'descending'))) {
			$sortDirection = $_POST['sortDirection'];
		}

		$filter = '';
		if (isset($_POST['blocksFilter'])) {
			$filter = $_POST['blocksFilter'];
		}

		if (!empty($_POST['updates']) && ($updates = json_decode(stripslashes($_POST['updates']), true)) !== false && is_array($updates)) {
			wfBlock::makePermanentBlockIDs($updates); //wfBlock::makePermanentBlockIDs sanitizes the array
			$hasCountryBlock = false;
			$blocks = self::_blocksAJAXReponse($hasCountryBlock, $offset, $sortColumn, $sortDirection, $filter);
			return array('success' => true, 'blocks' => $blocks, 'hasCountryBlock' => $hasCountryBlock);
		}

		return array(
			'error' => __('No blocks were provided.', 'wordfence'),
		);
	}
	public static function ajax_installLicense_callback() {
		if (!empty($_POST['license'])) {
			$statusChange = array_key_exists('status_change', $_POST) ? filter_var($_POST['status_change'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;
			$license = strtolower(trim($_POST['license']));
			if (!preg_match('/^[a-fA-F0-9]+$/', $license)) {
				return array(
					'error' => __('The license key entered is not in a valid format. It must contain only numbers and the letters A-F.', 'wordfence'),
				);
			}

			$existingLicense = strtolower(wfConfig::get('apiKey', ''));
			if ($existingLicense != $license) { //Key changed, try activating
				$api = new wfAPI($license, wfUtils::getWPVersion());
				try {
					$parameters = array();
					if (!empty($existingLicense))
						$parameters['previousLicense'] = $existingLicense;
					$res = $api->call('check_api_key', array(), $parameters);
					if ($res['ok'] && isset($res['isPaid'])) {
						$isPaid = wfUtils::truthyToBoolean($res['isPaid']);
						wfConfig::set('apiKey', $license);
						wfConfig::set('isPaid', $isPaid); //res['isPaid'] is boolean coming back as JSON and turned back into PHP struct. Assuming JSON to PHP handles bools.
						if ($statusChange !== false) {
							self::licenseStatusChanged();
						}
						if (!$isPaid) {
							wfConfig::set('keyType', wfLicense::KEY_TYPE_FREE);
						}
						self::scheduleCrons();
						return array(
							'success' => 1,
							'isPaid' => wfConfig::get('isPaid') ? 1 : 0,
							'type' => wfLicense::current()->getType()
						);
					}
					else if (isset($res['_hasKeyConflict']) && $res['_hasKeyConflict']) {
						return array(
							'error' => __('The license provided is already in use on another site.', 'wordfence'),
						);
					}
					else {
						return array(
							'error' => __('The Wordfence activation server returned an unexpected response. Please try again.', 'wordfence'),
						);
					}
				}
				catch (Exception $e) {
					return array(
						'error' => __('We received an error while trying to activate the license with the Wordfence servers: ', 'wordfence') . wp_kses($e->getMessage(), array())
					);
				}
			}
			else {
				if ($statusChange === true) {
					self::licenseStatusChanged();
				}
				return array(
					'success' => 1,
					'isPaid' => wfConfig::get('isPaid') ? 1 : 0,
					'type' => wfLicense::current()->getType()
				);
			}
		}

		return array(
			'error' => __('No license was provided to install.', 'wordfence'),
		);
	}
	public static function ajax_recordTOUPP_callback() {
		$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		$result = $api->call('record_toupp', array(), array());
		wfConfig::set('touppBypassNextCheck', 1); //In case this call kicks off the cron that checks, this avoids the race condition of that setting the prompt as being needed at the same time we've just recorded it as accepted
		wfConfig::set('touppPromptNeeded', 0);
		return array(
			'success' => 1,
		);
	}
	public static function ajax_mailingSignup_callback() {
		if (isset($_POST['emails'])) {
			$emails = @json_decode(stripslashes($_POST['emails']), true);
			if (is_array($emails) && count($emails)) {
				$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
				$result = $api->call('mailing_signup', array(), array('signup' => json_encode(array('emails' => $emails)), 'ip' => wfUtils::getIP()));
			}
		}

		return array(
			'success' => 1,
		);
	}
	public static function ajax_enableAllOptionsPage_callback() {
		wfConfig::set('displayTopLevelOptions', 1);
		$n = wfNotification::getNotificationForCategory('wfplugin_devalloptions');
		if ($n !== null) {
			$n->markAsRead();
		}

		$response = array('success' => true);
		if (function_exists('network_admin_url') && is_multisite()) {
			$response['redirect'] = network_admin_url('admin.php?page=WordfenceOptions');
		}
		else {
			$response['redirect'] = admin_url('admin.php?page=WordfenceOptions');
		}

		return $response;
	}
	public static function ajax_restoreDefaults_callback() {
		if (!empty($_POST['section'])) {
			if (wfConfig::restoreDefaults($_POST['section'])) {
				return array(
					'success' => true,
				);
			}
			else {
				return array(
					'error' => __('An unknown configuration section was provided.', 'wordfence'),
				);
			}
		}

		return array(
			'error' => __('No configuration section was provided.', 'wordfence'),
		);
	}
	public static function ajax_saveOptions_callback() {
		if (!empty($_POST['changes']) && ($changes = json_decode(stripslashes($_POST['changes']), true)) !== false) {
			try {
				$errors = wfConfig::validate($changes);
				if ($errors !== true) {
					if (count($errors) == 1) {
						return array(
							'error' => sprintf(/* translators: Error message. */ __('An error occurred while saving the configuration: %s', 'wordfence'), $errors[0]['error']),
						);
					}
					else if (count($errors) > 1) {
						$compoundMessage = array();
						foreach ($errors as $e) {
							$compoundMessage[] = $e['error'];
						}
						return array(
							'error' => sprintf(/* translators: Error message. */ __('Errors occurred while saving the configuration: %s', 'wordfence'), implode(', ', $compoundMessage)),
						);
					}

					return array(
						'error' => __('Errors occurred while saving the configuration.', 'wordfence'),
					);
				}

				wfConfig::save(wfConfig::clean($changes));

				$response = array('success' => true);
				if (!empty($_POST['page']) && preg_match('/^Wordfence/i', $_POST['page'])) {
					if ($_POST['page'] == 'WordfenceOptions' && isset($changes['displayTopLevelOptions']) && !wfUtils::truthyToBoolean($changes['displayTopLevelOptions'])) {
						if (function_exists('network_admin_url') && is_multisite()) {
							$response['redirect'] = network_admin_url('admin.php?page=Wordfence');
						}
						else {
							$response['redirect'] = admin_url('admin.php?page=Wordfence');
						}
					}
				}

				return $response;
			}
			catch (wfWAFStorageFileException $e) {
				return array(
					'error' => __('An error occurred while saving the configuration.', 'wordfence'),
				);
			}
			catch (wfWAFStorageEngineMySQLiException $e) {
				return array(
					'error' => __('An error occurred while saving the configuration.', 'wordfence'),
				);
			}
			catch (Exception $e) {
				return array(
					'error' => $e->getMessage(),
				);
			}
		}

		return array(
			'error' => __('No configuration changes were provided to save.', 'wordfence'),
		);
	}

	public static function ajax_setDeactivationOption_callback() {
		$key = array_key_exists('option', $_POST) ? $_POST['option'] : null;
		$option = wfDeactivationOption::forKey($key);
		if ($option === null) {
			return array(
				'error' => __('Invalid option specified', 'wordfence')
			);
		}
		wfConfig::set('deleteTablesOnDeact', $option->deletesMain());
		\WordfenceLS\Controller_Settings::shared()->set('delete-deactivation', $option->deletesLoginSecurity());
		return array(
			'success' => true
		);
	}

	public static function ajax_updateIPPreview_callback() {
		$howGet = $_POST['howGetIPs'];

		$validIPs = array();
		$invalidIPs = array();
		$testIPs = preg_split('/[\r\n,]+/', $_POST['howGetIPs_trusted_proxies']);
		foreach ($testIPs as $val) {
			if (strlen($val) > 0) {
				if (wfUtils::isValidIP($val) || wfUtils::isValidCIDRRange($val)) {
					$validIPs[] = $val;
				}
				else {
					$invalidIPs[] = $val;
				}
			}
		}
		$trustedProxies = $validIPs;

		$preset = $_POST['howGetIPs_trusted_proxy_preset'];
		$presets = wfConfig::getJSON('ipResolutionList', array());
		if (is_array($presets) && isset($presets[$preset])) {
			$testIPs = array_merge($presets[$preset]['ipv4'], $presets[$preset]['ipv6']);
			foreach ($testIPs as $val) {
				if (strlen($val) > 0) {
					if (wfUtils::isValidIP($val) || wfUtils::isValidCIDRRange($val)) {
						$trustedProxies[] = $val;
					}
				}
			}
		}

		$ipAll = wfUtils::getIPPreview($howGet, $trustedProxies);
		$ip = wfUtils::getIPForField($howGet, $trustedProxies);
		return array('ok' => 1, 'ip' => $ip, 'ipAll' => $ipAll, 'resolvedProxies' => $trustedProxies);
	}

	public static function ajax_hideFileHtaccess_callback(){
		$issues = new wfIssues();
		$issue  = $issues->getIssueByID((int) $_POST['issueID']);
		if (!$issue) {
			return array('errorMsg' => __("We could not find that issue in our database.", 'wordfence'));
		}

		if (!function_exists('get_home_path')) {
			include_once(ABSPATH . 'wp-admin/includes/file.php');
		}

		$homeURL = get_home_url();
		$components = parse_url($homeURL);
		if ($components === false) {
			return array('errorMsg' => __("An error occurred while trying to hide the file.", 'wordfence'));
		}

		$sitePath = '';
		if (isset($components['path'])) {
			$sitePath = trim($components['path'], '/');
		}

		$homePath = wfUtils::getHomePath();
		$file = $issue['data']['file'];
		$localFile = ABSPATH . '/' . $file; //The scanner uses ABSPATH as its base rather than get_home_path()
		$localFile = realpath($localFile);
		if (strpos($localFile, $homePath) !== 0) {
			return array('errorMsg' => __("An invalid file was requested for hiding.", 'wordfence'));
		}
		$localFile = substr($localFile, strlen($homePath));
		$absoluteURIPath = trim($sitePath . '/' . $localFile, '/');
		$regexLocalFile = preg_replace('#/#', '/+', preg_quote($absoluteURIPath));
		$filename = basename($localFile);

		$htaccessContent = <<<HTACCESS
<IfModule mod_rewrite.c>
        RewriteEngine On
        RewriteCond %{REQUEST_URI} ^/?{$regexLocalFile}$
        RewriteRule .* - [F,L,NC]
</IfModule>
<IfModule !mod_rewrite.c>
	<Files "{$filename}">
	<IfModule mod_authz_core.c>
		Require all denied
	</IfModule>
	<IfModule !mod_authz_core.c>
		Order deny,allow
		Deny from all
	</IfModule>
	</Files>
</IfModule>
HTACCESS;

		if (!wfUtils::htaccessPrepend($htaccessContent)) {
			return array('errorMsg' => __("You don't have permission to repair .htaccess. You need to either fix the file manually using FTP or change the file permissions and ownership so that your web server has write access to repair the file.", 'wordfence'));
		}
		$issues->updateIssue((int) $_POST['issueID'], 'delete');
		wfScanEngine::refreshScanNotification($issues);
		$counts = $issues->getIssueCounts();
		return array(
			'ok' => 1,
			'issueCounts' => $counts,
		);
	}
	public static function ajax_unlockOutIP_callback(){
		$IP = $_POST['IP'];
		wfBlock::unlockOutIP($IP);
		self::clearLockoutCounters($IP);
		return array('ok' => 1);
	}
	public static function ajax_unblockIP_callback(){
		$IP = $_POST['IP'];
		wfBlock::unblockIP($IP);
		self::clearLockoutCounters($IP);
		return array('ok' => 1);
	}
	public static function ajax_permBlockIP_callback(){
		$IP = $_POST['IP'];
		wfBlock::createIP(__('Manual permanent block by admin', 'wordfence'), $IP, wfBlock::DURATION_FOREVER, time(), false, 0, wfBlock::TYPE_IP_MANUAL);
		return array('ok' => 1);
	}
	public static function ajax_unblockRange_callback(){
		$id = trim($_POST['id']);
		wfBlock::removeBlockIDs(array($id));
		return array('ok' => 1);
	}

	public static function ajax_whois_callback(){
		$val = trim($_POST['val']);
		$val = preg_replace('/[^a-zA-Z0-9\.\-:]+/', '', $val);
		$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
		try {
			$result = $api->call('whois', array(), array(
				'val' => $val,
			));
			return array('ok' => 1, 'result' => $result['result']);
		}
		catch (wfAPICallErrorResponseException $e) {
			return array('ok' => 0);
		}
	}
	public static function ajax_recentTraffic_callback(){
		$ip = trim($_POST['ip']);
		try {
			$response = self::IPTraf($ip);
			$reverseLookup = $response['reverseLookup'];
			$results = $response['results'];
			ob_start();
			require(dirname(__FILE__) . '/IPTrafList.php');
			$content = ob_get_clean();
			return array('ok' => 1, 'result' => $content);
		} catch (InvalidArgumentException $e) {
			return array('errorMsg' => $e->getMessage());
		}
	}
	public static function ajax_blockIP_callback() {
		$IP = trim($_POST['IP']);
		$perm = (isset($_POST['perm']) && $_POST['perm'] == '1') ? wfBlock::DURATION_FOREVER : wfConfig::getInt('blockedTime');
		if (!wfUtils::isValidIP($IP)) {
			return array('err' => 1, 'errorMsg' => __("Please enter a valid IP address to block.", 'wordfence'));
		}
		if ($IP == wfUtils::getIP()) {
			return array('err' => 1, 'errorMsg' => __("You can't block your own IP address.", 'wordfence'));
		}
		$forcedWhitelistEntry = false;
		if (wfBlock::isWhitelisted($IP, $forcedWhitelistEntry)) {
			$message = sprintf(/* translators: IP address. */ __("The IP address %s is allowlisted and can't be blocked. You can remove this IP from the allowlist on the Wordfence options page.", 'wordfence'), wp_kses($IP, array()));
			if ($forcedWhitelistEntry) {
				$message = sprintf(/* translators: IP address. */ __("The IP address %s is in a range of IP addresses that Wordfence does not block. The IP range may be internal or belong to a service safe to allow access for.", 'wordfence'), wp_kses($IP, array()));
			}
			return array('err' => 1, 'errorMsg' => $message);
		}
		if (wfConfig::get('neverBlockBG') != 'treatAsOtherCrawlers') { //Either neverBlockVerified or neverBlockUA is selected which means the user doesn't want to block google
			if (wfCrawl::isVerifiedGoogleCrawler($IP)) {
				return array('err' => 1, 'errorMsg' => __("The IP address you're trying to block belongs to Google. Your options are currently set to not block these crawlers. Change this in Wordfence options if you want to manually block Google.", 'wordfence'));
			}
		}
		wfBlock::createIP($_POST['reason'], $IP, $perm);
		wfActivityReport::logBlockedIP($IP, null, 'manual');
		return array('ok' => 1);
	}
	public static function ajax_avatarLookup_callback() {
		$ids = explode(',', $_POST['ids']);
		$res = array();
		foreach ($ids as $id) {
			$avatar = get_avatar($id, 16);
			if ($avatar) {
				$res[$id] = $avatar;
			}
		}
		return array('ok' => 1, 'avatars' => $res);
	}
	public static function ajax_reverseLookup_callback(){
		$ips = explode(',', $_POST['ips']);
		$res = array();
		foreach($ips as $ip){
			$res[$ip] = wfUtils::reverseLookup($ip);
		}
		return array('ok' => 1, 'ips' => $res);
	}
	public static function ajax_deleteIssue_callback(){
		$wfIssues = new wfIssues();
		$issueID = $_POST['id'];
		$wfIssues->deleteIssue($issueID);
		wfScanEngine::refreshScanNotification($wfIssues);
		return array('ok' => 1);
	}
	public static function ajax_updateAllIssues_callback(){
		$op = $_POST['op'];
		$i = new wfIssues();
		if($op == 'deleteIgnored'){
			$i->deleteIgnored();
		} else if($op == 'deleteNew'){
			$i->deleteNew();
		} else if($op == 'ignoreAllNew'){
			$i->ignoreAllNew();
		} else {
			return array('errorMsg' => __("An invalid operation was called.", 'wordfence'));
		}
		wfScanEngine::refreshScanNotification($i);
		return array('ok' => 1);
	}
	public static function ajax_updateIssueStatus_callback(){
		$wfIssues = new wfIssues();
		$status = $_POST['status'];
		$issueID = $_POST['id'];
		if(! preg_match('/^(?:new|delete|ignoreP|ignoreC)$/', $status)){
			return array('errorMsg' => __("An invalid status was specified when trying to update that issue.", 'wordfence'));
		}
		$wfIssues->updateIssue($issueID, $status);
		wfScanEngine::refreshScanNotification($wfIssues);

		$counts = $wfIssues->getIssueCounts();
		return array(
			'ok' => 1,
			'issueCounts' => $counts,
		);
	}
	public static function ajax_killScan_callback(){
		wordfence::status(1, 'info', __("Scan stop request received.", 'wordfence'));
		wordfence::status(10, 'info', 'SUM_KILLED:' . __("A request was received to stop the previous scan.", 'wordfence'));
		wfUtils::clearScanLock(); //Clear the lock now because there may not be a scan running to pick up the kill request and clear the lock
		wfScanEngine::requestKill();
		wfConfig::remove('scanStartAttempt');
		wfConfig::set('lastScanFailureType', false);
		return array(
			'ok' => 1,
		);
	}
	public static function ajax_loadIssues_callback(){
		$offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
		$limit = isset($_POST['limit']) ? intval($_POST['limit']) : WORDFENCE_SCAN_ISSUES_PER_PAGE;
		$ignoredOffset = isset($_POST['ignoredOffset']) ? intval($_POST['ignoredOffset']) : 0;
		$ignoredLimit = isset($_POST['ignoredLimit']) ? intval($_POST['ignoredLimit']) : WORDFENCE_SCAN_ISSUES_PER_PAGE;

		$issues = wfIssues::shared()->getIssues($offset, $limit, $ignoredOffset, $ignoredLimit);
		$issueCounts = array_merge(array('new' => 0, 'ignoreP' => 0, 'ignoreC' => 0), wfIssues::shared()->getIssueCounts());

		return array(
			'issues' => $issues,
			'issueCounts' => $issueCounts,
		);
	}
	public static function ajax_ticker_callback() {
		$wfdb = new wfDB();
		$table_wfStatus = wfDB::networkTable('wfStatus');
		$serverTime = $wfdb->querySingle("select unix_timestamp()");
		$jsonData = array(
			'serverTime' => $serverTime,
			'serverMicrotime' => microtime(true),
			'msg' => wp_kses_data((string) $wfdb->querySingle("SELECT msg FROM {$table_wfStatus} WHERE level < 3 AND ctime > (UNIX_TIMESTAMP() - 3600) ORDER BY ctime DESC LIMIT 1")),
		);
		$events = array();
		if (get_site_option('wordfence_syncAttackDataAttempts') > 10) {
			self::syncAttackData(false);
		}
		$results = self::ajax_loadLiveTraffic_callback();
		$events = $results['data'];
		if (isset($results['sql'])) {
			$jsonData['sql'] = $results['sql'];
		}

		$jsonData['events'] = $events;
		return $jsonData;
	}
	public static function ajax_activityLogUpdate_callback() {
		global $wpdb;
		$statusTable = wfDB::networkTable('wfStatus');
		$row = $wpdb->get_row("SELECT ctime, msg FROM {$statusTable} WHERE level < 3 AND ctime > (UNIX_TIMESTAMP() - 3600) ORDER BY ctime DESC LIMIT 1", ARRAY_A);
		$lastMessage = __('Idle', 'wordfence');

		$lastScanCompleted = wfConfig::get('lastScanCompleted');
		if ($row) {
			$lastMessage = '[' . strtoupper(wfUtils::formatLocalTime('M d H:i:s', $row['ctime'])) . '] ' . wp_kses_data($row['msg']);
		}
		else if ($lastScanCompleted == 'ok') {
			$scanLastCompletion = (int) wfScanner::shared()->lastScanTime();
			if ($scanLastCompletion) {
				$lastMessage = sprintf(/* translators: Localized date. */ __('Scan completed on %s', 'wordfence'), wfUtils::formatLocalTime(get_option('date_format') . ' ' . get_option('time_format'), $scanLastCompletion));
			}
		}
		else if ($lastScanCompleted === false || empty($lastScanCompleted)) {
			//Do nothing
		}
		else {
			$lastMessage = __('Last scan failed', 'wordfence');
		}

		$issues = wfIssues::shared();
		$scanFailed = $issues->hasScanFailed();

		$scanner = wfScanner::shared();
		$stages = $scanner->stageStatus();
		foreach ($stages as $key => &$value) {
			switch ($value) {
				case wfScanner::STATUS_PENDING:
					$value = 'wf-scan-step';
					break;
				case wfScanner::STATUS_RUNNING:
				case wfScanner::STATUS_RUNNING_WARNING:
					if ($scanFailed) {
						$value = 'wf-scan-step';
						break;
					}
					$value = 'wf-scan-step wf-scan-step-running';
					break;
				case wfScanner::STATUS_COMPLETE_SUCCESS:
					$value = 'wf-scan-step wf-scan-step-complete-success';
					break;
				case wfScanner::STATUS_COMPLETE_WARNING:
					$value = 'wf-scan-step wf-scan-step-complete-warning';
					break;
				case wfScanner::STATUS_PREMIUM:
					$value = 'wf-scan-step wf-scan-step-premium';
					break;
				case wfScanner::STATUS_DISABLED:
					$value = 'wf-scan-step wf-scan-step-disabled';
					break;
			}
		}

		$stats = array(
			'wf-scan-results-stats-postscommentsfiles' => $scanner->getSummaryItem(wfScanner::SUMMARY_SCANNED_POSTS, 0) + $scanner->getSummaryItem(wfScanner::SUMMARY_SCANNED_COMMENTS, 0) + $scanner->getSummaryItem(wfScanner::SUMMARY_SCANNED_FILES, 0),
			'wf-scan-results-stats-themesplugins' => $scanner->getSummaryItem(wfScanner::SUMMARY_SCANNED_PLUGINS, 0) + $scanner->getSummaryItem(wfScanner::SUMMARY_SCANNED_THEMES, 0),
			'wf-scan-results-stats-users' => $scanner->getSummaryItem(wfScanner::SUMMARY_SCANNED_USERS, 0),
			'wf-scan-results-stats-urls' => $scanner->getSummaryItem(wfScanner::SUMMARY_SCANNED_URLS, 0),
			'wf-scan-results-stats-issues' => $issues->getIssueCount(),
		);

		$lastIssueUpdateTimestamp = wfIssues::shared()->getLastIssueUpdateTimestamp();
		$issues = 0;
		$issueCounts = array_merge(array('new' => 0, 'ignoreP' => 0, 'ignoreC' => 0), wfIssues::shared()->getIssueCounts());
		if ($lastIssueUpdateTimestamp > $_POST['lastissuetime']) {
			$issues = wfIssues::shared()->getIssues(0, WORDFENCE_SCAN_ISSUES_PER_PAGE, 0, WORDFENCE_SCAN_ISSUES_PER_PAGE);
		}

		$timeLimit = intval(wfConfig::get('scan_maxDuration'));
		if ($timeLimit < 1) {
			$timeLimit = WORDFENCE_DEFAULT_MAX_SCAN_TIME;
		}

		$scanFailedHTML = '';
		switch ($scanFailed) {
			case wfIssues::SCAN_FAILED_TIMEOUT:
				$scanFailedSeconds = time() - wfIssues::lastScanStatusUpdate();
				$scanFailedTiming = wfUtils::makeTimeAgo($scanFailedSeconds);

				if ($scanFailedSeconds > $timeLimit) {
					$scanFailedTiming = sprintf(/* translators: Time until. */ __('more than %s', 'wordfence'), wfUtils::makeTimeAgo($timeLimit));
				}

				$scanFailedHTML = wfView::create('scanner/scan-failed', array(
					'messageHTML' => sprintf(/* translators: Localized date. */ __('The current scan looks like it has failed. Its last status update was <span id="wf-scan-failed-time-ago">%s</span> ago. You may continue to wait in case it resumes or stop and restart the scan. Some sites may need adjustments to run scans reliably.', 'wordfence'), $scanFailedTiming) . ' <a href="' . wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_FAILS) . '" target="_blank" rel="noopener noreferrer">' . __('Click here for steps you can try.', 'wordfence') . '<span class="screen-reader-text"> (' . esc_html__('opens in new tab', 'wordfence') . ')</span></a>',
					'buttonTitle' => __('Cancel Scan', 'wordfence'),
				))->render();

				break;
			case wfIssues::SCAN_FAILED_FORK_FAILED:
			case wfIssues::SCAN_FAILED_GENERAL:
				$scanFailedHTML = wfView::create('scanner/scan-failed', array(
					'messageHTML' => __('The previous scan has failed. Some sites may need adjustments to run scans reliably.', 'wordfence') . ' <a href="' . wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_FAILS) . '" target="_blank" rel="noopener noreferrer">' . __('Click here for steps you can try.', 'wordfence') . '<span class="screen-reader-text"> (' . esc_html__('opens in new tab', 'wordfence') . ')</span></a>',
					'buttonTitle' => __('Close', 'wordfence'),
				))->render();
				break;
			case wfIssues::SCAN_FAILED_DURATION_REACHED:
				$scanFailedHTML = wfView::create('scanner/scan-failed', array(
					'messageHTML' => sprintf(/* translators: Time limit (number). */ __('The previous scan has terminated because the time limit of %s was reached. This limit can be customized on the options page.', 'wordfence'), wfUtils::makeDuration($timeLimit)) . ' <a href="' . wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_OPTION_OVERALL_TIME_LIMIT) . '" target="_blank" rel="noopener noreferrer" class="wf-inline-help"><i class="wf-fa wf-fa-question-circle-o" aria-hidden="true"></i><span class="screen-reader-text"> (' . esc_html__('opens in new tab', 'wordfence') . ')</span></a>',
					'buttonTitle' => __('Close', 'wordfence'),
				))->render();
				break;
			case wfIssues::SCAN_FAILED_VERSION_CHANGE:
				$scanFailedHTML = wfView::create('scanner/scan-failed', array(
					'messageHTML' => esc_html__('The previous scan has terminated because we detected an update occurring during the scan.', 'wordfence'),
					'buttonTitle' => __('Close', 'wordfence'),
				))->render();
				break;
			case wfIssues::SCAN_FAILED_START_TIMEOUT:
			case wfIssues::SCAN_FAILED_CALLBACK_TEST_FAILED:
				$resumeAttempts = wfScanMonitor::getConfiguredResumeAttempts();
				if ($resumeAttempts > 0) {
					if ($resumeAttempts === 1)
						$resumeMessage = __('Wordfence will make one attempt to resume each failed scan stage. This scan may recover if this attempt is successful.', 'wordfence');
					else
						$resumeMessage = sprintf(__('Wordfence will make up to %d attempts to resume each failed scan stage. This scan may recover if one of these attempts is successful.', 'wordfence'), $resumeAttempts);
					$resumeMessage = " {$resumeMessage} ";
				}
				else {
					$resumeMessage = '';
				}
				$scanFailedHTML = wfView::create('scanner/scan-failed', array(
					'messageTitle' => __('Scan Stage Failed', 'wordfence'),
					'messageHTML' => __('A scan stage has failed to start. This is often because the site either cannot make outbound requests or is blocked from connecting to itself.', 'wordfence') . $resumeMessage . ' <a href="' . wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_FAILED_START) . '" target="_blank" rel="noopener noreferrer">' . __('Click here for steps you can try.', 'wordfence') . '<span class="screen-reader-text"> (' . esc_html__('opens in new tab', 'wordfence') . ')</span></a>',
					'buttonTitle' => __('Close', 'wordfence'),
				))->render();
				break;
			case wfIssues::SCAN_FAILED_API_SSL_UNAVAILABLE:
				$scanFailedHTML = wfView::create('scanner/scan-failed', array(
					'messageHTML' => esc_html__('Scans are not functional because SSL is unavailable.', 'wordfence'),
					'buttonTitle' => __('Close', 'wordfence'),
				))->render();
				break;
			case wfIssues::SCAN_FAILED_API_CALL_FAILED:
				$scanFailedHTML = wfView::create('scanner/scan-failed', array(
					'messageHTML' => __('The scan has failed because we were unable to contact the Wordfence servers. Some sites may need adjustments to run scans reliably.', 'wordfence') . ' <a href="' . wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_FAILS) . '" target="_blank" rel="noopener noreferrer">' . __('Click here for steps you can try.', 'wordfence') . '<span class="screen-reader-text"> (' . esc_html__('opens in new tab', 'wordfence') . ')</span></a>',
					'rawErrorHTML' => esc_html(wfConfig::get('lastScanCompleted', '')),
					'buttonTitle' => __('Close', 'wordfence'),
				))->render();
				break;
			case wfIssues::SCAN_FAILED_API_INVALID_RESPONSE:
			case wfIssues::SCAN_FAILED_API_ERROR_RESPONSE:
				$scanFailedHTML = wfView::create('scanner/scan-failed', array(
					'messageHTML' => __('The scan has failed because we received an unexpected response from the Wordfence servers. This may be a temporary error, though some sites may need adjustments to run scans reliably.', 'wordfence') . ' <a href="' . wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_FAILS) . '" target="_blank" rel="noopener noreferrer">' . __('Click here for steps you can try.', 'wordfence') . '<span class="screen-reader-text"> (' . esc_html__('opens in new tab', 'wordfence') . ')</span></a>',
					'rawErrorHTML' => esc_html(wfConfig::get('lastScanCompleted'), ''),
					'buttonTitle' => __('Close', 'wordfence'),
				))->render();
				break;
		}

		wfUtils::doNotCache();
		return array(
			'ok'                  => 1,
			'lastMessage'		  => $lastMessage,
			'items'               => self::getLog()->getStatusEvents($_POST['lastctime']),
			'currentScanID'       => wfScanner::shared()->lastScanTime(),
			'signatureUpdateTime' => wfConfig::get('signatureUpdateTime'),
			'scanFailedHTML' 	  => $scanFailedHTML,
			'scanStalled'		  => ($scanFailed == wfIssues::SCAN_FAILED_TIMEOUT || $scanFailed == wfIssues::SCAN_FAILED_START_TIMEOUT ? 1 : 0),
			'scanRunning'		  => wfScanner::shared()->isRunning() ? 1 : 0,
			'scanStages'		  => $stages,
			'scanStats'			  => $stats,
			'issues'			  => $issues,
			'issueCounts'		  => $issueCounts,
			'issueUpdateTimestamp'=> $lastIssueUpdateTimestamp,
		);
	}
	public static function ajax_updateAlertEmail_callback(){
		$email = trim($_POST['email']);
		if(! preg_match('/[^\@]+\@[^\.]+\.[^\.]+/', $email) || in_array(hash('sha256', $email), wfConfig::alertEmailBlacklist())){
			return array( 'err' => __("Invalid email address given.", 'wordfence'));
		}
		wfConfig::set('alertEmails', $email);
		return array('ok' => 1, 'email' => $email);
	}
	private static function resolveLocalFile($issue) {
		$data = $issue['data'];
		if (array_key_exists('realFile', $data)) {
			return $data['realFile'];
		}
		else {
			$file = $issue['data']['file'];
			$localFile = ABSPATH . '/' . $file;
			$localFile = realpath($localFile);
			if (strpos($localFile, ABSPATH) !== 0) {
				return null;
			}
			return $localFile;
		}
	}
	public static function ajax_bulkOperation_callback() {
		$op = sanitize_text_field($_POST['op']);
		if ($op == 'del' || $op == 'repair') {
			$idsRemoved = array();
			$filesWorkedOn = 0;
			$errors = array();
			$wfIssues = new wfIssues();
			$issueCount = $wfIssues->getIssueCount();
			for ($offset = floor($issueCount / 100) * 100; $offset >= 0; $offset -= 100) {
				$issues = $wfIssues->getIssues($offset, 100, 0, 0);
				foreach ($issues['new'] as $i) {
					if ($op == 'del' && @$i['data']['canDelete']) {
						$file = $i['data']['file'];
						$localFile = self::resolveLocalFile($i);
						if ($localFile === null)
							continue;
						if ($localFile === ABSPATH . 'wp-config.php') {
							$errors[] = esc_html__('Deleting an infected wp-config.php file must be done outside of Wordfence. The wp-config.php file contains your database credentials, which you will need to restore normal site operations. Your site will NOT function once the wp-config.php file has been deleted.', 'wordfence');
						}
						else if (@unlink($localFile)) {
							$wfIssues->updateIssue($i['id'], 'delete');
							$idsRemoved[] = $i['id'];
							$filesWorkedOn++;
						}
						else {
							$err = error_get_last();
							$errors[] = esc_html(sprintf(/* translators: 1. File path. 2. Error message. */ __('Could not delete file %1$s. Error was: %2$s', 'wordfence'), wp_kses($file, array()), wp_kses(str_replace(ABSPATH, '{WordPress Root}/', $err['message']), array())));
						}
					}
					else if ($op == 'repair' && @$i['data']['canFix']) {
						$file = $i['data']['file'];
						$localFile = self::resolveLocalFile($i);
						if ($localFile === null)
							continue;
						$result = array();
						if (isset($i['data']) && is_array($i['data']) && isset($i['data']['file']) && isset($i['data']['cType']) && ( //Basics
								$i['data']['cType'] == 'core' || //Core file
								($i['data']['cType'] == 'plugin' || $i['data']['cType'] == 'theme') && isset($i['data']['cName']) && isset($i['data']['cVersion']) //Plugin or Theme file
							)) {
							$result = self::getWPFileContent($i['data']['file'], $i['data']['cType'], isset($i['data']['cName']) ? $i['data']['cName'] : null, isset($i['data']['cVersion']) ? $i['data']['cVersion'] : null);
						}

						if (is_array($result) && isset($result['errorMsg'])) {
							$errors[] = esc_html($result['errorMsg']);
							continue;
						}
						else if (!is_array($result) || !isset($result['fileContent'])) {
							$errors[] = esc_html(sprintf(/* translators: File path. */ __('We could not retrieve the original file of %s to do a repair.', 'wordfence'), wp_kses($file, array())));
							continue;
						}

						if (preg_match('/\.\./', $file)) {
							$errors[] = sprintf(/* translators: File path. */ __('An invalid file %s was specified for repair.', 'wordfence'), wp_kses($file, array()));
							continue;
						}

						$fh = fopen($localFile, 'w');
						if (!$fh) {
							$err = error_get_last();
							if (preg_match('/Permission denied/i', $err['message'])) {
								$errMsg = esc_html(sprintf(/* translators: File path. */ __('You don\'t have permission to repair %s. You need to either fix the file manually using FTP or change the file permissions and ownership so that your web server has write access to repair the file.', 'wordfence'), wp_kses($file, array())));
							}
							else {
								$errMsg = esc_html(sprintf(/* translators: 1. File path. 2. Error message. */ __('We could not write to %1$s. The error was: %2$s', 'wordfence'), wp_kses($file, array()),  $err['message']));
							}
							$errors[] = $errMsg;
							continue;
						}

						flock($fh, LOCK_EX);
						$bytes = fwrite($fh, $result['fileContent']);
						flock($fh, LOCK_UN);
						fclose($fh);
						if ($bytes < 1) {
							$errors[] = esc_html(sprintf(/* translators: 1. File path. 2. Number of bytes. */ __('We could not write to %1$s. (%2$d bytes written) You may not have permission to modify files on your WordPress server.', 'wordfence'), wp_kses($file, array()), $bytes));
							continue;
						}

						$filesWorkedOn++;
						$wfIssues->updateIssue($i['id'], 'delete');
						$idsRemoved[] = $i['id'];
					}
				}
			}

			if ($filesWorkedOn > 0 && count($errors) > 0) {
				$headMsg = esc_html($op == 'del' ? __('Deleted some files with errors', 'wordfence') : __('Repaired some files with errors', 'wordfence'));
				$bodyMsg = sprintf(esc_html($op == 'del' ?
					/* translators: 1. Number of files. 2. Error message. */
					__('Deleted %1$d files but we encountered the following errors with other files: %2$s', 'wordfence') :
					/* translators: 1. Number of files. 2. Error message. */
					__('Repaired %1$d files but we encountered the following errors with other files: %2$s', 'wordfence')),
					$filesWorkedOn, implode('<br>', $errors));
			}
			else if ($filesWorkedOn > 0) {
				$headMsg = sprintf(esc_html($op == 'del' ? /* translators: Number of files. */ __('Deleted %d files successfully', 'wordfence') : /* translators: Number of files. */ __('Repaired %d files successfully', 'wordfence')), $filesWorkedOn);
				$bodyMsg = sprintf(esc_html($op == 'del' ? /* translators: Number of files. */ __('Deleted %d files successfully. No errors were encountered.', 'wordfence') : /* translators: Number of files. */ __('Repaired %d files successfully. No errors were encountered.', 'wordfence')), $filesWorkedOn);
			}
			else if (count($errors) > 0) {
				$headMsg = esc_html($op == 'del' ? __('Could not delete files', 'wordfence') : __('Could not repair files', 'wordfence'));
				$bodyMsg = sprintf(esc_html($op == 'del' ?
					/* translators: Error message. */
					__('We could not delete any of the files you selected. We encountered the following errors: %s', 'wordfence') :
					/* translators: Error message. */
					__('We could not repair any of the files you selected. We encountered the following errors: %s', 'wordfence')),  implode('<br>', $errors));
			}
			else {
				$headMsg = esc_html__('Nothing done', 'wordfence');
				$bodyMsg = esc_html($op == 'del' ? __('We didn\'t delete anything and no errors were found.', 'wordfence') : __('We didn\'t repair anything and no errors were found.', 'wordfence'));
			}

			wfScanEngine::refreshScanNotification($wfIssues);
			$counts = $wfIssues->getIssueCounts();
			return array('ok' => 1, 'bulkHeading' => $headMsg, 'bulkBody' => $bodyMsg, 'idsRemoved' => $idsRemoved, 'issueCounts' => $counts);
		}
		else {
			return array('errorMsg' => esc_html__('Invalid bulk operation selected', 'wordfence'));
		}
	}
	public static function ajax_deleteFile_callback($issueID = null){
		if ($issueID === null) {
			$issueID = intval($_POST['issueID']);
		}
		$wfIssues = new wfIssues();
		$issue = $wfIssues->getIssueByID($issueID);
		if(! $issue){
			return array('errorMsg' => __('Could not delete file because we could not find that issue.', 'wordfence'));
		}
		if(! $issue['data']['file']){
			return array('errorMsg' => __('Could not delete file because that issue does not appear to be a file related issue.', 'wordfence'));
		}
		$file = $issue['data']['file'];
		$localFile = self::resolveLocalFile($issue);
		if($localFile === null){
			return array('errorMsg' => __('An invalid file was requested for deletion.', 'wordfence'));
		}
		if ($file === 'wp-config.php') {
			return array(
				'errorMsg' => __('Deleting an infected wp-config.php file must be done outside of Wordfence. The wp-config.php file contains your database credentials, which you will need to restore normal site operations. Your site will NOT function once the wp-config.php file has been deleted.', 'wordfence')
			);
		}

		/** @var WP_Filesystem_Base $wp_filesystem */
		global $wp_filesystem;

		$adminURL = network_admin_url('admin.php?' . http_build_query(array(
				'page'               => 'WordfenceScan',
				'subpage'       	 => 'scan_credentials',
				'action'			 => 'deleteFile',
				'issueID'            => $issueID,
				'nonce'              => wp_create_nonce('wp-ajax'),
			)));

		if (!self::requestFilesystemCredentials($adminURL, null, true, false)) {
			return array(
				'ok'               => 1,
				'needsCredentials' => 1,
				'redirect'         => $adminURL,
			);
		}

		if ($wp_filesystem->delete($localFile)) {
			$wfIssues->updateIssue($issueID, 'delete');
			$counts = $wfIssues->getIssueCounts();
			wfScanEngine::refreshScanNotification($wfIssues);
			return array(
				'ok' => 1,
				'localFile' => $localFile,
				'file' => $file,
				'issueCounts' => $counts,
			);
		}

		$err = error_get_last();
		return array(
			'errorMsg' => sprintf(
			/* translators: 1. File path. 2. Error message. */
				__('Could not delete file %1$s. The error was: %2$s', 'wordfence'),
				wp_kses($file, array()),
				wp_kses(str_replace(ABSPATH, '{WordPress Root}/', $err['message']), array())
			)
		);
	}
	public static function ajax_deleteDatabaseOption_callback(){
		/** @var wpdb $wpdb */
		global $wpdb;
		$issueID = intval($_POST['issueID']);
		$wfIssues = new wfIssues();
		$issue = $wfIssues->getIssueByID($issueID);
		if (!$issue) {
			return array('errorMsg' => __("Could not remove the option because we could not find that issue.", 'wordfence'));
		}
		if (empty($issue['data']['option_name'])) {
			return array('errorMsg' => __("Could not remove the option because that issue does not appear to be a database related issue.", 'wordfence'));
		}
		$table_options = wfDB::blogTable('options', $issue['data']['site_id']);
		if ($wpdb->query($wpdb->prepare("DELETE FROM {$table_options} WHERE option_name = %s", $issue['data']['option_name']))) {
			$wfIssues->updateIssue($issueID, 'delete');
			wfScanEngine::refreshScanNotification($wfIssues);
			return array(
				'ok'          => 1,
				'option_name' => $issue['data']['option_name'],
			);
		} else {
			return array('errorMsg' => sprintf(
			/* translators: 1. WordPress option. 2. Error message. */
				__('Could not remove the option %1$s. The error was: %2$s', 'wordfence'),
				esc_html($issue['data']['option_name']),
				esc_html($wpdb->last_error)
			));
		}
	}
	public static function ajax_fixFPD_callback(){
		$issues = new wfIssues();
		$issue  = $issues->getIssueByID($_POST['issueID']);
		if (!$issue) {
			return array('cerrorMsg' => __("We could not find that issue in our database.", 'wordfence'));
		}

		$htaccess = ABSPATH . '/.htaccess';
		$change   = "<IfModule mod_php5.c>\n\tphp_value display_errors 0\n</IfModule>\n<IfModule mod_php7.c>\n\tphp_value display_errors 0\n</IfModule>\n<IfModule mod_php.c>\n\tphp_value display_errors 0\n</IfModule>";
		$content  = "";
		if (file_exists($htaccess)) {
			$content = file_get_contents($htaccess);
		}

		if (@file_put_contents($htaccess, trim($content . "\n" . $change), LOCK_EX) === false) {
			return array('cerrorMsg' => __("You don't have permission to repair .htaccess. You need to either fix the file manually using FTP or change the file permissions and ownership so that your web server has write access to repair the file.", 'wordfence'));
		}
		if (wfScanEngine::testForFullPathDisclosure()) {
			// Didn't fix it, so revert the changes and return an error
			file_put_contents($htaccess, $content, LOCK_EX);
			return array(
				'cerrorMsg' => __("Modifying the .htaccess file did not resolve the issue, so the original .htaccess file was restored. You can fix this manually by setting <code>display_errors</code> to <code>Off</code> in your php.ini if your site is on a VPS or dedicated server that you control.", 'wordfence'),
			);
		}
		$issues->updateIssue($_POST['issueID'], 'delete');
		wfScanEngine::refreshScanNotification($issues);
		return array('ok' => 1);
	}
	public static function ajax_restoreFile_callback($issueID = null){
		if ($issueID === null) {
			$issueID = intval($_POST['issueID']);
		}
		$wfIssues = new wfIssues();
		$issue = $wfIssues->getIssueByID($issueID);
		if(! $issue){
			return array('cerrorMsg' => __("We could not find that issue in our database.", 'wordfence'));
		}

		/** @var WP_Filesystem_Base $wp_filesystem */
		global $wp_filesystem;

		$adminURL = network_admin_url('admin.php?' . http_build_query(array(
				'page'               => 'WordfenceScan',
				'subpage'       	 => 'scan_credentials',
				'action'			 => 'restoreFile',
				'issueID'            => $issueID,
				'nonce'              => wp_create_nonce('wp-ajax'),
			)));

		if (!self::requestFilesystemCredentials($adminURL, null, true, false)) {
			return array(
				'ok'               => 1,
				'needsCredentials' => true,
				'redirect'         => $adminURL,
			);
		}

		$dat = $issue['data'];
		$result = self::getWPFileContent($dat['file'], $dat['cType'], (isset($dat['cName']) ? $dat['cName'] : ''), (isset($dat['cVersion']) ? $dat['cVersion'] : ''));
		$file = $dat['file'];
		if(isset($result['errorMsg']) && $result['errorMsg']){
			return $result;
		} else if(! $result['fileContent']){
			return array('errorMsg' => __("We could not get the original file to do a repair.", 'wordfence'));
		}

		if(preg_match('/\.\./', $file)){
			return array('errorMsg' => __("An invalid file was specified for repair.", 'wordfence'));
		}
		if (array_key_exists('realFile', $dat)) {
			$localFile = $dat['realFile'];
		}
		else {
			$localFile = rtrim(ABSPATH, '/') . '/' . preg_replace('/^[\.\/]+/', '', $file);
		}
		if ($wp_filesystem->put_contents($localFile, $result['fileContent'])) {
			$wfIssues->updateIssue($issueID, 'delete');
			$counts = $wfIssues->getIssueCounts();
			wfScanEngine::refreshScanNotification($wfIssues);
			return array(
				'ok'   => 1,
				'localFile' => $localFile,
				'file' => $file,
				'issueCounts' => $counts,
			);
		}
		return array(
			'errorMsg' => __("We could not write to that file. You may not have permission to modify files on your WordPress server.", 'wordfence'),
		);
	}
	public static function ajax_scan_callback(){
		self::status(4, 'info', __("Ajax request received to start scan.", 'wordfence'));
		$err = wfScanEngine::startScan();
		if ($err) {
			return array('errorMsg' => wp_kses($err, array()));
		}
		else {
			$issueCounts = array_merge(array('new' => 0, 'ignoreP' => 0, 'ignoreC' => 0), wfIssues::shared()->getIssueCounts());
			return array("ok" => 1, 'issueCounts' => $issueCounts);
		}
	}
	public static function ajax_exportSettings_callback() {
		$result = wfImportExportController::shared()->export();
		return $result;
	}
	public static function ajax_importSettings_callback(){
		$token = $_POST['token'];
		return self::importSettings($token);
	}
	public static function importSettings($token) { //Documented call for external interfacing.
		return wfImportExportController::shared()->import($token);
	}
	public static function ajax_dismissNotification_callback() {
		$id = $_POST['id'];
		$n = wfNotification::getNotificationForID($id);
		if ($n !== null) {
			$n->markAsRead();
		}
		return array(
			'ok' => 1,
		);
	}
	public static function ajax_utilityScanForBlacklisted_callback() {
		if (wfScanner::shared()->isRunning()) {
			return array('wait' => 2); //Can't run while a scan is running since the URL hoover is currently implemented like a singleton
		}

		$pageURL = stripslashes($_POST['url']);
		$source = stripslashes($_POST['source']);
		$apiKey = wfConfig::get('apiKey');
		$wp_version = wfUtils::getWPVersion();
		$h = new wordfenceURLHoover($apiKey, $wp_version);
		$h->hoover(1, $source);
		$hooverResults = $h->getBaddies();
		if ($h->errorMsg) {
			$h->cleanup();
			return array('wait' => 3, 'errorMsg' => $h->errorMsg); //Unable to contact noc1 to verify
		}
		$h->cleanup();
		if (sizeof($hooverResults) > 0 && isset($hooverResults[1])) {
			$hresults = $hooverResults[1];
			$count = count($hresults);
			if ($count > 0) {
				new wfNotification(
					null,
					wfNotification::PRIORITY_HIGH_WARNING,
					sprintf(/* translators: Number of URLs. */ _n("Page contains %d malware URL: ", "Page contains %d malware URLs: ", $count, 'wordfence') . esc_html($pageURL)),
					'wfplugin_malwareurl_' . md5($pageURL),
					null,
					array(array('link' => wfUtils::wpAdminURL('admin.php?page=WordfenceScan'), 'label' => __('Run a Scan', 'wordfence'))));
				return array('bad' => $count);
			}
		}
		return array('ok' => 1);
	}
	public static function ajax_dashboardShowMore_callback() {
		$grouping = $_POST['grouping'];
		$period = $_POST['period'];

		$dashboard = new wfDashboard();
		if ($grouping == 'ips') {
			$data = null;
			if ($period == '24h') { $data = $dashboard->ips24h; }
			else if ($period == '7d') { $data = $dashboard->ips7d; }
			else if ($period == '30d') { $data = $dashboard->ips30d; }

			if ($data !== null) {
				foreach ($data as &$d) {
					$d['IP'] = esc_html(wfUtils::inet_ntop($d['IP']));
					$d['blockCount'] = esc_html(number_format_i18n($d['blockCount']));
					$d['countryFlag'] = esc_attr('wf-flag-' . strtolower($d['countryCode']));
					$d['countryName'] = esc_html($d['countryName']);
				}
				return array('ok' => 1, 'data' => $data);
			}
		}
		else if ($grouping == 'logins') {
			$data = null;
			if ($period == 'success') { $data = $dashboard->loginsSuccess; }
			else if ($period == 'fail') { $data = $dashboard->loginsFail; }

			if ($data !== null) {
				$data = array_slice($data, 0, 100);
				foreach ($data as &$d) {
					$d['ip'] = esc_html($d['ip']);
					$d['name'] = esc_html($d['name']);
					if (time() - $d['t'] < 86400) {
						$d['t'] = esc_html(wfUtils::makeTimeAgo(time() - $d['t']) . ' ago');
					}
					else {
						$d['t'] = esc_html(wfUtils::formatLocalTime(get_option('date_format') . ' ' . get_option('time_format'), (int) $d['t']));
					}
				}
				return array('ok' => 1, 'data' => $data);
			}
		}

		return array('error' => __('Unknown dashboard data set.', 'wordfence'));
	}
	public static function startScan(){
		wfScanEngine::startScan();
	}
	public static function templateRedir(){
		if (!empty($_GET['wordfence_lh'])) {
			self::ajax_lh_callback();
			exit;
		}
		if (!empty($_GET['wfcentral_admin_redirect'])) {
			wp_safe_redirect(remove_query_arg('wfcentral_admin_redirect', network_admin_url('admin.php?page=Wordfence' . rawurlencode(ucwords(preg_replace('/\W/', '', $_GET['wfcentral_admin_redirect']))) . '&' . $_SERVER['QUERY_STRING'])));
			exit;
		}

		$wfFunc = !empty($_GET['_wfsf']) && is_string($_GET['_wfsf']) ? $_GET['_wfsf'] : '';

		//Logging
		self::doEarlyAccessLogging();
		//End logging


		if(! ($wfFunc == 'diff' || $wfFunc == 'view' || $wfFunc == 'viewOption' || $wfFunc == 'sysinfo' || $wfFunc == 'IPTraf' || $wfFunc == 'viewActivityLog' || $wfFunc == 'testmem' || $wfFunc == 'testtime' || $wfFunc == 'download' || $wfFunc == 'blockedIPs' || ($wfFunc == 'debugWAF' && WFWAF_DEBUG))){
			return;
		}
		if(! wfUtils::isAdmin()){
			return;
		}

		$nonce = $_GET['nonce'];
		if(! wp_verify_nonce($nonce, 'wp-ajax')){
			_e("Bad security token. It may have been more than 12 hours since you reloaded the page you came from. Try reloading the page you came from. If that doesn't work, please sign out and sign-in again.", 'wordfence');
			exit(0);
		}
		if($wfFunc == 'diff'){
			self::wfFunc_diff();
		} else if($wfFunc == 'view'){
			self::wfFunc_view();
		} else if($wfFunc == 'viewOption'){
			self::wfFunc_viewOption();
		} else if($wfFunc == 'sysinfo') {
			require(dirname(__FILE__) . '/sysinfo.php' );
		} else if($wfFunc == 'IPTraf'){
			self::wfFunc_IPTraf();
		} else if($wfFunc == 'viewActivityLog'){
			self::wfFunc_viewActivityLog();
		} else if($wfFunc == 'testmem'){
			self::wfFunc_testmem();
		} else if($wfFunc == 'testtime'){
			self::wfFunc_testtime();
		} else if($wfFunc == 'download'){
			self::wfFunc_download();
		} else if($wfFunc == 'blockedIPs'){
			self::wfFunc_blockedIPs();
		} else if($wfFunc == 'debugWAF' && WFWAF_DEBUG){
			self::wfFunc_debugWAF();
		}
		exit(0);
	}
	public static function memtest_error_handler($errno, $errstr, $errfile, $errline){
		echo "Error received: $errstr\n";
	}
	private static function wfFunc_testtime(){
		header('Content-Type: text/plain');
		@error_reporting(E_ALL);
		wfUtils::iniSet('display_errors','On');
		set_error_handler('wordfence::memtest_error_handler', E_ALL);

		echo "Wordfence process duration benchmarking utility version " . WORDFENCE_VERSION . ".\n";
		echo "This utility tests how long your WordPress host allows a process to run.\n\n--Starting test--\n";
		echo "Starting timed test. This will take at least three minutes. Seconds elapsed are printed below.\nAn error after this line is not unusual. Read it and the elapsed seconds to determine max process running time on your host.\n";
		for($i = 1; $i <= 180; $i++){
			echo "\n$i:";
			for($j = 0; $j < 1000; $j++){
				echo '.';
			}
			flush();
			sleep(1);
		}
		echo "\n--Test complete.--\n\nCongratulations, your web host allows your PHP processes to run at least 3 minutes.\n";
		exit();
	}
	private static function wfFunc_testmem(){
		header('Content-Type: text/plain');
		@error_reporting(E_ALL);
		wfUtils::iniSet('display_errors','On');
		set_error_handler('wordfence::memtest_error_handler', E_ALL);

		$maxMemory = ini_get('memory_limit');
		$last = strtolower(substr($maxMemory, -1));
		$maxMemory = (int) $maxMemory;

		$configuredMax = wfConfig::get('maxMem', 0);
		if ($configuredMax <= 0) {
			if ($last == 'g') { $configuredMax = $maxMemory * 1024; }
			else if ($last == 'm') { $configuredMax = $maxMemory; }
			else if ($last == 'k') { $configuredMax = $maxMemory / 1024; }
			$configuredMax = floor($configuredMax);
		}

		$stepSize = 5242880; //5 MB

		echo "Wordfence Memory benchmarking utility version " . WORDFENCE_VERSION . ".\n";
		echo "This utility tests if your WordPress host respects the maximum memory configured\nin their php.ini file, or if they are using other methods to limit your access to memory.\n\n--Starting test--\n";
		echo "Current maximum memory configured in php.ini: " . ini_get('memory_limit') . "\n";
		echo "Current memory usage: " . sprintf('%.2f', memory_get_usage(true) / (1024 * 1024)) . "M\n";
		echo "Attempting to set max memory to {$configuredMax}M.\n";
		wfUtils::iniSet('memory_limit', ($configuredMax + 5) . 'M'); //Allow a little extra for testing overhead
		echo "Starting memory benchmark. Seeing an error after this line is not unusual. Read the error carefully\nto determine how much memory your host allows. We have requested {$configuredMax} megabytes.\n";

		if (memory_get_usage(true) < 1) {
			echo "Exiting test because memory_get_usage() returned a negative number\n";
			exit();
		}
		if (memory_get_usage(true) > (1024 * 1024 * 1024)) {
			echo "Exiting because current memory usage is greater than a gigabyte.\n";
			exit();
		}

		if (!defined('WP_SANDBOX_SCRAPING')) { define('WP_SANDBOX_SCRAPING', true); } //Disables the WP error handler in somewhat of a hacky way

		$accumulatedMemory = array_fill(0, ceil($configuredMax / $stepSize), '');
		$currentUsage = memory_get_usage(true);
		$tenMB = 10 * 1024 * 1024;
		$start = ceil($currentUsage / $tenMB) * $tenMB - $currentUsage; //Start at the closest 10 MB increment to the current usage
		$configuredMax = $configuredMax * 1048576; //Bytes
		$testLimit = $configuredMax - memory_get_usage(true);
		$finalUsage = '0';
		$previous = 0;
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ012345678900000000000000000000000000000000000000000000000000000000000000000000000000000000000000011111111111111111222222222222222222233333333333333334444444444444444444444444555555555555666666666666666666";
		$index = 0;
		while ($start <= $testLimit) {
			$accumulatedMemory[$index] = str_repeat($chars, ($start - $previous) / 256);

			$finalUsage = sprintf('%.2f', (memory_get_usage(true) / 1024 / 1024));
			echo "Tested up to " . $finalUsage . " megabytes.\n";
			if ($start == $testLimit) { break; }
			$previous = $start;
			$start = min($start + $stepSize, $testLimit);

			if (memory_get_usage(true) > $configuredMax) { break; }
			$index++;
		}
		echo "--Test complete.--\n\nYour web host allows you to use at least {$finalUsage} megabytes of memory for each PHP process hosting your WordPress site.\n";
		exit();
	}
	public static function wfLogHumanHeader(){
		//Final check in case this was added as an action before the request was fully initialized
		if (self::getLog()->getCurrentRequest()->jsRun || !wfConfig::liveTrafficEnabled()) {
			return;
		}

		self::$hitID = self::getLog()->logHit();
		if (self::$hitID) {
			$URL = home_url('/?wordfence_lh=1&hid=' . wfUtils::encrypt(self::$hitID));
			$URL = addslashes(preg_replace('/^https?:/i', '', $URL));
			#Load as external script async so we don't slow page down.
			echo <<<HTML
<script type="text/javascript">
(function(url){
	if(/(?:Chrome\/26\.0\.1410\.63 Safari\/537\.31|WordfenceTestMonBot)/.test(navigator.userAgent)){ return; }
	var addEvent = function(evt, handler) {
		if (window.addEventListener) {
			document.addEventListener(evt, handler, false);
		} else if (window.attachEvent) {
			document.attachEvent('on' + evt, handler);
		}
	};
	var removeEvent = function(evt, handler) {
		if (window.removeEventListener) {
			document.removeEventListener(evt, handler, false);
		} else if (window.detachEvent) {
			document.detachEvent('on' + evt, handler);
		}
	};
	var evts = 'contextmenu dblclick drag dragend dragenter dragleave dragover dragstart drop keydown keypress keyup mousedown mousemove mouseout mouseover mouseup mousewheel scroll'.split(' ');
	var logHuman = function() {
		if (window.wfLogHumanRan) { return; }
		window.wfLogHumanRan = true;
		var wfscr = document.createElement('script');
		wfscr.type = 'text/javascript';
		wfscr.async = true;
		wfscr.src = url + '&r=' + Math.random();
		(document.getElementsByTagName('head')[0]||document.getElementsByTagName('body')[0]).appendChild(wfscr);
		for (var i = 0; i < evts.length; i++) {
			removeEvent(evts[i], logHuman);
		}
	};
	for (var i = 0; i < evts.length; i++) {
		addEvent(evts[i], logHuman);
	}
})('$URL');
</script>
HTML;
		}
	}
	public static function shutdownAction(){
	}
	public static function wfFunc_viewActivityLog(){
		require(dirname(__FILE__) . '/viewFullActivityLog.php');
		exit(0);
	}
	public static function wfFunc_IPTraf(){
		$IP = $_GET['IP'];
		try {
			$response = self::IPTraf($IP);
			$reverseLookup = $response['reverseLookup'];
			$results = $response['results'];
			require(dirname(__FILE__) . '/IPTraf.php');
			exit(0);
		} catch (InvalidArgumentException $e) {
			echo $e->getMessage();
			exit;
		}
	}

	private static function IPTraf($ip) {
		if(!wfUtils::isValidIP($ip)){
			throw new InvalidArgumentException(__("An invalid IP address was specified.", 'wordfence'));
		}
		$reverseLookup = wfUtils::reverseLookup($ip);
		$wfLog = wfLog::shared();
		$results = array_merge(
			$wfLog->getHits('hits', '404', 0, 10000, $ip),
			$wfLog->getHits('hits', 'hit', 0, 10000, $ip)
		);
		usort($results, 'wordfence::iptrafsort');

		$ids = array();
		foreach ($results as $k => $r) {
			if (isset($ids[$r['id']])) {
				unset($results[$k]);
			}
			else {
				$ids[$r['id']] = 1;
			}
		}

		$results = array_values($results);

		for ($i = 0; $i < count($results); $i++){
			if(array_key_exists($i + 1, $results)){
				$results[$i]['timeSinceLastHit'] = sprintf('%.4f', $results[$i]['ctime'] - $results[$i + 1]['ctime']);
			} else {
				$results[$i]['timeSinceLastHit'] = '';
			}
		}
		return compact('reverseLookup', 'results');
	}

	public static function iptrafsort($b, $a){
		if($a['ctime'] == $b['ctime']){ return 0; }
		return ($a['ctime'] < $b['ctime']) ? -1 : 1;
	}

	private static function checkRealFileParameters() {
		if (array_key_exists('realFile', $_GET)) {
			$realFile = stripslashes($_GET['realFile']);
			$token = array_key_exists('realFileToken', $_GET) ? $_GET['realFileToken'] : '';
			if (!wfIssues::verifyRealFileToken($token, $realFile)) {
				esc_html_e('This link has expired. Refresh the scan results page and try again.', 'wordfence');
				exit(0);
			}
			return $realFile;
		}
		return null;
	}

	public static function wfFunc_viewOption() {
		/** @var wpdb $wpdb */
		global $wpdb;
		$site_id = !empty($_GET['site_id']) ? absint($_GET['site_id']) : get_current_blog_id();
		$option_name = !empty($_GET['option']) ? $_GET['option'] : false;

		$table_options = wfDB::blogTable('options', $site_id);
		$option_value = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$table_options} WHERE option_name = %s", $option_name));

		header('Content-type: text/plain');
		exit($option_value);
	}

	public static function wfFunc_view(){
		wfUtils::doNotCache();
		if (WORDFENCE_DISABLE_FILE_VIEWER) {
			_e("File access blocked. (WORDFENCE_DISABLE_FILE_VIEWER is true)", 'wordfence');
			exit();
		}
		$localFile = self::checkRealFileParameters();
		if ($localFile === null)
			$localFile = ABSPATH . preg_replace('/^(?:\.\.|[\/]+)/', '', sanitize_text_field($_GET['file']));
		if(strpos($localFile, '..') !== false){
			_e("Invalid file requested. (Relative paths not allowed)", 'wordfence');
			exit();
		}
		if(preg_match('/[\'\"<>\!\{\}\(\)\&\@\%\$\*\+\[\]\?]+/', $localFile)){
			_e("File contains illegal characters.", 'wordfence');
			exit();
		}
		$cont = @file_get_contents($localFile);
		$isEmpty = false;
		if(! $cont){
			if(file_exists($localFile) && filesize($localFile) === 0){ //There's a remote possibility that very large files on 32 bit systems will return 0 here, but it's about 1 in 2 billion
				$isEmpty = true;
			} else {
				$err = error_get_last();
				printf(/* translators: Error message. */ __("We could not open the requested file for reading. The error was: %s", 'wordfence'), $err['message']);
				exit(0);
			}
		}
		$fileMTime = @filemtime($localFile);
		$fileMTime = date('l jS \of F Y h:i:s A', $fileMTime);
		try {
			if(wfUtils::fileOver2Gigs($localFile)){
				$fileSize = __("Greater than 2 Gigs", 'wordfence');
			} else {
				$fileSize = @filesize($localFile); //Checked if over 2 gigs above
				$fileSize = number_format($fileSize, 0, '', ',') . ' bytes';
			}
		} catch(Exception $e){ $fileSize = __('Unknown file size.', 'wordfence'); }

		require(dirname(__FILE__) . '/wfViewResult.php');
		exit(0);
	}

	public static function wfFunc_diff(){
		wfUtils::doNotCache();
		if (WORDFENCE_DISABLE_FILE_VIEWER) {
			esc_html_e("File access blocked. (WORDFENCE_DISABLE_FILE_VIEWER is true)", 'wordfence');
			exit();
		}
		if(preg_match('/[\'\"<>\!\{\}\(\)\&\@\%\$\*\+\[\]\?]+/', $_GET['file'])){
			esc_html_e("File contains illegal characters.", 'wordfence');
			exit();
		}

		$result = self::getWPFileContent($_GET['file'], $_GET['cType'], wp_unslash($_GET['cName']), $_GET['cVersion']);
		if( isset( $result['errorMsg'] ) && $result['errorMsg']){
			echo wp_kses($result['errorMsg'], array());
			exit(0);
		} else if(! $result['fileContent']){
			esc_html_e("We could not get the contents of the original file to do a comparison.", 'wordfence');
			exit(0);
		}

		$localFile = self::checkRealFileParameters();
		if ($localFile === null) {
			$localFile = realpath(ABSPATH . '/' . preg_replace('/^[\.\/]+/', '', $_GET['file']));
		}
		if (empty($localFile)) {
			esc_html_e('Empty file path provided', 'wordfence');
			exit(0);
		}
		$localContents = file_get_contents($localFile);
		if ($localContents === false) {
			esc_html_e('Unable to read file contents', 'wordfence');
			exit(0);
		}
		if($localContents == $result['fileContent']){
			$diffResult = '';
		} else {
			$diff = new Diff(
			//Treat DOS and Unix files the same
				preg_split("/(?:\r\n|\n)/", $result['fileContent']),
				preg_split("/(?:\r\n|\n)/", $localContents),
				array()
			);
			$renderer = new Diff_Renderer_Html_SideBySide;
			$diffResult = $diff->Render($renderer);
		}
		require(dirname(__FILE__) . '/diffResult.php');
		exit(0);
	}

	public static function wfFunc_download() {
		wfUtils::doNotCache();
		if (WORDFENCE_DISABLE_FILE_VIEWER) {
			esc_html_e("File access blocked. (WORDFENCE_DISABLE_FILE_VIEWER is true)", 'wordfence');
			exit();
		}
		$localFile = self::checkRealFileParameters();
		if ($localFile === null)
			$localFile = ABSPATH . preg_replace('/^(?:\.\.|[\/]+)/', '', sanitize_text_field($_GET['file']));
		if (strpos($localFile, '..') !== false) {
			esc_html_e("Invalid file requested. (Relative paths not allowed)", 'wordfence');
			exit();
		}
		if (preg_match('/[\'\"<>\!\{\}\(\)\&\@\%\$\*\+\[\]\?]+/', $localFile)) {
			esc_html_e("File contains illegal characters.", 'wordfence');
			exit();
		}
		if (!file_exists($localFile)) {
			_e('File does not exist.', 'wordfence');
			exit();
		}

		$filename = basename($localFile);
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Length: ' . filesize($localFile));
		readfile($localFile);
		exit;
	}

	public static function wfFunc_blockedIPs() {
		$blocks = wfBlock::ipBlocks(true);

		$output = '';
		if (is_array($blocks)) {
			foreach ($blocks as $entry) {
				$output .= $entry->ip . "\n";
			}
		}

		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . get_bloginfo('name', 'raw') . ' - Blocked IPs.txt"');
		header('Content-Length: ' . strlen($output));

		echo $output;
		exit;
	}

	/**
	 *
	 */
	public static function wfFunc_debugWAF() {
		$data = array();
		if (!empty($_GET['hitid'])) {
			$data['hit'] = new wfRequestModel($_GET['hitid']);
			if ($data['hit']->actionData) {
				$data['hitData'] = (object) wfRequestModel::unserializeActionData($data['hit']->actionData);
			}
			echo wfView::create('waf/debug', $data);
		}
	}

	public static function isWafFailureLoggingEnabled() {
		return wfConfig::get('other_WFNet', true);
	}

	private static function purgeWafFailures() {
		global $wpdb;
		$table = wfDB::networkTable('wfWafFailures');
		$wpdb->query("DELETE FROM {$table} WHERE `timestamp` < DATE_SUB(NOW(), INTERVAL 1 DAY)");
	}

	private static function capWafFailures() {
		global $wpdb;
		$table = wfDB::networkTable('wfWafFailures');
		$highestDeletableId = $wpdb->get_var("SELECT id FROM {$table} ORDER BY id DESC LIMIT 1 OFFSET 25");
		if ($highestDeletableId === null)
			return;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE id <= %d",
				$highestDeletableId
			)
		);
	}

	public static function logWafFailure() {
		global $wf_waf_failure, $wpdb;
		if (!self::isWafFailureLoggingEnabled())
			return;
		if (is_array($wf_waf_failure) && array_key_exists('throwable', $wf_waf_failure)) {
			$throwable = $wf_waf_failure['throwable'];
			if (!($throwable instanceof Throwable || $throwable instanceof Exception))
				return;
			$table = wfDB::networkTable('wfWafFailures');
			$data = [
				'throwable' => (string) $throwable
			];
			if (array_key_exists('rule_id', $wf_waf_failure)) {
				$ruleId = $wf_waf_failure['rule_id'];
				if (is_int($ruleId) || $ruleId >= 0)
					$data['rule_id'] = (int) $ruleId;
			}
			$wpdb->insert($table, $data);
			self::capWafFailures();
			self::scheduleSendAttackData();
		}
	}

	public static function initAction(){
		self::logWafFailure();

		$firewall = new wfFirewall();
		define('WFWAF_OPERATIONAL', $firewall->testConfig());
	}
	public static function admin_init(){
		if(! wfUtils::isAdmin()){ return; }

		wfOnboardingController::initialize();

		if (is_admin() && isset($_GET['page'])) {
			switch ($_GET['page']) {
				case 'WordfenceBlocking':
					wp_redirect(network_admin_url('admin.php?page=WordfenceWAF#top#blocking'));
					die;

				case 'WordfenceLiveTraffic':
					wp_redirect(network_admin_url('admin.php?page=WordfenceTools&subpage=livetraffic'));
					die;

				case 'WordfenceTools':
					if (wfOnboardingController::shouldShowAttempt3() && !array_key_exists('subpage', $_GET)) {
						wp_redirect(add_query_arg('subpage', 'diagnostics'));
						die;
					}
			}
		}



		if (wfConfig::get('touppBypassNextCheck')) {
			wfConfig::set('touppBypassNextCheck', 0);
			wfConfig::set('touppPromptNeeded', 0);
		}

		foreach(array(
			'activate', 'scan', 'updateAlertEmail', 'sendActivityLog', 'restoreFile',
			'exportSettings', 'importSettings', 'bulkOperation', 'deleteFile', 'deleteDatabaseOption', 'removeExclusion',
			'activityLogUpdate', 'ticker', 'loadIssues', 'updateIssueStatus', 'deleteIssue', 'updateAllIssues',
			'avatarLookup', 'reverseLookup', 'unlockOutIP', 'unblockRange', 'whois', 'recentTraffic', 'unblockIP',
			'blockIP', 'permBlockIP', 'loadStaticPanel', 'updateIPPreview', 'downloadHtaccess', 'downloadLogFile', 'checkHtaccess',
			'updateConfig', 'autoUpdateChoice', 'misconfiguredHowGetIPsChoice', 'switchLiveTrafficSecurityOnlyChoice', 'dismissAdminNotice',
			'killScan', 'saveCountryBlocking', 'tourClosed',
			'downgradeLicense', 'addTwoFactor', 'twoFacActivate', 'twoFacDel',
			'loadTwoFactor', 'sendTestEmail',
			'email_summary_email_address_debug', 'unblockNetwork',
			'sendDiagnostic', 'saveDisclosureState', 'saveWAFConfig', 'updateWAFRules', 'loadLiveTraffic', 'whitelistWAFParamKey',
			'disableDirectoryListing', 'fixFPD', 'deleteAdminUser', 'revokeAdminUser', 'acknowledgeAdminUser',
			'hideFileHtaccess', 'saveDebuggingConfig',
			'whitelistBulkDelete', 'whitelistBulkEnable', 'whitelistBulkDisable',
			'dismissNotification', 'utilityScanForBlacklisted', 'dashboardShowMore',
			'saveOptions', 'restoreDefaults', 'enableAllOptionsPage', 'createBlock', 'deleteBlocks', 'makePermanentBlocks', 'getBlocks',
			'installAutoPrepend', 'uninstallAutoPrepend',
			'installLicense', 'recordTOUPP', 'mailingSignup',
			'switchTo2FANew', 'switchTo2FAOld',
			'wfcentral_step1', 'wfcentral_step2', 'wfcentral_step3', 'wfcentral_step4', 'wfcentral_step5', 'wfcentral_step6', 'wfcentral_disconnect',
			'exportDiagnostics',
			'hideNoticeForUser',
			'setDeactivationOption'
		) as $func){
			add_action('wp_ajax_wordfence_' . $func, 'wordfence::ajaxReceiver');
		}

		wp_register_script('chart-js', wfUtils::getBaseURL() . wfUtils::versionedAsset('js/chart.umd.js'), array('jquery'), '4.2.1');
		wp_register_script('wordfence-select2-js', wfUtils::getBaseURL() . wfUtils::versionedAsset('js/wfselect2.min.js'), array('jquery', 'jquery-ui-tooltip'), WORDFENCE_VERSION);
		wp_register_style('wordfence-select2-css', wfUtils::getBaseURL() . wfUtils::versionedAsset('css/wfselect2.min.css'), array(), WORDFENCE_VERSION);
		wp_register_style('wordfence-font-awesome-style', wfUtils::getBaseURL() . wfUtils::versionedAsset('css/wf-font-awesome.css'), '', WORDFENCE_VERSION);

		if (self::isWordfencePage()) {
			wp_enqueue_style('wp-pointer');
			wp_enqueue_script('wp-pointer');
			wp_enqueue_style('wordfence-font', wfUtils::getBaseURL() . wfUtils::versionedAsset('css/wf-roboto-font.css'), '', WORDFENCE_VERSION);
			wp_enqueue_style('wordfence-font-awesome-style');
			wp_enqueue_style('wordfence-main-style', wfUtils::getBaseURL() . wfUtils::versionedAsset('css/main.css'), '', WORDFENCE_VERSION);
			wp_enqueue_style('wordfence-ionicons-style', wfUtils::getBaseURL() . wfUtils::versionedAsset('css/wf-ionicons.css'), '', WORDFENCE_VERSION);
			wp_enqueue_style('wordfence-colorbox-style', wfUtils::getBaseURL() . wfUtils::versionedAsset('css/wf-colorbox.css'), '', WORDFENCE_VERSION);
			wp_enqueue_style('wordfence-license-style', wfLicense::current()->getStylesheet());

			wp_enqueue_script('json2');
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-menu');
			wp_enqueue_script('jquery.wftmpl', wfUtils::getBaseURL() . wfUtils::versionedAsset('js/jquery.tmpl.min.js'), array('jquery'), WORDFENCE_VERSION);
			wp_enqueue_script('jquery.wfcolorbox', wfUtils::getBaseURL() . wfUtils::versionedAsset('js/jquery.colorbox-min.js'), array('jquery'), WORDFENCE_VERSION);
			wp_enqueue_script('jquery.qrcode', wfUtils::getBaseURL() . wfUtils::versionedAsset('js/jquery.qrcode.min.js'), array('jquery'), WORDFENCE_VERSION);
			wp_enqueue_script('wfi18njs', wfUtils::getBaseURL() . wfUtils::versionedAsset('js/wfi18n.js'), array(), WORDFENCE_VERSION);
			wp_enqueue_script('wordfenceAdminExtjs', wfUtils::getBaseURL() . wfUtils::versionedAsset('js/wfglobal.js'), array('jquery'), WORDFENCE_VERSION);
			wp_enqueue_script('wordfenceAdminjs', wfUtils::getBaseURL() . wfUtils::versionedAsset('js/admin.js'), array('jquery', 'jquery-ui-core', 'jquery-ui-menu'), WORDFENCE_VERSION);
			wp_enqueue_script('wordfenceDropdownjs', wfUtils::getBaseURL() . wfUtils::versionedAsset('js/wfdropdown.js'), array('jquery'), WORDFENCE_VERSION);
			self::setupAdminVars();

			if (wfConfig::get('touppPromptNeeded')) {
				add_filter('admin_body_class', 'wordfence::showTOUPPOverlay', 99, 1);
			}
		} else {
			wp_enqueue_style('wp-pointer');
			wp_enqueue_script('wp-pointer');
			wp_enqueue_script('wfi18njs', wfUtils::getBaseURL() . wfUtils::versionedAsset('js/wfi18n.js'), array(), WORDFENCE_VERSION);
			wp_enqueue_script('wordfenceAdminExtjs', wfUtils::getBaseURL() . wfUtils::versionedAsset('js/wfglobal.js'), array('jquery'), WORDFENCE_VERSION);
			wp_enqueue_style('wordfence-font-awesome-style');
			wp_enqueue_style('wordfence-global-style', wfUtils::getBaseURL() . wfUtils::versionedAsset('css/wf-global.css'), '', WORDFENCE_VERSION);
			self::setupAdminVars();
		}

		if (is_admin()) { //Back end only
			if (wfOnboardingController::shouldShowAnyAttempt()) {
				wp_enqueue_script('wordfenceOnboardingjs', wfUtils::getBaseURL() . wfUtils::versionedAsset('js/wfonboarding.js'), array('jquery', 'wordfenceAdminExtjs'), WORDFENCE_VERSION);
			}
			if (preg_match('/\/wp-admin(\/network)?\/plugins.php$/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
				wp_enqueue_style('wordfence-colorbox-style', wfUtils::getBaseURL() . wfUtils::versionedAsset('css/wf-colorbox.css'), '', WORDFENCE_VERSION);
				wp_enqueue_script('jquery.wfcolorbox', wfUtils::getBaseURL() . wfUtils::versionedAsset('js/jquery.colorbox-min.js'), array('jquery'), WORDFENCE_VERSION);
			}

			wfUtils::refreshCachedHomeURL();
			wfUtils::refreshCachedSiteURL();
		}

		if (self::isWordfenceInstallPage())
			return;

		//Early WAF configuration actions
		if (wfOnboardingController::shouldShowAttempt3(!self::isWordfencePage(false))) {
			add_action(is_multisite() ? 'network_admin_notices' : 'admin_notices', 'wordfence::showOnboardingBanner');
		}
		elseif ((!WFWAF_AUTO_PREPEND || WFWAF_SUBDIRECTORY_INSTALL) && empty($_GET['wafAction']) && !wfConfig::get('dismissAutoPrependNotice') && !wfConfig::get('touppPromptNeeded')) {
			if (is_multisite()) {
				add_action('network_admin_notices', 'wordfence::wafAutoPrependNotice');
			} else {
				add_action('admin_notices', 'wordfence::wafAutoPrependNotice');
			}
		}

		if (wfConfig::get('wordfenceCentralConfigurationIssue')) {
			add_action(is_multisite() ? 'network_admin_notices' : 'admin_notices', 'wordfence::showCentralConfigurationIssueNotice');
		}

		if (isset($_GET['page']) && $_GET['page'] == 'WordfenceWAF' && isset($_GET['subpage']) && $_GET['subpage'] == 'waf_options') {
			if (!WFWAF_AUTO_PREPEND || WFWAF_SUBDIRECTORY_INSTALL) { //Not yet installed
				if (isset($_GET['action']) && $_GET['action'] == 'configureAutoPrepend') {
					check_admin_referer('wfWAFAutoPrepend', 'wfnonce');
					if (isset($_GET['serverConfiguration']) && wfWAFAutoPrependHelper::isValidServerConfig($_GET['serverConfiguration'])) {
						$helper = new wfWAFAutoPrependHelper($_GET['serverConfiguration']);
						if (isset($_GET['downloadBackup'])) {
							$helper->downloadBackups(isset($_GET['backupIndex']) ? absint($_GET['backupIndex']) : 0);
						}
					}
				}
			}
			else { //Already installed
				if (isset($_GET['action']) && $_GET['action'] == 'removeAutoPrepend') {
					check_admin_referer('wfWAFRemoveAutoPrepend', 'wfnonce');
					if (isset($_GET['serverConfiguration']) && wfWAFAutoPrependHelper::isValidServerConfig($_GET['serverConfiguration'])) {
						$helper = new wfWAFAutoPrependHelper($_GET['serverConfiguration']);
						if (isset($_GET['downloadBackup'])) {
							$helper->downloadBackups(isset($_GET['backupIndex']) ? absint($_GET['backupIndex']) : 0);
						}
					}
				}
			}
		}
	}
	private static function setupAdminVars(){
		$updateInt = max(absint(wfConfig::getInt('actUpdateInterval', 2)), 2) * 1000; //ms

		wp_localize_script('wordfenceAdminExtjs', 'WordfenceAdminVars', array(
			'ajaxURL' => admin_url('admin-ajax.php'),
			'firstNonce' => wp_create_nonce('wp-ajax'),
			'siteBaseURL' => wfUtils::getSiteBaseURL(),
			'debugOn' => wfConfig::get('debugOn', 0),
			'actUpdateInterval' => $updateInt,
			'cacheType' => wfConfig::get('cacheType'),
			'liveTrafficEnabled' => wfConfig::liveTrafficEnabled(),
			'scanIssuesPerPage' => WORDFENCE_SCAN_ISSUES_PER_PAGE,
			'allowsPausing' => wfConfig::get('liveActivityPauseEnabled'),
			'scanRunning' => wfScanner::shared()->isRunning() ? '1' : '0',
			'modalTemplate' => wfView::create('common/modal-prompt', array('title' => '${title}', 'message' => '${message}', 'primaryButton' => array('id' => 'wf-generic-modal-close', 'label' => __('Close', 'wordfence'), 'link' => '#')))->render(),
			'tokenInvalidTemplate' => wfView::create('common/modal-prompt', array('title' => '${title}', 'message' => '${message}', 'primaryButton' => array('id' => 'wf-token-invalid-modal-reload', 'label' => __('Reload', 'wordfence'), 'link' => '#')))->render(),
			'modalHTMLTemplate' => wfView::create('common/modal-prompt', array('title' => '${title}', 'message' => '{{html message}}', 'primaryButton' => array('id' => 'wf-generic-modal-close', 'label' => __('Close', 'wordfence'), 'link' => '#')))->render(),
			'alertEmailBlacklist' => wfConfig::alertEmailBlacklist(),
			'supportURLs' => array(
				'scan-result-repair-modified-files' => esc_url_raw(wfSupportController::supportURL(wfSupportController::ITEM_SCAN_RESULT_REPAIR_MODIFIED_FILES)),
			),
		));
		self::setupI18nJSStrings();
	}

	private static function setupI18nJSStrings() {
		static $called;
		if ($called) {
			return;
		}
		$called = true;
		wp_localize_script('wfi18njs', 'WordfenceI18nStrings', array(
			'${totalIPs} addresses in this network' => __('${totalIPs} addresses in this network', 'wordfence'),
			'%s in POST body: %s' => /* translators: 1. Description of firewall action. 2. Description of input parameters. */ __('%s in POST body: %s', 'wordfence'),
			'%s in cookie: %s' => /* translators: 1. Description of firewall action. 2. Description of input parameters. */ __('%s in cookie: %s', 'wordfence'),
			'%s in file: %s' => /* translators: 1. Description of firewall action. 2. Description of input parameters. */ __('%s in file: %s', 'wordfence'),
			'%s in query string: %s' => /* translators: 1. Description of firewall action. 2. Description of input parameters. */ __('%s in query string: %s', 'wordfence'),
			'%s is not valid hostname' => /* translators: Domain name. */ __('%s is not valid hostname', 'wordfence'),
			'.htaccess Updated' => __('.htaccess Updated', 'wordfence'),
			'.htaccess change' => __('.htaccess change', 'wordfence'),
			'404 Not Found' => __('404 Not Found', 'wordfence'),
			'Activity Log Sent' => __('Activity Log Sent', 'wordfence'),
			'Add action to allowlist' => __('Add action to allowlist', 'wordfence'),
			'Add code to .htaccess' => __('Add code to .htaccess', 'wordfence'),
			'All Hits' => __('All Hits', 'wordfence'),
			'All capabilties of admin user %s were successfully revoked.' => /* translators: WordPress username. */ __('All capabilties of admin user %s were successfully revoked.', 'wordfence'),
			'An error occurred' => __('An error occurred', 'wordfence'),
			'An error occurred when adding the request to the allowlist.' => __('An error occurred when adding the request to the allowlist.', 'wordfence'),
			'Are you sure you want to allowlist this action?' => __('Are you sure you want to allowlist this action?', 'wordfence'),
			'Authentication Code' => __('Authentication Code', 'wordfence'),
			'Background Request Blocked' => __('Background Request Blocked', 'wordfence'),
			'Block This Network' => __('Block This Network', 'wordfence'),
			'Blocked' => __('Blocked', 'wordfence'),
			'Blocked By Firewall' => __('Blocked By Firewall', 'wordfence'),
			'Blocked WAF' => __('Blocked WAF', 'wordfence'),
			'Blocked by Wordfence' => __('Blocked by Wordfence', 'wordfence'),
			'Blocked by Wordfence plugin settings' => __('Blocked by Wordfence plugin settings', 'wordfence'),
			'Blocked by the Wordfence Application Firewall and plugin settings' => __('Blocked by the Wordfence Application Firewall and plugin settings', 'wordfence'),
			'Blocked by the Wordfence Security Network' => __('Blocked by the Wordfence Security Network', 'wordfence'),
			'Blocked by the Wordfence Web Application Firewall' => __('Blocked by the Wordfence Web Application Firewall', 'wordfence'),
			'Bot' => __('Bot', 'wordfence'),
			'Cancel Changes' => __('Cancel Changes', 'wordfence'),
			'Cellphone Sign-In Recovery Codes' => __('Cellphone Sign-In Recovery Codes', 'wordfence'),
			'Cellphone Sign-in activated for user.' => __('Cellphone Sign-in activated for user.', 'wordfence'),
			'Click here to download a backup copy of this file now' => __('Click here to download a backup copy of this file now', 'wordfence'),
			'Click here to download a backup copy of your .htaccess file now' => __('Click here to download a backup copy of your .htaccess file now', 'wordfence'),
			'Click to fix .htaccess' => __('Click to fix .htaccess', 'wordfence'),
			'Close' => __('Close', 'wordfence'),
			'Crawlers' => __('Crawlers', 'wordfence'),
			'Diagnostic report has been sent successfully.' => __('Diagnostic report has been sent successfully.', 'wordfence'),
			'Directory Listing Disabled' => __('Directory Listing Disabled', 'wordfence'),
			'Directory listing has been disabled on your server.' => __('Directory listing has been disabled on your server.', 'wordfence'),
			'Disabled' => __('Disabled', 'wordfence'),
			'Dismiss' => __('Dismiss', 'wordfence'),
			'Don\'t ask again' => __('Don\'t ask again', 'wordfence'),
			'Download' => __('Download', 'wordfence'),
			'Download Backup File' => __('Download Backup File', 'wordfence'),
			'Each line of 16 letters and numbers is a single recovery code, with optional spaces for readability. When typing your password, enter "wf" followed by the entire code like "mypassword wf1234 5678 90AB CDEF". If your site shows a separate prompt for entering a code after entering only your username and password, enter only the code like "1234 5678 90AB CDEF". Your recovery codes are:' => __('Each line of 16 letters and numbers is a single recovery code, with optional spaces for readability. When typing your password, enter "wf" followed by the entire code like "mypassword wf1234 5678 90AB CDEF". If your site shows a separate prompt for entering a code after entering only your username and password, enter only the code like "1234 5678 90AB CDEF". Your recovery codes are:', 'wordfence'),
			'Email Diagnostic Report' => __('Email Diagnostic Report', 'wordfence'),
			'Email Wordfence Activity Log' => __('Email Wordfence Activity Log', 'wordfence'),
			'Enter a valid IP or domain' => __('Enter a valid IP or domain', 'wordfence'),
			'Enter the email address you would like to send the Wordfence activity log to. Note that the activity log may contain thousands of lines of data. This log is usually only sent to a member of the Wordfence support team. It also contains your PHP configuration from the phpinfo() function for diagnostic data.' => __('Enter the email address you would like to send the Wordfence activity log to. Note that the activity log may contain thousands of lines of data. This log is usually only sent to a member of the Wordfence support team. It also contains your PHP configuration from the phpinfo() function for diagnostic data.', 'wordfence'),
			'Error' => __('Error', 'wordfence'),
			'Error Enabling All Options Page' => __('Error Enabling All Options Page', 'wordfence'),
			'Error Restoring Defaults' => __('Error Restoring Defaults', 'wordfence'),
			'Error Saving Option' => __('Error Saving Option', 'wordfence'),
			'Error Saving Options' => __('Error Saving Options', 'wordfence'),
			'Failed Login' => __('Failed Login', 'wordfence'),
			'Failed Login: Invalid Username' => __('Failed Login: Invalid Username', 'wordfence'),
			'Failed Login: Valid Username' => __('Failed Login: Valid Username', 'wordfence'),
			'File hidden successfully' => __('File hidden successfully', 'wordfence'),
			'File restored OK' => __('File restored OK', 'wordfence'),
			'Filter Traffic' => __('Filter Traffic', 'wordfence'),
			'Firewall Response' => __('Firewall Response', 'wordfence'),
			'Full Path Disclosure' => __('Full Path Disclosure', 'wordfence'),
			'Google Bot' => __('Google Bot', 'wordfence'),
			'Google Crawlers' => __('Google Crawlers', 'wordfence'),
			'HTTP Response Code' => __('HTTP Response Code', 'wordfence'),
			'Human' => __('Human', 'wordfence'),
			'Humans' => __('Humans', 'wordfence'),
			'IP' => __('IP', 'wordfence'),
			'Key:' => __('Key:', 'wordfence'),
			'Last Updated: %s' => /* translators: Localized date. */ __('Last Updated: %s', 'wordfence'),
			'Learn more about repairing modified files.' => __('Learn more about repairing modified files.', 'wordfence'),
			'Loading...' => __('Loading...', 'wordfence'),
			'Locked Out' => __('Locked Out', 'wordfence'),
			'Locked out from logging in' => __('Locked out from logging in', 'wordfence'),
			'Logged In' => __('Logged In', 'wordfence'),
			'Logins' => __('Logins', 'wordfence'),
			'Logins and Logouts' => __('Logins and Logouts', 'wordfence'),
			'Look up IP or Domain' => __('Look up IP or Domain', 'wordfence'),
			'Manual block by administrator' => __('Manual block by administrator', 'wordfence'),
			'Next Update Check: %s' => /* translators: Localized date. */ __('Next Update Check: %s', 'wordfence'),
			'No activity to report yet. Please complete your first scan.' => __('No activity to report yet. Please complete your first scan.', 'wordfence'),
			'No issues have been ignored.' => __('No issues have been ignored.', 'wordfence'),
			'No new issues have been found.' => __('No new issues have been found.', 'wordfence'),
			'No rules were updated. Please verify you have permissions to write to the /wp-content/wflogs directory.' => __('No rules were updated. Please verify you have permissions to write to the /wp-content/wflogs directory.', 'wordfence'),
			'No rules were updated. Please verify your website can reach the Wordfence servers.' => __('No rules were updated. Please verify your website can reach the Wordfence servers.', 'wordfence'),
			'No rules were updated. Your website has reached the maximum number of rule update requests. Please try again later.' => __('No rules were updated. Your website has reached the maximum number of rule update requests. Please try again later.', 'wordfence'),
			'Note: Status will update when changes are saved' => __('Note: Status will update when changes are saved', 'wordfence'),
			'OK' => __('OK', 'wordfence'),
			'Pages Not Found' => __('Pages Not Found', 'wordfence'),
			'Paid Members Only' => __('Paid Members Only', 'wordfence'),
			'Please enter a valid IP address or domain name for your whois lookup.' => __('Please enter a valid IP address or domain name for your whois lookup.', 'wordfence'),
			'Please enter a valid email address.' => __('Please enter a valid email address.', 'wordfence'),
			'Please include your support ticket number or forum username.' => __('Please include your support ticket number or forum username.', 'wordfence'),
			'Please make a backup of this file before proceeding. If you need to restore this backup file, you can copy it to the following path from your site\'s root:' => __('Please make a backup of this file before proceeding. If you need to restore this backup file, you can copy it to the following path from your site\'s root:', 'wordfence'),
			'Please specify a reason' => __('Please specify a reason', 'wordfence'),
			'Please specify a valid IP address range in the form of "1.2.3.4 - 1.2.3.5" without quotes. Make sure the dash between the IP addresses in a normal dash (a minus sign on your keyboard) and not another character that looks like a dash.' => __('Please specify a valid IP address range in the form of "1.2.3.4 - 1.2.3.5" without quotes. Make sure the dash between the IP addresses in a normal dash (a minus sign on your keyboard) and not another character that looks like a dash.', 'wordfence'),
			'Please specify either an IP address range, Hostname or a web browser pattern to match.' => __('Please specify either an IP address range, Hostname or a web browser pattern to match.', 'wordfence'),
			'Recent Activity' => __('Recent Activity', 'wordfence'),
			'Recovery Codes' => __('Recovery Codes', 'wordfence'),
			'Redirected' => __('Redirected', 'wordfence'),
			'Redirected by Country Blocking bypass URL' => __('Redirected by Country Blocking bypass URL', 'wordfence'),
			'Referer' => __('Referer', 'wordfence'),
			'Registered Users' => __('Registered Users', 'wordfence'),
			'Restore Defaults' => __('Restore Defaults', 'wordfence'),
			'Rule Update Failed' => __('Rule Update Failed', 'wordfence'),
			'Rules Updated' => __('Rules Updated', 'wordfence'),
			'Save Changes' => __('Save Changes', 'wordfence'),
			'Scan Complete.' => __('Scan Complete.', 'wordfence'),
			'Scan the code below with your authenticator app to add this account. Some authenticator apps also allow you to type in the text version instead.' => __('Scan the code below with your authenticator app to add this account. Some authenticator apps also allow you to type in the text version instead.', 'wordfence'),
			'Security Event' => __('Security Event', 'wordfence'),
			'Send' => __('Send', 'wordfence'),
			'Sorry, but no data for that IP or domain was found.' => __('Sorry, but no data for that IP or domain was found.', 'wordfence'),
			'Specify a valid IP range' => __('Specify a valid IP range', 'wordfence'),
			'Specify a valid hostname' => __('Specify a valid hostname', 'wordfence'),
			'Specify an IP range, Hostname or Browser pattern' => __('Specify an IP range, Hostname or Browser pattern', 'wordfence'),
			'Success deleting file' => __('Success deleting file', 'wordfence'),
			'Success removing option' => __('Success removing option', 'wordfence'),
			'Success restoring file' => __('Success restoring file', 'wordfence'),
			'Success updating option' => __('Success updating option', 'wordfence'),
			'Successfully deleted admin' => __('Successfully deleted admin', 'wordfence'),
			'Successfully revoked admin' => __('Successfully revoked admin', 'wordfence'),
			'Test Email Sent' => __('Test Email Sent', 'wordfence'),
			'The \'How does Wordfence get IPs\' option was successfully updated to the recommended value.' => __('The \'How does Wordfence get IPs\' option was successfully updated to the recommended value.', 'wordfence'),
			'The Full Path disclosure issue has been fixed' => __('The Full Path disclosure issue has been fixed', 'wordfence'),
			'The admin user %s was successfully deleted.' => /* translators: WordPress username. */ __('The admin user %s was successfully deleted.', 'wordfence'),
			'The file %s was successfully deleted.' => /* translators: File path. */ __('The file %s was successfully deleted.', 'wordfence'),
			'The file %s was successfully hidden from public view.' => /* translators: File path. */ __('The file %s was successfully hidden from public view.', 'wordfence'),
			'The file %s was successfully restored.' => /* translators: File path. */ __('The file %s was successfully restored.', 'wordfence'),
			'The option %s was successfully removed.' => /* translators: WordPress option. */ __('The option %s was successfully removed.', 'wordfence'),
			'The request has been allowlisted. Please try it again.' => __('The request has been allowlisted. Please try it again.', 'wordfence'),
			'There was an error while sending the email.' => __('There was an error while sending the email.', 'wordfence'),
			'This will be shown only once. Keep these codes somewhere safe.' => __('This will be shown only once. Keep these codes somewhere safe.', 'wordfence'),
			'Throttled' => __('Throttled', 'wordfence'),
			'Two Factor Status' => __('Two Factor Status', 'wordfence'),
			'Type' => __('Type', 'wordfence'),
			'Type: %s' => /* translators: HTTP client type. */ __('Type: %s', 'wordfence'),
			'URL' => __('URL', 'wordfence'),
			'Unable to automatically hide file' => __('Unable to automatically hide file', 'wordfence'),
			'Use one of these %s codes to log in if you are unable to access your phone. Codes are 16 characters long, plus optional spaces. Each one may be used only once.' => /* translators: 2FA backup codes. */ __('Use one of these %s codes to log in if you are unable to access your phone. Codes are 16 characters long, plus optional spaces. Each one may be used only once.', 'wordfence'),
			'Use one of these %s codes to log in if you lose access to your authenticator device. Codes are 16 characters long, plus optional spaces. Each one may be used only once.' => /* translators: 2FA backup codes. */ __('Use one of these %s codes to log in if you lose access to your authenticator device. Codes are 16 characters long, plus optional spaces. Each one may be used only once.', 'wordfence'),
			'User Agent' => __('User Agent', 'wordfence'),
			'User ID' => __('User ID', 'wordfence'),
			'Username' => __('Username', 'wordfence'),
			'WHOIS LOOKUP' => __('WHOIS LOOKUP', 'wordfence'),
			'We are about to change your <em>.htaccess</em> file. Please make a backup of this file before proceeding.' => __('We are about to change your <em>.htaccess</em> file. Please make a backup of this file before proceeding.', 'wordfence'),
			'We can\'t modify your .htaccess file for you because: %s' => /* translators: Error message. */ __('We can\'t modify your .htaccess file for you because: %s', 'wordfence'),
			'We encountered a problem' => __('We encountered a problem', 'wordfence'),
			'Wordfence Firewall blocked a background request to WordPress for the URL %s. If this occurred as a result of an intentional action, you may consider allowlisting the request to allow it in the future.' => /* translators: URL. */ __('Wordfence Firewall blocked a background request to WordPress for the URL %s. If this occurred as a result of an intentional action, you may consider allowlisting the request to allow it in the future.', 'wordfence'),
			'Wordfence is working...' => __('Wordfence is working...', 'wordfence'),
			'You are using Nginx as your web server. You\'ll need to disable autoindexing in your nginx.conf. See the <a target=\'_blank\'  rel=\'noopener noreferrer\' href=\'https://nginx.org/en/docs/http/ngx_http_autoindex_module.html\'>Nginx docs for more info</a> on how to do this.' => __('You are using Nginx as your web server. You\'ll need to disable autoindexing in your nginx.conf. See the <a target=\'_blank\'  rel=\'noopener noreferrer\' href=\'https://nginx.org/en/docs/http/ngx_http_autoindex_module.html\'>Nginx docs for more info</a> on how to do this.', 'wordfence'),
			'You are using an Nginx web server and using a FastCGI processor like PHP5-FPM. You will need to manually delete or hide those files.' => __('You are using an Nginx web server and using a FastCGI processor like PHP5-FPM. You will need to manually delete or hide those files.', 'wordfence'),
			'You are using an Nginx web server and using a FastCGI processor like PHP5-FPM. You will need to manually modify your php.ini to disable <em>display_error</em>' => __('You are using an Nginx web server and using a FastCGI processor like PHP5-FPM. You will need to manually modify your php.ini to disable <em>display_error</em>', 'wordfence'),
			'You forgot to include a reason you\'re blocking this IP range. We ask you to include this for your own record keeping.' => __('You forgot to include a reason you\'re blocking this IP range. We ask you to include this for your own record keeping.', 'wordfence'),
			'You have unsaved changes to your options. If you leave this page, those changes will be lost.' => __('You have unsaved changes to your options. If you leave this page, those changes will be lost.', 'wordfence'),
			'Your .htaccess has been updated successfully. Please verify your site is functioning normally.' => __('Your .htaccess has been updated successfully. Please verify your site is functioning normally.', 'wordfence'),
			'Your Wordfence activity log was sent to %s' => /* translators: Email address. */ __('Your Wordfence activity log was sent to %s', 'wordfence'),
			'Your rules have been updated successfully.' => __('Your rules have been updated successfully.', 'wordfence'),
			'Your rules have been updated successfully. You are currently using the free version of Wordfence. Upgrade to Wordfence premium to have your rules updated automatically as new threats emerge. <a href="https://www.wordfence.com/wafUpdateRules1/wordfence-signup/">Click here to purchase a premium license</a>. <em>Note: Your rules will still update every 30 days as a free user.</em>' => __('Your rules have been updated successfully. You are currently using the free version of Wordfence. Upgrade to Wordfence premium to have your rules updated automatically as new threats emerge. <a href="https://www.wordfence.com/wafUpdateRules1/wordfence-signup/">Click here to purchase a premium license</a>. <em>Note: Your rules will still update every 30 days as a free user.</em>', 'wordfence'),
			'Your test email was sent to the requested email address. The result we received from the WordPress wp_mail() function was: %s<br /><br />A \'True\' result means WordPress thinks the mail was sent without errors. A \'False\' result means that WordPress encountered an error sending your mail. Note that it\'s possible to get a \'True\' response with an error elsewhere in your mail system that may cause emails to not be delivered.' => /* translators: wp_mail() return value. */ __('Your test email was sent to the requested email address. The result we received from the WordPress wp_mail() function was: %s<br /><br />A \'True\' result means WordPress thinks the mail was sent without errors. A \'False\' result means that WordPress encountered an error sending your mail. Note that it\'s possible to get a \'True\' response with an error elsewhere in your mail system that may cause emails to not be delivered.', 'wordfence'),
			'blocked by firewall' => __('blocked by firewall', 'wordfence'),
			'blocked by firewall for %s' => /* translators: Reason for firewall action. */ __('blocked by firewall for %s', 'wordfence'),
			'blocked by real-time IP blocklist' => __('blocked by real-time IP blocklist', 'wordfence'),
			'blocked by the Wordfence Security Network' => __('blocked by the Wordfence Security Network', 'wordfence'),
			'blocked for %s' => /* translators: Reason for firewall action. */ __('blocked for %s', 'wordfence'),
			'locked out from logging in' => __('locked out from logging in', 'wordfence'),
		));
	}
	public static function showTOUPPOverlay($classList) {
		return trim($classList . ' wf-toupp-required');
	}
	public static function activation_warning(){
		$activationError = get_option('wf_plugin_act_error', '');
		if(strlen($activationError) > 400){
			$activationError = substr($activationError, 0, 400) . '...[output truncated]';
		}
		if($activationError){
			echo '<div id="wordfenceConfigWarning" class="updated fade"><p><strong>' .
			     __('Wordfence generated an error on activation. The output we received during activation was:', 'wordfence')
			     . '</strong> ' . wp_kses($activationError, array()) . '</p></div>';
		}
		delete_option('wf_plugin_act_error');
	}
	public static function noKeyError(){
		echo '<div id="wordfenceConfigWarning" class="fade error"><p>' .
		     sprintf('<strong>%s</strong> ', __("Wordfence's license key is missing.", 'wordfence')) .
		     wp_kses(sprintf(__("This could be caused by a database problem. You may need to repair your \"wfconfig\" database table or fix your database user's privileges if they have changed recently, or you may need to reinstall Wordfence. Please <a href=\"%s\" target=\"_blank\" rel=\"noopener noreferrer\">contact Wordfence support<span class=\"screen-reader-text\"> (" . esc_html__('opens in new tab', 'wordfence') . ")</span></a> if you need help.", 'wordfence'), wfSupportController::esc_supportURL()), array('a' => array('href' => array(), 'target' => array(), 'rel' => array()), 'span' => array('class' => array())))
		     . '</p></div>';
	}
	public static function wafConfigInaccessibleNotice() {
		if (function_exists('network_admin_url') && is_multisite()) {
			$wafMenuURL = network_admin_url('admin.php?page=WordfenceWAF&wafconfigrebuild=1');
		}
		else {
			$wafMenuURL = admin_url('admin.php?page=WordfenceWAF&wafconfigrebuild=1');
		}
		$wafMenuURL = add_query_arg(array(
			'waf-nonce' => wp_create_nonce('wafconfigrebuild'),
		), $wafMenuURL);

		echo '<div id="wafConfigInaccessibleNotice" class="fade error"><p><strong>' . __('The Wordfence Web Application Firewall cannot run.', 'wordfence') . '</strong> ' .
		     sprintf(
		     /* translators: 1. WordPress admin panel URL. 2. Support URL. */
			     __('The configuration files are corrupt or inaccessible by the web server, which is preventing the WAF from functioning. Please verify the web server has permission to access the configuration files. You may also try to rebuild the configuration file by <a href="%1$s">clicking here</a>. It will automatically resume normal operation when it is fixed. <a class="wfhelp" target="_blank" rel="noopener noreferrer" href="%2$s"><span class="screen-reader-text"> (' . esc_html__('opens in new tab', 'wordfence') . ')</span></a>', 'wordfence'),
			     $wafMenuURL,
			     wfSupportController::esc_supportURL(wfSupportController::ITEM_NOTICE_WAF_INACCESSIBLE_CONFIG)
		     ) . '</p></div>';
	}
	public static function wafStorageEngineFallbackNotice() {
		echo '<div class="notice notice-warning"><p>'.__('The WAF storage engine is currently set to mysqli, but Wordfence is unable to use the database. The WAF will fall back to using local file system storage instead.', 'wordfence').'</p></div>';
	}
	public static function wafConfigNeedsUpdate_mod_php() {
		if (function_exists('network_admin_url') && is_multisite()) {
			$wafMenuURL = network_admin_url('admin.php?page=WordfenceWAF&wafconfigfixmodphp=1');
		}
		else {
			$wafMenuURL = admin_url('admin.php?page=WordfenceWAF&wafconfigfixmodphp=1');
		}
		$wafMenuURL = add_query_arg(array(
			'waf-nonce' => wp_create_nonce('wafconfigfixmodphp'),
		), $wafMenuURL);

		echo '<div id="wafConfigNeedsUpdateNotice" class="fade error"><p><strong>' . __('The Wordfence Web Application Firewall needs a configuration update.', 'wordfence') . '</strong> ' .
		     sprintf(
		     /* translators: 1. WordPress admin panel URL. 2. Support URL. */
			     __('It is currently configured to use an older version of PHP and may become deactivated if PHP is updated. You may perform the configuration update automatically by <a href="%1$s">clicking here</a>. <a class="wfhelp" target="_blank" rel="noopener noreferrer" href="%2$s"><span class="screen-reader-text"> (' . esc_html__('opens in new tab', 'wordfence') . ')</span></a>', 'wordfence'),
			     $wafMenuURL,
			     wfSupportController::esc_supportURL(wfSupportController::ITEM_NOTICE_WAF_MOD_PHP_FIX)
		     ) . '</p></div>';
	}
	public static function wafConfigNeedsFixed_mod_php() {
		if (function_exists('network_admin_url') && is_multisite()) {
			$wafMenuURL = network_admin_url('admin.php?page=WordfenceWAF&wafconfigfixmodphp=1');
		}
		else {
			$wafMenuURL = admin_url('admin.php?page=WordfenceWAF&wafconfigfixmodphp=1');
		}
		$wafMenuURL = add_query_arg(array(
			'waf-nonce' => wp_create_nonce('wafconfigfixmodphp'),
		), $wafMenuURL);

		echo '<div id="wafConfigNeedsFixedNotice" class="fade error"><p><strong>' . __('The Wordfence Web Application Firewall needs a configuration update.', 'wordfence') . '</strong> ' .
		     sprintf(
		     /* translators: 1. WordPress admin panel URL. 2. Support URL. */
			     __('It is not currently in extended protection mode but was configured to use an older version of PHP and may have become deactivated when PHP was updated. You may perform the configuration update automatically by <a href="%1$s">clicking here</a> or use the "Optimize the Wordfence Firewall" button on the Firewall Options page. <a class="wfhelp" target="_blank" rel="noopener noreferrer" href="%2$s"><span class="screen-reader-text"> (' . esc_html__('opens in new tab', 'wordfence') . ')</span></a>', 'wordfence'),
			     $wafMenuURL,
			     wfSupportController::esc_supportURL(wfSupportController::ITEM_NOTICE_WAF_MOD_PHP_FIX)
		     ) . '</p></div>';
	}
	public static function _retargetWordfenceSubmenuCallout() {
		echo <<<JQUERY
<script type="text/javascript">
jQuery(document).ready(function($) {
	$('#wfMenuCallout').closest('a').attr('target', '_blank').attr('rel', 'noopener noreferrer');
});
</script>
JQUERY;

	}

	public static function fsActionRestoreFileCallback() {
		$issueID = filter_input(INPUT_GET, 'issueID', FILTER_SANITIZE_NUMBER_INT);
		$response = self::ajax_restoreFile_callback($issueID);
		if (!empty($response['ok'])) {
			$result = sprintf('<p>' . /* translators: File path. */ __('The file <code>%s</code> was restored successfully.', 'wordfence') . '</p>',
				esc_html(strpos($response['file'], ABSPATH) === 0 ? substr($response['file'], strlen(ABSPATH) + 1) : $response['file']));
		} else if (!empty($response['cerrorMessage'])) {
			$result = sprintf('<div class="wfSummaryErr">%s</div>', esc_html($response['cerrorMessage']));
		} else {
			$result = '<div class="wfSummaryErr">' . __('There was an error restoring the file.', 'wordfence') . '</div>';
		}
		printf(<<<HTML
<br>
%s
<p><a href="%s">%s</a></p>
HTML
			,
			$result,
			esc_url(network_admin_url('admin.php?page=WordfenceScan')),
			__('Return to scan results', 'wordfence')
		);
		wfScanEngine::refreshScanNotification();
	}

	public static function fsActionDeleteFileCallback() {
		$issueID = filter_input(INPUT_GET, 'issueID', FILTER_SANITIZE_NUMBER_INT);
		$response = self::ajax_deleteFile_callback($issueID);
		if (!empty($response['ok'])) {
			$result = sprintf('<p>' . /* translators: File path. */ __('The file <code>%s</code> was deleted successfully.', 'wordfence') . '</p>', esc_html($response['file']));
		} else if (!empty($response['errorMessage'])) {
			$result = sprintf('<div class="wfSummaryErr">%s</div>', esc_html($response['errorMessage']));
		} else {
			$result = '<div class="wfSummaryErr">' . __('There was an error deleting the file.', 'wordfence') . '</div>';
		}
		printf(<<<HTML
<br>
%s
<p><a href="%s">%s</a></p>
HTML
			,
			$result,
			esc_url(network_admin_url('admin.php?page=WordfenceScan')),
			__('Return to scan results', 'wordfence')
		);
		wfScanEngine::refreshScanNotification();
	}

	public static function status($level /* 1 has highest visibility */, $type /* info|error */, $msg){
		if($level > 3 && $level < 10 && (! self::isDebugOn())){ //level 10 and higher is for summary messages
			return false;
		}
		if($type != 'info' && $type != 'error'){ error_log("Invalid status type: $type"); return; }
		if(self::$printStatus){
			echo "STATUS: $level : $type : ".esc_html($msg)."\n";
		} else {
			self::getLog()->addStatus($level, $type, $msg);
		}
	}
	public static function profileUpdateAction($userID, $newDat = false){
		if(! $newDat){ return; }
		if(wfConfig::get('other_pwStrengthOnUpdate')){
			$oldDat = get_userdata($userID);
			if($newDat->user_pass != $oldDat->user_pass){
				$wf = new wfScanEngine();
				$wf->scanUserPassword($userID);
				$wf->emailNewIssues();
			}
		}
	}

	public static function replaceVersion($url) {
		if (is_string($url))
			return preg_replace_callback("/([&;\?]ver)=(.+?)(&|$)/", "wordfence::replaceVersionCallback", $url);
		return $url;
	}

	public static function replaceVersionCallback($matches) {
		global $wp_version;
		return $matches[1] . '=' . ($wp_version === $matches[2] ? wp_hash($matches[2]) : $matches[2]) . $matches[3];
	}

	public static function genFilter($gen, $type){
		if(wfConfig::get('other_hideWPVersion')){
			return '';
		} else {
			return $gen;
		}
	}
	public static function getMyHomeURL(){
		return wfUtils::wpAdminURL('admin.php?page=Wordfence');
	}
	public static function getMyOptionsURL(){
		return wfUtils::wpAdminURL('admin.php?page=Wordfence&subpage=global_options');
	}

	public static function alert($subject, $alertMsg, $IP) {
		wfConfig::inc('totalAlertsSent');
		$emails = wfConfig::getAlertEmails();
		if (sizeof($emails) < 1) { return false; }

		$IPMsg = "";
		if ($IP) {
			$IPMsg = sprintf(/* translators: IP address. */ __("User IP: %s\n", 'wordfence'), $IP);
			$reverse = wfUtils::reverseLookup($IP);
			if ($reverse) {
				$IPMsg .= sprintf(/* translators: Domain name. */ __("User hostname: %s\n", 'wordfence'), $reverse);
			}
			$userLoc = wfUtils::getIPGeo($IP);
			if ($userLoc) {
				$IPMsg .= __('User location: ', 'wordfence');
				if ($userLoc['city']) {
					$IPMsg .= $userLoc['city'] . ', ';
				}
				if ($userLoc['region'] && wfUtils::shouldDisplayRegion($userLoc['countryName'])) {
					$IPMsg .= $userLoc['region'] . ', ';
				}
				$IPMsg .= $userLoc['countryName'] . "\n";
			}
		}

		$content = wfUtils::tmpl('email_genericAlert.php', array(
			'isPaid' => wfConfig::get('isPaid'),
			'subject' => $subject,
			'blogName' => get_bloginfo('name', 'raw'),
			'adminURL' => get_admin_url(),
			'alertMsg' => $alertMsg,
			'IPMsg' => $IPMsg,
			'date' => wfUtils::localHumanDate(),
			'myHomeURL' => self::getMyHomeURL(),
			'myOptionsURL' => self::getMyOptionsURL()
		));
		$shortSiteURL = preg_replace('/^https?:\/\//i', '', site_url());
		$subject = "[Wordfence Alert] $shortSiteURL " . $subject;

		$sendMax = wfConfig::get('alert_maxHourly', 0);
		if($sendMax > 0){
			$sendArr = wfConfig::get_ser('alertFreqTrack', array());
			if(! is_array($sendArr)){
				$sendArr = array();
			}
			$minuteTime = floor(time() / 60);
			$totalSent = 0;
			for($i = $minuteTime; $i > $minuteTime - 60; $i--){
				$totalSent += isset($sendArr[$i]) ? $sendArr[$i] : 0;
			}
			if($totalSent >= $sendMax){
				return false;
			}
			$sendArr[$minuteTime] = isset($sendArr[$minuteTime]) ? $sendArr[$minuteTime] + 1 : 1;
			wfConfig::set_ser('alertFreqTrack', $sendArr);
		}
		//Prevent duplicate emails within 1 hour:
		$hash = md5(implode(',', $emails) . ':' . $subject . ':' . $alertMsg . ':' . $IP); //Hex
		$lastHash = wfConfig::get('lastEmailHash', false);
		if($lastHash){
			$lastHashDat = explode(':', $lastHash); //[time, hash]
			if(time() - $lastHashDat[0] < 3600){
				if($lastHashDat[1] == $hash){
					return false; //Don't send because this email is identical to the previous email which was sent within the last hour.
				}
			}
		}
		wfConfig::set('lastEmailHash', time() . ':' . $hash);
		foreach ($emails as $email) {
			$uniqueContent = $content . "\n\n" . sprintf(/* translators: WordPress admin panel URL. */ __('No longer an administrator for this site? Click here to stop receiving security alerts: %s', 'wordfence'), wfUtils::getSiteBaseURL() . '?_wfsf=removeAlertEmail&jwt=' . wfUtils::generateJWT(array('email' => $email)));
			wp_mail($email, $subject, $uniqueContent);
		}
		return true;
	}
	public static function getLog(){
		if(! self::$wfLog){
			$wfLog = wfLog::shared();
			self::$wfLog = $wfLog;
		}
		return self::$wfLog;
	}
	public static function wfSchemaExists(){
		global $wpdb;
		$exists = $wpdb->get_col($wpdb->prepare(<<<SQL
SELECT TABLE_NAME FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE()
AND TABLE_NAME=%s
SQL
			, wfDB::networkTable('wfConfig')));
		return $exists ? true : false;
	}
	public static function isDebugOn(){
		if(is_null(self::$debugOn)){
			if(wfConfig::get('debugOn')){
				self::$debugOn = true;
			} else {
				self::$debugOn = false;
			}
		}
		return self::$debugOn;
	}
	//PUBLIC API
	public static function doNotCache(){ //Call this to prevent Wordfence from caching the current page.
		wfCache::doNotCache();
		return true;
	}
	public static function whitelistIP($IP){ //IP as a string in dotted quad notation e.g. '10.11.12.13'
		$IP = trim($IP);
		$user_range = new wfUserIPRange($IP);
		if (!$user_range->isValidRange()) {
			throw new Exception(__("The IP you provided must be in dotted quad notation or use ranges with square brackets. e.g. 10.11.12.13 or 10.11.12.[1-50]", 'wordfence'));
		}
		$whites = wfConfig::get('whitelisted', '');
		$arr = explode(',', $whites);
		$arr2 = array();
		foreach($arr as $e){
			if($e == $IP){
				return false;
			}
			$arr2[] = trim($e);
		}
		$arr2[] = $IP;
		wfConfig::set('whitelisted', implode(',', $arr2));
		return true;
	}

	public static function ajax_email_summary_email_address_debug_callback() {
		$email = !empty($_REQUEST['email']) ? $_REQUEST['email'] : null;
		if (!wfUtils::isValidEmail($email)) {
			return array('result' => __('Invalid email address provided', 'wordfence'));
		}

		$report = new wfActivityReport();
		return $report->sendReportViaEmail($email) ?
			array('ok' => 1, 'result' => __('Test email sent successfully', 'wordfence')) :
			array('result' => __("Test email failed to send", 'wordfence'));
	}

	public static function addDashboardWidget() {
		if (wfUtils::isAdmin() && (is_network_admin() || !is_multisite()) && wfConfig::get('email_summary_dashboard_widget_enabled')) {
			wp_enqueue_style('wordfence-activity-report-widget', wfUtils::getBaseURL() . wfUtils::versionedAsset('css/activity-report-widget.css'), '', WORDFENCE_VERSION);
			$report_date_range = 'week';
			switch (wfConfig::get('email_summary_interval')) {
				case 'daily':
					$report_date_range = 'day';
					break;

				case 'monthly':
					$report_date_range = 'month';
					break;
			}
			wp_add_dashboard_widget(
				'wordfence_activity_report_widget',
				sprintf(/* translators: Localized date range. */ __('Wordfence activity in the past %s', 'wordfence'), $report_date_range),
				array('wfActivityReport', 'outputDashboardWidget')
			);
		}
	}

	/**
	 * @return bool
	 */
	public static function hasGDLimitLoginsMUPlugin() {
		return defined('GD_SYSTEM_PLUGIN_DIR') && file_exists(GD_SYSTEM_PLUGIN_DIR . 'limit-login-attempts/limit-login-attempts.php')
		       && defined('LIMIT_LOGIN_DIRECT_ADDR');
	}

	/**
	 * @param string $content
	 * @return string
	 */
	public static function fixGDLimitLoginsErrors($content) {
		if (self::$authError) {
			$content = str_replace(wp_kses(__('<strong>ERROR</strong>: Incorrect username or password.', 'limit-login-attempts'), array('strong'=>array())) . "<br />\n", '', $content);
			$content .= '<br />' . self::$authError->get_error_message();
		}
		return $content;
	}

	/**
	 * @return array
	 */
	public static function ajax_deleteAdminUser_callback() {
		/** @var wpdb $wpdb */
		global $wpdb;
		$issueID = absint(!empty($_POST['issueID']) ? $_POST['issueID'] : 0);
		$wfIssues = new wfIssues();
		$issue = $wfIssues->getIssueByID($issueID);
		if (!$issue) {
			return array('errorMsg' => __("We could not find that issue in our database.", 'wordfence'));
		}
		$data = $issue['data'];
		if (empty($data['userID'])) {
			return array('errorMsg' => __("We could not find that user in the database.", 'wordfence'));
		}
		$user = new WP_User($data['userID']);
		if (!$user->exists()) {
			return array('errorMsg' => __("We could not find that user in the database.", 'wordfence'));
		}
		$userLogin = $user->user_login;
		if (is_multisite() && strcasecmp($user->user_email, get_site_option('admin_email')) === 0) {
			return array('errorMsg' => __("This user's email is the network admin email. It will need to be changed before deleting this user.", 'wordfence'));
		}
		if (is_multisite()) {
			revoke_super_admin($data['userID']);
		}
		wp_delete_user($data['userID']);
		if (is_multisite()) {
			$wpdb->delete($wpdb->users, array('ID' => $data['userID']));
		}
		$wfIssues->deleteIssue($issueID);
		wfScanEngine::refreshScanNotification($wfIssues);

		return array(
			'ok'         => 1,
			'user_login' => $userLogin,
		);
	}

	public static function ajax_revokeAdminUser_callback() {
		$issueID = absint(!empty($_POST['issueID']) ? $_POST['issueID'] : 0);
		$wfIssues = new wfIssues();
		$issue = $wfIssues->getIssueByID($issueID);
		if (!$issue) {
			return array('errorMsg' => __("We could not find that issue in our database.", 'wordfence'));
		}
		$data = $issue['data'];
		if (empty($data['userID'])) {
			return array('errorMsg' => __("We could not find that user in the database.", 'wordfence'));
		}
		$user = new WP_User($data['userID']);
		$userLogin = $user->user_login;
		wp_revoke_user($data['userID']);
		if (is_multisite()) {
			revoke_super_admin($data['userID']);
		}

		$wfIssues->deleteIssue($issueID);
		wfScanEngine::refreshScanNotification($wfIssues);

		return array(
			'ok'         => 1,
			'user_login' => $userLogin,
		);
	}

	public static function ajax_acknowledgeAdminUser_callback() {
		$issueID = absint(!empty($_POST['issueID']) ? $_POST['issueID'] : 0);
		$wfIssues = new wfIssues();
		$issue = $wfIssues->getIssueByID($issueID);
		if (!$issue) {
			return array('errorMsg' => __("We could not find that issue in the database.", 'wordfence'));
		}
		$data = $issue['data'];
		if (empty($data['userID'])) {
			return array('errorMsg' => __("We could not find that user in the database.", 'wordfence'));
		}
		$user = new WP_User($data['userID']);
		if (!$user->exists()) {
			return array('errorMsg' => __("We could not find that user in the database.", 'wordfence'));
		}
		$userLogin = $user->user_login;

		$adminUsers = new wfAdminUserMonitor();
		$adminUsers->addAdmin($data['userID']);

		$wfIssues->deleteIssue($issueID);
		wfScanEngine::refreshScanNotification($wfIssues);

		return array(
			'ok'         => 1,
			'user_login' => $userLogin,
		);
	}

	/**
	 *
	 */
	public static function ajax_disableDirectoryListing_callback() {
		$issueID = absint($_POST['issueID']);
		$wfIssues = new wfIssues();
		$issue = $wfIssues->getIssueByID($issueID);
		if (!$issue) {
			return array(
				'err'      => 1,
				'errorMsg' => __("We could not find that issue in our database.", 'wordfence'),
			);
		}
		$wfIssues->deleteIssue($issueID);

		$htaccessPath = wfCache::getHtaccessPath();
		if (!$htaccessPath) {
			return array(
				'err'      => 1,
				'errorMsg' => __("Wordfence could not find your .htaccess file.", 'wordfence'),
			);
		}

		$fileContents = file_get_contents($htaccessPath);
		if (file_put_contents($htaccessPath, "# Added by Wordfence " . date('r') . "\nOptions -Indexes\n\n" . $fileContents, LOCK_EX)) {
			$uploadPaths = wp_upload_dir();
			if (!wfScanEngine::isDirectoryListingEnabled($uploadPaths['baseurl'])) {
				return array(
					'ok' => 1,
				);
			} else {
				// Revert any changes done to .htaccess
				file_put_contents($htaccessPath, $fileContents, LOCK_EX);
				return array(
					'err'      => 1,
					'errorMsg' => __("Updating the .htaccess did not fix the issue. You may need to add <code>Options -Indexes</code> to your httpd.conf if using Apache, or find documentation on how to disable directory listing for your web server.", 'wordfence'),
				);
			}
		}
		return array(
			'err'      => 1,
			'errorMsg' => __("There was an error writing to your .htaccess file.", 'wordfence'),
		);
	}

	/**
	 * Modify the query to prevent username enumeration.
	 *
	 * @param array $query_vars
	 * @return array
	 */
	public static function preventAuthorNScans($query_vars) {
		if (wfConfig::get('loginSec_disableAuthorScan') && !is_admin() &&
		    !empty($query_vars['author']) && (is_array($query_vars['author']) || is_numeric(preg_replace('/[^0-9]/', '', $query_vars['author']))) &&
		    (
			    (isset($_GET['author']) && (is_array($_GET['author']) || is_numeric(preg_replace('/[^0-9]/', '', $_GET['author'])))) ||
			    (isset($_POST['author']) && (is_array($_POST['author']) || is_numeric(preg_replace('/[^0-9]/', '', $_POST['author']))))
		    )
		) {
			global $wp_query;
			$wp_query->set_404();
			status_header(404);
			nocache_headers();

			$template = get_404_template();
			if ($template && file_exists($template)) {
				include($template);
			}

			exit;
		}
		return $query_vars;
	}

	/**
	 * @param WP_Upgrader $updater
	 * @param array $hook_extra
	 */
	public static function hideReadme($updater, $hook_extra = null) {
		if (wfConfig::get('other_hideWPVersion')) {
			wfUtils::hideReadme();
		}
	}

	public static function ajax_saveDisclosureState_callback() {
		if (isset($_POST['name']) && isset($_POST['state'])) {
			$name = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['name']);
			$state = wfUtils::truthyToBoolean($_POST['state']);
			if (!empty($name)) {
				$disclosureStates = wfConfig::get_ser('disclosureStates', array());
				$disclosureStates[$name] = $state;
				wfConfig::set_ser('disclosureStates', $disclosureStates);
				return array('ok' => 1);
			}
		}
		else if (isset($_POST['names']) && isset($_POST['state'])) {
			$rawNames = $_POST['names'];
			if (is_array($rawNames)) {
				$filteredNames = array();
				foreach ($rawNames as $name) {
					$name = preg_replace('/[^a-zA-Z0-9_\-]/', '', $name);
					if (!empty($name)) {
						$filteredNames[] = $name;
					}
				}

				$state = wfUtils::truthyToBoolean($_POST['state']);
				if (!empty($filteredNames)) {
					$disclosureStates = wfConfig::get_ser('disclosureStates', array());
					foreach ($filteredNames as $name) {
						$disclosureStates[$name] = $state;
					}
					wfConfig::set_ser('disclosureStates', $disclosureStates);
					return array('ok' => 1);
				}
			}
		}

		return array(
			'err'      => 1,
			'errorMsg' => __("Required parameters not sent.", 'wordfence'),
		);
	}

	public static function ajax_saveWAFConfig_callback() {
		if (isset($_POST['wafConfigAction'])) {
			$waf = wfWAF::getInstance();
			if (method_exists($waf, 'isReadOnly') && $waf->isReadOnly()) {
				return array(
					'err'      => 1,
					'errorMsg' => __("The WAF is currently in read-only mode and will not save any configuration changes.", 'wordfence'),
				);
			}

			switch ($_POST['wafConfigAction']) {
				case 'config':
					if (!empty($_POST['wafStatus']) && in_array($_POST['wafStatus'], array(wfFirewall::FIREWALL_MODE_DISABLED, wfFirewall::FIREWALL_MODE_LEARNING, wfFirewall::FIREWALL_MODE_ENABLED))) {
						if ($_POST['wafStatus'] == 'learning-mode' && !empty($_POST['learningModeGracePeriodEnabled'])) {
							$gracePeriodEnd = strtotime(isset($_POST['learningModeGracePeriod']) ? $_POST['learningModeGracePeriod'] : '');
							if ($gracePeriodEnd > time()) {
								wfWAF::getInstance()->getStorageEngine()->setConfig('learningModeGracePeriodEnabled', 1);
								wfWAF::getInstance()->getStorageEngine()->setConfig('learningModeGracePeriod', $gracePeriodEnd);
							} else {
								return array(
									'err'      => 1,
									'errorMsg' => __("The grace period end time must be in the future.", 'wordfence'),
								);
							}
						} else {
							wfWAF::getInstance()->getStorageEngine()->setConfig('learningModeGracePeriodEnabled', 0);
							wfWAF::getInstance()->getStorageEngine()->unsetConfig('learningModeGracePeriod');
						}
						wfWAF::getInstance()->getStorageEngine()->setConfig('wafStatus', $_POST['wafStatus']);
						$firewall = new wfFirewall();
						$firewall->syncStatus(true);
					}

					break;

				case 'addWhitelist':
					if (isset($_POST['whitelistedPath']) && isset($_POST['whitelistedParam'])) {
						$path = stripslashes($_POST['whitelistedPath']);
						$paramKey = stripslashes($_POST['whitelistedParam']);
						if (!$path || !$paramKey) {
							break;
						}
						$data = array(
							'timestamp'   => time(),
							'description' => __('Allowlisted via Firewall Options page', 'wordfence'),
							'ip'          => wfUtils::getIP(),
							'disabled'    => empty($_POST['whitelistedEnabled']),
						);
						if (function_exists('get_current_user_id')) {
							$data['userID'] = get_current_user_id();
						}
						wfWAF::getInstance()->whitelistRuleForParam($path, $paramKey, 'all', $data);
					}
					break;

				case 'replaceWhitelist':
					if (
						!empty($_POST['oldWhitelistedPath']) && !empty($_POST['oldWhitelistedParam']) &&
						!empty($_POST['newWhitelistedPath']) && !empty($_POST['newWhitelistedParam'])
					) {
						$oldWhitelistedPath = stripslashes($_POST['oldWhitelistedPath']);
						$oldWhitelistedParam = stripslashes($_POST['oldWhitelistedParam']);

						$newWhitelistedPath = stripslashes($_POST['newWhitelistedPath']);
						$newWhitelistedParam = stripslashes($_POST['newWhitelistedParam']);

						$savedWhitelistedURLParams = (array) wfWAF::getInstance()->getStorageEngine()->getConfig('whitelistedURLParams', null, 'livewaf');
						// These are already base64'd
						$oldKey = $oldWhitelistedPath . '|' . $oldWhitelistedParam;
						$newKey = base64_encode($newWhitelistedPath) . '|' . base64_encode($newWhitelistedParam);
						try {
							$savedWhitelistedURLParams = wfUtils::arrayReplaceKey($savedWhitelistedURLParams, $oldKey, $newKey);
						} catch (Exception $e) {
							error_log("Caught exception from 'wfUtils::arrayReplaceKey' with message: " . $e->getMessage());
						}
						wfWAF::getInstance()->getStorageEngine()->setConfig('whitelistedURLParams', $savedWhitelistedURLParams, 'livewaf');
					}
					break;

				case 'deleteWhitelist':
					if (
						isset($_POST['deletedWhitelistedPath']) && is_string($_POST['deletedWhitelistedPath']) &&
						isset($_POST['deletedWhitelistedParam']) && is_string($_POST['deletedWhitelistedParam'])
					) {
						$deletedWhitelistedPath = stripslashes($_POST['deletedWhitelistedPath']);
						$deletedWhitelistedParam = stripslashes($_POST['deletedWhitelistedParam']);
						$savedWhitelistedURLParams = (array) wfWAF::getInstance()->getStorageEngine()->getConfig('whitelistedURLParams', null, 'livewaf');
						$key = $deletedWhitelistedPath . '|' . $deletedWhitelistedParam;
						unset($savedWhitelistedURLParams[$key]);
						wfWAF::getInstance()->getStorageEngine()->setConfig('whitelistedURLParams', $savedWhitelistedURLParams, 'livewaf');
					}
					break;

				case 'enableWhitelist':
					if (isset($_POST['whitelistedPath']) && isset($_POST['whitelistedParam'])) {
						$path = stripslashes($_POST['whitelistedPath']);
						$paramKey = stripslashes($_POST['whitelistedParam']);
						if (!$path || !$paramKey) {
							break;
						}
						$enabled = !empty($_POST['whitelistedEnabled']);

						$savedWhitelistedURLParams = (array) wfWAF::getInstance()->getStorageEngine()->getConfig('whitelistedURLParams', null, 'livewaf');
						$key = $path . '|' . $paramKey;
						if (array_key_exists($key, $savedWhitelistedURLParams) && is_array($savedWhitelistedURLParams[$key])) {
							foreach ($savedWhitelistedURLParams[$key] as $ruleID => $data) {
								$savedWhitelistedURLParams[$key][$ruleID]['disabled'] = !$enabled;
							}
						}
						wfWAF::getInstance()->getStorageEngine()->setConfig('whitelistedURLParams', $savedWhitelistedURLParams, 'livewaf');
					}
					break;

				case 'enableRule':
					$ruleEnabled = !empty($_POST['ruleEnabled']);
					$ruleID = !empty($_POST['ruleID']) ? (int) $_POST['ruleID'] : false;
					if ($ruleID) {
						$disabledRules = (array) wfWAF::getInstance()->getStorageEngine()->getConfig('disabledRules');
						if ($ruleEnabled) {
							unset($disabledRules[$ruleID]);
						} else {
							$disabledRules[$ruleID] = true;
						}
						wfWAF::getInstance()->getStorageEngine()->setConfig('disabledRules', $disabledRules);
					}
					break;
				case 'disableWAFBlacklistBlocking':
					if (isset($_POST['disableWAFBlacklistBlocking'])) {
						$disableWAFBlacklistBlocking = (int) $_POST['disableWAFBlacklistBlocking'];
						wfWAF::getInstance()->getStorageEngine()->setConfig('disableWAFBlacklistBlocking', $disableWAFBlacklistBlocking);
						if (method_exists(wfWAF::getInstance()->getStorageEngine(), 'purgeIPBlocks')) {
							wfWAF::getInstance()->getStorageEngine()->purgeIPBlocks(wfWAFStorageInterface::IP_BLOCKS_BLACKLIST);
						}
					}
					break;
			}
		}

		return array(
			'success' => true,
			'data'    => self::_getWAFData(),
		);
	}

	public static function ajax_updateWAFRules_callback() {
		try {
			$event = new wfWAFCronFetchRulesEvent(time() - 2, true);
			$event->setWaf(wfWAF::getInstance());
			$success = $event->fire();
			$failureReason = false;
			if (!$success && method_exists($event, 'getResponse')) {
				$response = $event->getResponse();
				if ($response === false) {
					$failureReason = wfFirewall::UPDATE_FAILURE_UNREACHABLE;
				}
				else {
					$jsonData = @json_decode($response->getBody(), true);
					if (isset($jsonData['errorMessage']) && strpos($jsonData['errorMessage'], 'rate limit') !== false) {
						$failureReason = wfFirewall::UPDATE_FAILURE_RATELIMIT;
					}
					else if (isset($jsonData['data']['signature'])) {
						$failureReason = wfFirewall::UPDATE_FAILURE_FILESYSTEM;
					}
				}
			}

			return self::_getWAFData($success, $failureReason);
		}
		catch (Exception $e) {
			$wafData = array(
				'learningMode' => false,
				'rules' => array(),
				'whitelistedURLParams' => array(),
				'disabledRules' => array(),
				'isPaid' => (bool) wfConfig::get('isPaid', 0),
			);

			return $wafData;
		}
	}

	public static function ajax_loadLiveTraffic_callback() {
		$return = array();

		$filters = new wfLiveTrafficQueryFilterCollection();
		$query = new wfLiveTrafficQuery(self::getLog());
		$query->setFilters($filters);
		if (array_key_exists('groupby', $_REQUEST)) {
			$param = $_REQUEST['groupby'];
			if ($param === 'type') {
				$param = 'jsRun';
			}
			$query->setGroupBy(new wfLiveTrafficQueryGroupBy($query, $param));
		}
		$query->setLimit(isset($_REQUEST['limit']) ? absint($_REQUEST['limit']) : 20);
		$query->setOffset(isset($_REQUEST['offset']) ? absint($_REQUEST['offset']) : 0);

		if (!empty($_REQUEST['since'])) {
			$query->setStartDate($_REQUEST['since']);
		} else if (!empty($_REQUEST['startDate'])) {
			$query->setStartDate(is_numeric($_REQUEST['startDate']) ? $_REQUEST['startDate'] : strtotime($_REQUEST['startDate']));
		}

		if (!empty($_REQUEST['endDate'])) {
			$query->setEndDate(is_numeric($_REQUEST['endDate']) ? $_REQUEST['endDate'] : strtotime($_REQUEST['endDate']));
		}

		if (
			array_key_exists('param', $_REQUEST) && is_array($_REQUEST['param']) &&
			array_key_exists('operator', $_REQUEST) && is_array($_REQUEST['operator']) &&
			array_key_exists('value', $_REQUEST) && is_array($_REQUEST['value'])
		) {
			for ($i = 0; $i < count($_REQUEST['param']); $i++) {
				if (
					array_key_exists($i, $_REQUEST['param']) &&
					array_key_exists($i, $_REQUEST['operator']) &&
					array_key_exists($i, $_REQUEST['value'])
				) {
					$param = $_REQUEST['param'][$i];
					$operator = $_REQUEST['operator'][$i];
					$value = $_REQUEST['value'][$i];

					switch (strtolower($param)) {
						case 'type':
							$param = 'jsRun';
							$value = strtolower($value) === 'human' ? 1 : 0;
							break;
						case 'ip':
							$ip = $value;

							if (strpos($ip, '*') !== false) { //If the IP contains a *, treat it as a wildcard for that segment and silently adjust the rule
								if (preg_match('/^(?:(?:\d{1,3}|\*)(?:\.|$)){2,4}/', $ip)) { //IPv4
									$value = array('00', '00', '00', '00', '00', '00', '00', '00', '00', '00', 'FF', 'FF');
									$octets = explode('.', $ip);
									foreach ($octets as $o)
									{
										if (strpos($o, '*') !== false) {
											$value[] = '..';
										}
										else {
											$value[] = strtoupper(str_pad(dechex($o), 2, '0', STR_PAD_LEFT));
										}
									}
									$value = '^' . implode('', array_pad($value, 16, '..')) . '$';
									$operator = ($operator == '!=' ? 'hnotregexp' : 'hregexp');
								}
								else if (!empty($ip) && preg_match('/^((?:[\da-f*]{1,4}(?::|)){0,8})(::)?((?:[\da-f*]{1,4}(?::|)){0,8})$/i', $ip)) { //IPv6
									if ($ip === '::') {
										$value = '^' . str_repeat('00', 16) . '$';
									}
									else {
										$colon_count = substr_count($ip, ':');
										$dbl_colon_pos = strpos($ip, '::');
										if ($dbl_colon_pos !== false) {
											$ip = str_replace('::', str_repeat(':0000', (($dbl_colon_pos === 0 || $dbl_colon_pos === strlen($ip) - 2) ? 9 : 8) - $colon_count) . ':', $ip);
											$ip = trim($ip, ':');
										}

										$ip_groups = explode(':', $ip);
										$value = array();
										foreach ($ip_groups as $ip_group) {
											if (strpos($ip_group, '*') !== false) {
												$value[] = '..';
												$value[] = '..';
											}
											else {
												$ip_group = strtoupper(str_pad($ip_group, 4, '0', STR_PAD_LEFT));
												$value[] = substr($ip_group, 0, 2);
												$value[] = substr($ip_group, -2);
											}
										}

										$value = '^' . implode('', array_pad($value, 16, '..')) . '$';
									}
									$operator = ($operator == '=' ? 'hregexp' : 'hnotregexp');
								}
								else if (preg_match('/^((?:0{1,4}(?::|)){0,5})(::)?ffff:((?:\d{1,3}(?:\.|$)){4})$/i', $ip, $matches)) { //IPv4 mapped IPv6
									$value = array('00', '00', '00', '00', '00', '00', '00', '00', '00', '00', 'FF', 'FF');
									$octets = explode('.', $matches[3]);
									foreach ($octets as $o)
									{
										if (strpos($o, '*') !== false) {
											$value[] = '..';
										}
										else {
											$value[] = strtoupper(str_pad(dechex($o), 2, '0', STR_PAD_LEFT));
										}
									}
									$value = '^' . implode('', array_pad($value, 16, '.')) . '$';
									$operator = ($operator == '=' ? 'hregexp' : 'hnotregexp');
								}
								else {
									$value = false;
								}
							}
							else {
								$value = wfUtils::inet_pton($ip);
							}
							break;
						case 'userid':
							$value = absint($value);
							break;
					}
					if ($operator === 'match' && $param !== 'ip') {
						$value = str_replace('*', '%', $value);
					}
					$filters->addFilter(new wfLiveTrafficQueryFilter($query, $param, $operator, $value));
				}
			}
		}

		try {
			$return['data'] = $query->execute();
			/*if (defined('WP_DEBUG') && WP_DEBUG) {
				$return['sql'] = $query->buildQuery();
			}*/
		} catch (wfLiveTrafficQueryException $e) {
			$return['data'] = array();
			$return['sql'] = $e->getMessage();
		}

		$return['success'] = true;

		return $return;
	}

	public static function ajax_whitelistWAFParamKey_callback() {
		if (class_exists('wfWAF') && $waf = wfWAF::getInstance()) {
			if (isset($_POST['path']) && isset($_POST['paramKey']) && isset($_POST['failedRules'])) {
				$data = array(
					'timestamp'   => time(),
					'description' => __('Allowlisted via Live Traffic', 'wordfence'),
					'source'	  => 'live-traffic',
					'ip'          => wfUtils::getIP(),
				);
				if (function_exists('get_current_user_id')) {
					$data['userID'] = get_current_user_id();
				}
				$waf->whitelistRuleForParam(base64_decode($_POST['path']), base64_decode($_POST['paramKey']),
					$_POST['failedRules'], $data);

				return array(
					'success' => true,
				);
			}
		}
		return false;
	}

	public static function ajax_whitelistBulkDelete_callback() {
		if (class_exists('wfWAF') && $waf = wfWAF::getInstance()) {
			if (!empty($_POST['items']) && ($items = json_decode(stripslashes($_POST['items']), true)) !== false) {
				$whitelist = (array) $waf->getStorageEngine()->getConfig('whitelistedURLParams', null, 'livewaf');
				if (!is_array($whitelist)) {
					$whitelist = array();
				}
				foreach ($items as $key) {
					list($path, $paramKey, ) = $key;
					$whitelistKey = $path . '|' . $paramKey;
					if (array_key_exists($whitelistKey, $whitelist)) {
						unset($whitelist[$whitelistKey]);
					}
				}
				$waf->getStorageEngine()->setConfig('whitelistedURLParams', $whitelist, 'livewaf');
				return array(
					'data'    => self::_getWAFData(),
					'success' => true,
				);
			}
		}
		return false;
	}

	public static function ajax_whitelistBulkEnable_callback() {
		if (class_exists('wfWAF') && $waf = wfWAF::getInstance()) {
			if (!empty($_POST['items']) && ($items = json_decode(stripslashes($_POST['items']), true)) !== false) {
				self::_whitelistBulkToggle($items, true);
				return array(
					'data'    => self::_getWAFData(),
					'success' => true,
				);
			}
		}
		return false;
	}

	public static function ajax_whitelistBulkDisable_callback() {
		if (class_exists('wfWAF') && $waf = wfWAF::getInstance()) {
			if (!empty($_POST['items']) && ($items = json_decode(stripslashes($_POST['items']), true)) !== false) {
				self::_whitelistBulkToggle($items, false);
				return array(
					'data'    => self::_getWAFData(),
					'success' => true,
				);
			}
		}
		return false;
	}

	private static function _whitelistBulkToggle($items, $enabled) {
		$waf = wfWAF::getInstance();
		$whitelist = (array) $waf->getStorageEngine()->getConfig('whitelistedURLParams', null, 'livewaf');
		if (!is_array($whitelist)) {
			$whitelist = array();
		}
		foreach ($items as $key) {
			list($path, $paramKey, ) = $key;
			$whitelistKey = $path . '|' . $paramKey;
			if (array_key_exists($whitelistKey, $whitelist) && is_array($whitelist[$whitelistKey])) {
				foreach ($whitelist[$whitelistKey] as $ruleID => $data) {
					$whitelist[$whitelistKey][$ruleID]['disabled'] = !$enabled;
				}
			}
		}
		$waf->getStorageEngine()->setConfig('whitelistedURLParams', $whitelist, 'livewaf');
	}

	private static function _getWAFData($updated = null, $failureReason = false) {
		$data['learningMode'] = wfWAF::getInstance()->isInLearningMode();
		$data['rules'] = wfWAF::getInstance()->getRules();
		/** @var wfWAFRule $rule */
		foreach ($data['rules'] as $ruleID => $rule) {
			$data['rules'][$ruleID] = $rule->toArray();
		}

		$whitelistedURLParams = (array) wfWAF::getInstance()->getStorageEngine()->getConfig('whitelistedURLParams', array(), 'livewaf');
		$data['whitelistedURLParams'] = array();
		if (is_array($whitelistedURLParams)) {
			foreach ($whitelistedURLParams as $urlParamKey => $rules) {
				list($path, $paramKey) = explode('|', $urlParamKey);
				$whitelistData = null;
				foreach ($rules as $ruleID => $whitelistedData) {
					if ($whitelistData === null) {
						$whitelistData = $whitelistedData;
						continue;
					}
					if ($ruleID === 'all') {
						$whitelistData = $whitelistedData;
						break;
					}
				}

				if (is_array($whitelistData) && array_key_exists('userID', $whitelistData) && function_exists('get_user_by')) {
					$user = get_user_by('id', $whitelistData['userID']);
					if ($user) {
						$whitelistData['username'] = $user->user_login;
					}
				}

				$data['whitelistedURLParams'][] = array(
					'path'     => $path,
					'paramKey' => $paramKey,
					'ruleID'   => array_keys($rules),
					'data'     => $whitelistData,
				);
			}
		}

		$data['disabledRules'] = (array) wfWAF::getInstance()->getStorageEngine()->getConfig('disabledRules');
		if ($lastUpdated = wfWAF::getInstance()->getStorageEngine()->getConfig('rulesLastUpdated', null, 'transient')) {
			$data['rulesLastUpdated'] = $lastUpdated;
		}
		$data['isPaid'] = (bool) wfConfig::get('isPaid', 0);
		if ($updated !== null) {
			$data['updated'] = (bool) $updated;
			if (!$updated) {
				$data['failure'] = $failureReason;
			}
		}
		return $data;
	}

	public static function ajax_wafStatus_callback() {
		if (!empty($_REQUEST['nonce']) && hash_equals($_REQUEST['nonce'], wfConfig::get('wafStatusCallbackNonce', ''))) {
			wfConfig::set('wafStatusCallbackNonce', '');
			wfUtils::send_json(array('active' => WFWAF_AUTO_PREPEND, 'subdirectory' => WFWAF_SUBDIRECTORY_INSTALL));
		}
		wfUtils::send_json(false);
	}

	public static function ajax_installAutoPrepend_callback() {
		global $wp_filesystem;

		$currentAutoPrependFile = ini_get('auto_prepend_file');
		$currentAutoPrepend = null;
		if (isset($_POST['currentAutoPrepend']) && !WF_IS_WP_ENGINE && !WF_IS_PRESSABLE && !WF_IS_FLYWHEEL) {
			$currentAutoPrepend = $_POST['currentAutoPrepend'];
		}

		$serverConfiguration = null;
		if (isset($_POST['serverConfiguration']) && wfWAFAutoPrependHelper::isValidServerConfig($_POST['serverConfiguration'])) {
			$serverConfiguration = $_POST['serverConfiguration'];
		}

		if ($serverConfiguration === null) {
			return array('errorMsg' => __('A valid server configuration was not provided.', 'wordfence'));
		}

		$helper = new wfWAFAutoPrependHelper($serverConfiguration, $currentAutoPrepend === 'override' ? null : $currentAutoPrependFile);

		ob_start();
		$ajaxURL = admin_url('admin-ajax.php');
		$allow_relaxed_file_ownership = true;
		if (false === ($credentials = request_filesystem_credentials($ajaxURL, '', false, ABSPATH, array('version', 'locale', 'action', 'serverConfiguration', 'currentAutoPrepend'), $allow_relaxed_file_ownership))) {
			$credentialsContent = ob_get_clean();
			$html = wfView::create('waf/waf-modal-wrapper', array(
				'title' => __('Filesystem Credentials Required', 'wordfence'),
				'html' => $credentialsContent,
				'helpHTML' => wp_kses(sprintf(/* translators: Support URL. */ __('If you cannot complete the setup process, <a target="_blank" rel="noopener noreferrer" href="%s">click here for help<span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'), wfSupportController::esc_supportURL(wfSupportController::ITEM_FIREWALL_WAF_INSTALL_MANUALLY)), array('a' => array('href' => array(), 'target' => array(), 'rel' => array()), 'span' => array('class' => array()))),
				'footerHTML' => esc_html__('Once you have entered credentials, click Continue to complete the setup.', 'wordfence'),
			))->render();
			return array('needsCredentials' => 1, 'html' => $html);
		}
		ob_end_clean();

		if (!WP_Filesystem($credentials, ABSPATH, $allow_relaxed_file_ownership) && $wp_filesystem->errors->get_error_code()) {
			$credentialsError = '';
			foreach ($wp_filesystem->errors->get_error_messages() as $message) {
				if (is_wp_error($message)) {
					if ($message->get_error_data() && is_string($message->get_error_data())) {
						$message = $message->get_error_message() . ': ' . $message->get_error_data();
					}
					else {
						$message = $message->get_error_message();
					}
				}
				$credentialsError .= "<p>$message</p>\n";
			}

			$html = wfView::create('waf/waf-modal-wrapper', array(
				'title' => __('Filesystem Permission Error', 'wordfence'),
				'html' => $credentialsError,
				'helpHTML' => wp_kses(sprintf(/* translators: Support URL. */ __('If you cannot complete the setup process, <a target="_blank" rel="noopener noreferrer" href="%s">click here for help<span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'), wfSupportController::esc_supportURL(wfSupportController::ITEM_FIREWALL_WAF_INSTALL_MANUALLY)), array('a' => array('href' => array(), 'target' => array(), 'rel' => array()), 'span' => array('class' => array()))),
				'footerButtonTitle' => __('Cancel', 'wordfence'),
			))->render();
			return array('credentialsFailed' => 1, 'html' => $html);
		}

		try {
			$helper->performInstallation($wp_filesystem);

			$nonce = bin2hex(wfWAFUtils::random_bytes(32));
			wfConfig::set('wafStatusCallbackNonce', $nonce);
			$verifyURL = add_query_arg(array('action' => 'wordfence_wafStatus', 'nonce' => $nonce), $ajaxURL);
			$response = wp_remote_get($verifyURL, array('headers' => array('Referer' => false/*, 'Cookie' => 'XDEBUG_SESSION=1'*/)));

			$active = false;
			if (!is_wp_error($response)) {
				$wafStatus = @json_decode(wp_remote_retrieve_body($response), true);
				if (isset($wafStatus['active']) && isset($wafStatus['subdirectory'])) {
					$active = $wafStatus['active'] && !$wafStatus['subdirectory'];
				}
			}

			if ($serverConfiguration == 'manual') {
				$html = wfView::create('waf/waf-modal-wrapper', array(
					'title' => __('Manual Installation Instructions', 'wordfence'),
					'html' => wfView::create('waf/waf-install-manual')->render(),
					'footerButtonTitle' => __('Close', 'wordfence'),
				))->render();
			}
			else {
				$html = wfView::create('waf/waf-modal-wrapper', array(
					'title' => __('Installation Successful', 'wordfence'),
					'html' => wfView::create('waf/waf-install-success', array('active' => $active))->render(),
					'footerButtonTitle' => __('Close', 'wordfence'),
				))->render();
			}

			return array('ok' => 1, 'html' => $html);
		}
		catch (wfWAFAutoPrependHelperException $e) {
			$installError = "<p>" . $e->getMessage() . "</p>";
			$html = wfView::create('waf/waf-modal-wrapper', array(
				'title' => __('Installation Failed', 'wordfence'),
				'html' => $installError,
				'helpHTML' => wp_kses(sprintf(/* translators: Support URL. */ __('If you cannot complete the setup process, <a target="_blank" rel="noopener noreferrer" href="%s">click here for help<span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'), wfSupportController::esc_supportURL(wfSupportController::ITEM_FIREWALL_WAF_INSTALL_MANUALLY)), array('a' => array('href' => array(), 'target' => array(), 'rel' => array()), 'span' => array('class' => array()))),
				'footerButtonTitle' => __('Cancel', 'wordfence'),
			))->render();
			return array('installationFailed' => 1, 'html' => $html);
		}
	}

	public static function ajax_uninstallAutoPrepend_callback() {
		global $wp_filesystem;

		$serverConfiguration = null;
		if (isset($_POST['serverConfiguration']) && wfWAFAutoPrependHelper::isValidServerConfig($_POST['serverConfiguration'])) {
			$serverConfiguration = $_POST['serverConfiguration'];
		}

		if ($serverConfiguration === null) {
			return array('errorMsg' => __('A valid server configuration was not provided.', 'wordfence'));
		}

		$helper = new wfWAFAutoPrependHelper($serverConfiguration, null);

		if (isset($_POST['credentials']) && isset($_POST['credentialsSignature'])) {
			$salt = wp_salt('logged_in');
			$expectedSignature = hash_hmac('sha256', $_POST['credentials'], $salt);
			if (hash_equals($expectedSignature, $_POST['credentialsSignature'])) {
				$decrypted = wfUtils::decrypt($_POST['credentials']);
				$credentials = @json_decode($decrypted, true);
			}
		}

		$ajaxURL = admin_url('admin-ajax.php');
		if (!isset($credentials)) {
			$allow_relaxed_file_ownership = true;
			ob_start();
			if (false === ($credentials = request_filesystem_credentials($ajaxURL, '', false, ABSPATH, array('version', 'locale', 'action', 'serverConfiguration', 'iniModified'), $allow_relaxed_file_ownership))) {
				$credentialsContent = ob_get_clean();
				$html = wfView::create('waf/waf-modal-wrapper', array(
					'title' => __('Filesystem Credentials Required', 'wordfence'),
					'html' => $credentialsContent,
					'helpHTML' => wp_kses(sprintf(/* translators: Support URL. */ __('If you cannot complete the uninstall process, <a target="_blank" rel="noopener noreferrer" href="%s">click here for help<span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'), wfSupportController::esc_supportURL(wfSupportController::ITEM_FIREWALL_WAF_REMOVE_MANUALLY)), array('a' => array('href' => array(), 'target' => array(), 'rel' => array()), 'span' => array('class' => array()))),
					'footerHTML' => esc_html__('Once you have entered credentials, click Continue to complete uninstallation.', 'wordfence'),
				))->render();
				return array('needsCredentials' => 1, 'html' => $html);
			}
			ob_end_clean();
		}

		if (!WP_Filesystem($credentials, ABSPATH, $allow_relaxed_file_ownership) && $wp_filesystem->errors->get_error_code()) {
			$credentialsError = '';
			foreach ($wp_filesystem->errors->get_error_messages() as $message) {
				if (is_wp_error($message)) {
					if ($message->get_error_data() && is_string($message->get_error_data())) {
						$message = $message->get_error_message() . ': ' . $message->get_error_data();
					}
					else {
						$message = $message->get_error_message();
					}
				}
				$credentialsError .= "<p>$message</p>\n";
			}

			$html = wfView::create('waf/waf-modal-wrapper', array(
				'title' => __('Filesystem Permission Error', 'wordfence'),
				'html' => $credentialsError,
				'helpHTML' => wp_kses(sprintf(/* translators: Support URL. */ __('If you cannot complete the uninstall process, <a target="_blank" rel="noopener noreferrer" href="%s">click here for help<span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'), wfSupportController::esc_supportURL(wfSupportController::ITEM_FIREWALL_WAF_REMOVE_MANUALLY)), array('a' => array('href' => array(), 'target' => array(), 'rel' => array()), 'span' => array('class' => array()))),
				'footerButtonTitle' => __('Cancel', 'wordfence'),
			))->render();
			return array('credentialsFailed' => 1, 'html' => $html);
		}

		try {
			if ((!isset($_POST['iniModified']) || (isset($_POST['iniModified']) && !$_POST['iniModified'])) && !WF_IS_PRESSABLE) { //Uses .user.ini but not yet modified
				$hasPreviousAutoPrepend = $helper->performIniRemoval($wp_filesystem);

				$iniTTL = intval(ini_get('user_ini.cache_ttl'));
				if ($iniTTL == 0) {
					$iniTTL = 300; //The PHP default
				}
				if (!$helper->usesUserIni()) {
					$iniTTL = 0; //.htaccess
				}
				$timeout = max(30, $iniTTL);
				$timeoutString = wfUtils::makeDuration($timeout);

				$waitingResponse = '<p>' . __('The <code>auto_prepend_file</code> setting has been successfully removed from <code>.htaccess</code> and <code>.user.ini</code>. Once this change takes effect, Extended Protection Mode will be disabled.', 'wordfence') . '</p>';
				if ($hasPreviousAutoPrepend) {
					$waitingResponse .= '<p>' . __('Any previous value for <code>auto_prepend_file</code> will need to be re-enabled manually if still needed.', 'wordfence') . '</p>';
				}

				$spinner = wfView::create('common/indeterminate-progress', array('size' => 32))->render();
				$waitingResponse .= '<ul class="wf-flex-horizontal"><li>' . $spinner . '</li><li class="wf-padding-add-left">' . sprintf(/* translators: Time until. */ __('Waiting for it to take effect. This may take up to %s.', 'wordfence'), $timeoutString) . '</li></ul>';

				$html = wfView::create('waf/waf-modal-wrapper', array(
					'title' => __('Waiting for Changes', 'wordfence'),
					'html' => $waitingResponse,
					'helpHTML' => wp_kses(sprintf(/* translators: Support URL. */ __('If you cannot complete the uninstall process, <a target="_blank" rel="noopener noreferrer" href="%s">click here for help<span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'), wfSupportController::esc_supportURL(wfSupportController::ITEM_FIREWALL_WAF_REMOVE_MANUALLY)), array('a' => array('href' => array(), 'target' => array(), 'rel' => array()), 'span' => array('class' => array()))),
					'footerButtonTitle' => __('Close', 'wordfence'),
					'noX' => true,
				))->render();

				$response = array('uninstallationWaiting' => 1, 'html' => $html, 'timeout' => $timeout, 'serverConfiguration' => $_POST['serverConfiguration']);
				if (isset($credentials) && is_array($credentials)) {
					$salt = wp_salt('logged_in');
					$json = json_encode($credentials);
					$encrypted = wfUtils::encrypt($json);
					$signature = hash_hmac('sha256', $encrypted, $salt);
					$response['credentials'] = $encrypted;
					$response['credentialsSignature'] = $signature;
				}
				return $response;
			}
			else { //.user.ini and .htaccess modified if applicable and waiting period elapsed or otherwise ready to advance to next step
				if (WFWAF_AUTO_PREPEND && !WFWAF_SUBDIRECTORY_INSTALL && !WF_IS_WP_ENGINE && !WF_IS_PRESSABLE) { //.user.ini modified, but the WAF is still enabled
					$retryAttempted = (isset($_POST['retryAttempted']) && $_POST['retryAttempted']);
					$userIniError = '<p class="wf-error">';
					$userIniError .= __('Extended Protection Mode has not been disabled. This may be because <code>auto_prepend_file</code> is configured somewhere else or the value is still cached by PHP.', 'wordfence');
					if ($retryAttempted) {
						$userIniError .= ' <strong>' . __('Retrying Failed.', 'wordfence') . '</strong>';
					}
					$userIniError .= ' <a href="#" class="wf-waf-uninstall-try-again" role="button">' . __('Try Again', 'wordfence') . '</a>';
					$userIniError .= '</p>';
					$html = wfView::create('waf/waf-modal-wrapper', array(
						'title' => __('Unable to Uninstall', 'wordfence'),
						'html' => $userIniError,
						'helpHTML' => wp_kses(sprintf(/* translators: Support URL. */ __('If you cannot complete the uninstall process, <a target="_blank" rel="noopener noreferrer" href="%s">click here for help<span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'), wfSupportController::esc_supportURL(wfSupportController::ITEM_FIREWALL_WAF_REMOVE_MANUALLY)), array('a' => array('href' => array(), 'target' => array(), 'rel' => array()), 'span' => array('class' => array()))),
						'footerButtonTitle' => __('Cancel', 'wordfence'),
					))->render();

					$response = array('uninstallationFailed' => 1, 'html' => $html, 'serverConfiguration' => $_POST['serverConfiguration']);
					if (isset($credentials) && is_array($credentials)) {
						$salt = wp_salt('logged_in');
						$json = json_encode($credentials);
						$encrypted = wfUtils::encrypt($json);
						$signature = hash_hmac('sha256', $encrypted, $salt);
						$response['credentials'] = $encrypted;
						$response['credentialsSignature'] = $signature;
					}
					return $response;
				}

				$helper->performAutoPrependFileRemoval($wp_filesystem);

				$nonce = bin2hex(wfWAFUtils::random_bytes(32));
				wfConfig::set('wafStatusCallbackNonce', $nonce);
				$verifyURL = add_query_arg(array('action' => 'wordfence_wafStatus', 'nonce' => $nonce), $ajaxURL);
				$response = wp_remote_get($verifyURL, array('headers' => array('Referer' => false/*, 'Cookie' => 'XDEBUG_SESSION=1'*/)));

				$active = true;
				$subdirectory = WFWAF_SUBDIRECTORY_INSTALL;
				if (!is_wp_error($response)) {
					$wafStatus = @json_decode(wp_remote_retrieve_body($response), true);
					if (isset($wafStatus['active']) && isset($wafStatus['subdirectory'])) {
						$active = $wafStatus['active'] && !$wafStatus['subdirectory'];
						$subdirectory = $wafStatus['subdirectory'];
					}
				}

				$html = wfView::create('waf/waf-modal-wrapper', array(
					'title' => __('Uninstallation Complete', 'wordfence'),
					'html' => wfView::create('waf/waf-uninstall-success', array('active' => $active, 'subdirectory' => $subdirectory))->render(),
					'footerButtonTitle' => __('Close', 'wordfence'),
				))->render();
				return array('ok' => 1, 'html' => $html);
			}
		}
		catch (wfWAFAutoPrependHelperException $e) {
			$installError = "<p>" . $e->getMessage() . "</p>";
			$html = wfView::create('waf/waf-modal-wrapper', array(
				'title' => __('Uninstallation Failed', 'wordfence'),
				'html' => $installError,
				'helpHTML' => wp_kses(sprintf(/* translators: Support URL. */ __('If you cannot complete the uninstall process, <a target="_blank" rel="noopener noreferrer" href="%s">click here for help<span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'), wfSupportController::esc_supportURL(wfSupportController::ITEM_FIREWALL_WAF_REMOVE_MANUALLY)), array('a' => array('href' => array(), 'target' => array(), 'rel' => array()), 'span' => array('class' => array()))),
				'footerButtonTitle' => __('Cancel', 'wordfence'),
			))->render();
			return array('uninstallationFailed' => 1, 'html' => $html);
		}
	}

	public static function actionUserRegistration($user_id) {
		if (wfUtils::isAdmin($user_id) && ($request = self::getLog()->getCurrentRequest())) {
			//self::getLog()->canLogHit = true;
			$request->action = 'user:adminCreate';
			$request->save();
		}
	}

	public static function actionPasswordReset($user = null, $new_pass = null) {
		if ($request = self::getLog()->getCurrentRequest()) {
			//self::getLog()->canLogHit = true;
			$request->action = 'user:passwordReset';
			$request->save();
		}
	}

	public static function trimWfHits($force = false) {
		if(!$force && self::isApiDelayed())
			return;
		$wfdb = new wfDB();
		$lastAggregation = wfConfig::get('lastBlockAggregation', 0);
		$table_wfHits = wfDB::networkTable('wfHits');
		$count = $wfdb->querySingle("select count(*) as cnt from {$table_wfHits}");
		$liveTrafficMaxRows = absint(wfConfig::get('liveTraf_maxRows', 2000));
		if ($count > $liveTrafficMaxRows * 10) {
			self::_aggregateBlockStats($lastAggregation);
			$wfdb->truncate($table_wfHits); //So we don't slow down sites that have very large wfHits tables
		}
		else if ($count > $liveTrafficMaxRows) {
			self::_aggregateBlockStats($lastAggregation);
			$wfdb->queryWrite("delete from {$table_wfHits} order by id asc limit %d", ($count - $liveTrafficMaxRows) + ($liveTrafficMaxRows * .2));
		}
		else if ($lastAggregation < (time() - 86400)) {
			self::_aggregateBlockStats($lastAggregation);
		}

		$maxAge = wfConfig::get('liveTraf_maxAge', 30);
		if ($maxAge <= 0 || $maxAge > 30) {
			$maxAge = 30;
		}
		$wfdb->queryWrite("DELETE FROM {$table_wfHits} WHERE ctime < %d", time() - ($maxAge * 86400));
	}

	private static function _aggregateBlockStats($since = false) {
		global $wpdb;

		if (!wfConfig::get('other_WFNet', true)) {
			return;
		}

		if ($since === false) {
			$since = wfConfig::get('lastBlockAggregation', 0);
		}

		$hitsTable = wfDB::networkTable('wfHits');
		$query = $wpdb->prepare("SELECT COUNT(*) AS cnt, CASE WHEN (jsRun = 1 OR userID > 0) THEN 1 ELSE 0 END AS isHuman, statusCode FROM {$hitsTable} WHERE ctime > %d GROUP BY isHuman, statusCode", $since);
		$rows = $wpdb->get_results($query, ARRAY_A);
		if (count($rows)) {
			try {
				$api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
				$api->call('aggregate_stats', array(), array('stats' => json_encode($rows)));
			}
			catch (Exception $e) {
				// Do nothing
			}
		}

		wfConfig::set('lastBlockAggregation', time());
	}

	private static function isApiDelayed() {
		return wfConfig::get('apiDelayedUntil', 0) > time();
	}

	private static function delaySendAttackData($until) {
		wfConfig::set('apiDelayedUntil', $until);
		self::scheduleSendAttackData($until);
	}

	private static function scheduleSendAttackData($timeToSend = null) {
		if ($timeToSend === null) {
			$timeToSend = time() + (60 * 5);
		}
		$notMainSite = is_multisite() && !is_main_site();
		if ($notMainSite) {
			global $current_site;
			switch_to_blog($current_site->blog_id);
		}
		if (!wp_next_scheduled('wordfence_processAttackData')) {
			wp_schedule_single_event($timeToSend, 'wordfence_processAttackData');
		}
		if ($notMainSite) {
			restore_current_blog();
		}
	}

	private static function truncateWafFailures() {
		wfDB::shared()->truncate(wfDB::networkTable('wfWafFailures'));
	}

	private static function loadWafFailures(&$purgeCallable = null) {
		global $wpdb;
		$table = wfDB::networkTable('wfWafFailures');
		$query = <<<SQL
			SELECT
				id,
				failures.rule_id,
				throwable AS latest_throwable,
				UNIX_TIMESTAMP(latest_occurrence) AS latest_occurrence,
				occurrences
			FROM
				{$table} failures
				JOIN (
					SELECT
						rule_id,
						MAX(id) AS max_id,
						MAX(timestamp) AS latest_occurrence,
						COUNT(*) AS occurrences
					FROM
						{$table}
					GROUP BY
						rule_id
				) aggregate ON failures.id = aggregate.max_id
SQL;
		$results = $wpdb->get_results($query);
		$maxId = null;
		foreach ($results as $row) {
			if ($maxId === null) {
				$maxId = $row->id;
			}
			else {
				$maxId = max($maxId, $row->id);
			}
		}
		if ($maxId === null) {
			$purgeCallable = function() { /* Nothing to delete */ };
		}
		else {
			$purgeCallable = function() use ($table, $maxId, $wpdb) {
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$table} WHERE id <= %d",
						$maxId
					)
				);
			};
		}
		return $results;
	}

	/**
	 *
	 */
	public static function processAttackData() {
		global $wpdb;
		$table_wfHits = wfDB::networkTable('wfHits');
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }

		$waf = wfWAF::getInstance();
		if ($waf->getStorageEngine()->getConfig('attackDataKey', false) === false) {
			$waf->getStorageEngine()->setConfig('attackDataKey', mt_rand(0, 0xfff));
		}

		//Send alert email if needed
		if (wfConfig::get('wafAlertOnAttacks')) {
			$alertInterval = wfConfig::get('wafAlertInterval', 0);
			$cutoffTime = max(time() - $alertInterval, wfConfig::get('wafAlertLastSendTime'));
			$wafAlertWhitelist = wfConfig::get('wafAlertWhitelist', '');
			$wafAlertWhitelist = preg_split("/[,\r\n]+/", $wafAlertWhitelist);
			foreach ($wafAlertWhitelist as $index => &$entry) {
				$entry = trim($entry);
				if (empty($entry) || (!preg_match('/^(?:\d{1,3}(?:\.|$)){4}/', $entry) && !preg_match('/^((?:[\da-f]{1,4}(?::|)){0,8})(::)?((?:[\da-f]{1,4}(?::|)){0,8})$/i', $entry))) {
					unset($wafAlertWhitelist[$index]);
					continue;
				}

				$packed = @wfUtils::inet_pton($entry);
				if ($packed === false) {
					unset($wafAlertWhitelist[$index]);
					continue;
				}
				$entry = bin2hex($packed);
			}
			$wafAlertWhitelist = array_filter($wafAlertWhitelist);
			$attackDataQuery = $wpdb->prepare(
				"SELECT * FROM {$table_wfHits}
				WHERE action = 'blocked:waf' " .
				(count($wafAlertWhitelist) ? "AND HEX(IP) NOT IN (" . implode(", ", array_fill(0, count($wafAlertWhitelist), '%s')) . ")" : "")
				. " AND attackLogTime > %f
				ORDER BY attackLogTime DESC
				LIMIT 10",
				array_merge($wafAlertWhitelist, array(sprintf('%.6f', $cutoffTime))));
			$attackDataCountQuery = str_replace(
				array(
					"SELECT * FROM",
					"ORDER BY attackLogTime DESC",
					"LIMIT 10",
				),
				array( "SELECT COUNT(*) FROM", "", "" ), $attackDataQuery
			);
			$attackData = $wpdb->get_results($attackDataQuery);
			$attackCount = $wpdb->get_var($attackDataCountQuery);
			unset( $attackDataQuery, $attackDataCountQuery );
			$threshold = (int) wfConfig::get('wafAlertThreshold');
			if ($threshold < 1) {
				$threshold = 100;
			}
			if ($attackCount >= $threshold) {
				$durationMessage = wfUtils::makeDuration($alertInterval);
				$message = sprintf(
				/* translators: 1. Number of attacks/blocks. 2. Time since. */
					__('The Wordfence Web Application Firewall has blocked %1$d attacks over the last %2$s.', 'wordfence'),
					$attackCount,
					$durationMessage
				);
				$message .= "\n\n";
				$message .= __('Wordfence is blocking these attacks, and we\'re sending this notice to make you aware that there is a higher volume of the attacks than usual. Additionally, the Wordfence Real-Time IP Blocklist can block known attackers\' IP addresses automatically for Premium users, including any probing requests that may not be malicious on their own. All Wordfence users can also opt to block the attacking IPs manually if desired. As always, be sure to watch your scan results and keep your plugins, themes and WordPress core version updated.', 'wordfence');
				$message .= "\n\n";
				$message .= __('Below is a sample of these recent attacks:', 'wordfence');
				$attackTable = array();
				$dateMax = $ipMax = $countryMax = 0;
				foreach ($attackData as $row) {
					$actionData = json_decode($row->actionData, true);
					if (!is_array($actionData) || !isset($actionData['paramKey']) || !isset($actionData['paramValue'])) {
						continue;
					}

					if (isset($actionData['failedRules']) && $actionData['failedRules'] == 'blocked') {
						$row->longDescription = __("Blocked because the IP is blocklisted", 'wordfence');
					}
					else {
						$row->longDescription = sprintf(/* translators: Reason for firewall action. */ __("Blocked for %s", 'wordfence'), $row->actionDescription);
					}

					$paramKey = base64_decode($actionData['paramKey']);
					$paramValue = base64_decode($actionData['paramValue']);
					if (strlen($paramValue) > 100) {
						$paramValue = substr($paramValue, 0, 100) . '...';
					}

					if (preg_match('/([a-z0-9_]+\.[a-z0-9_]+)(?:\[(.+?)\](.*))?/i', $paramKey, $matches)) {
						switch ($matches[1]) {
							case 'request.queryString':
								$row->longDescription = sprintf(
								/* translators: 1. Reason for firewall action. 2. Input parameter. 2. Input parameter value. */
									__('Blocked for %1$s in query string: %2$s = %3$s', 'wordfence'), $row->actionDescription, $matches[2], $paramValue);
								break;
							case 'request.body':
								$row->longDescription = sprintf(
								/* translators: 1. Reason for firewall action. 2. Input parameter. 2. Input parameter value. */
									__('Blocked for %1$s in POST body: %2$s = %3$s', 'wordfence'), $row->actionDescription, $matches[2], $paramValue);
								break;
							case 'request.cookie':
								$row->longDescription = sprintf(
								/* translators: 1. Reason for firewall action. 2. Input parameter. 2. Input parameter value. */
									__('Blocked for %1$s in cookie: %2$s = %3$s', 'wordfence'), $row->actionDescription, $matches[2], $paramValue);
								break;
							case 'request.fileNames':
								$row->longDescription = sprintf(
								/* translators: 1. Reason for firewall action. 2. Input parameter. 2. Input parameter value. */
									__('Blocked for %1$s in file: %2$s = %3$s', 'wordfence'), $row->actionDescription, $matches[2], $paramValue);
								break;
						}
					}

					$date = date_i18n('F j, Y g:ia', floor($row->attackLogTime)); $dateMax = max(strlen($date), $dateMax);
					$ip = wfUtils::inet_ntop($row->IP); $ipMax = max(strlen($ip), $ipMax);
					$country = wfUtils::countryCode2Name(wfUtils::IP2Country($ip)); $country = (empty($country) ? 'Unknown' : $country); $countryMax = max(strlen($country), $countryMax);
					$attackTable[] = array('date' => $date, 'IP' => $ip, 'country' => $country, 'message' => $row->longDescription);
				}

				foreach ($attackTable as $row) {
					$date = str_pad($row['date'], $dateMax + 2);
					$ip = str_pad($row['IP'] . " ({$row['country']})", $ipMax + $countryMax + 8);
					$attackMessage = $row['message'];
					$message .= "\n" . $date . $ip . $attackMessage;
				}

				$alertCallback = array(new wfIncreasedAttackRateAlert($message), 'send');
				do_action('wordfence_security_event', 'increasedAttackRate', array(
					'attackCount' => $attackCount,
					'attackTable' => $attackTable,
					'duration' => $alertInterval,
					'ip' => wfUtils::getIP(),
				), $alertCallback);

				wfConfig::set('wafAlertLastSendTime', time());
			}
		}

		if (wfConfig::get('other_WFNet', true)) {
			$response = wp_remote_get(sprintf(WFWAF_API_URL_SEC . "waf-rules/%d.txt", $waf->getStorageEngine()->getConfig('attackDataKey')), array('headers' => array('Referer' => false)));
			if (!is_wp_error($response)) {
				$okToSendBody = wp_remote_retrieve_body($response);
				if ($okToSendBody === 'ok') {
					//Send attack data
					$limit = 500;
					$lastSendTime = wfConfig::get('lastAttackDataSendTime');
					$lastSendId = wfConfig::get('lastAttackDataSendId');
					if($lastSendId===false){
						$query=$wpdb->prepare("SELECT * FROM {$table_wfHits}
						WHERE action in ('blocked:waf', 'learned:waf', 'logged:waf', 'blocked:waf-always')
						AND attackLogTime > %f
						LIMIT %d", sprintf('%.6f', $lastSendTime), $limit);

						$count_query = str_replace(
							array(
								"SELECT * FROM",
								"LIMIT " . $limit,
							),
							array( "SELECT COUNT(*) FROM", "" ), $query
						);
					}
					else{
						$query=$wpdb->prepare("SELECT * FROM {$table_wfHits}
						WHERE action in ('blocked:waf', 'learned:waf', 'logged:waf', 'blocked:waf-always')
						AND id > %d
						ORDER BY id LIMIT %d", $lastSendId, $limit);

						$count_query = str_replace(
							array(
								"SELECT * FROM",
								"ORDER BY id LIMIT " . $limit,
							),
							array( "SELECT COUNT(*) FROM", "" ), $query
						);
					}

					$params[]=$limit;
					$attackData = $wpdb->get_results($query);
					$totalRows = $wpdb->get_var($count_query);

					if ($attackData) { // Build JSON to send
						$dataToSend = array();
						$attackDataToUpdate = array();
						foreach ($attackData as $attackDataRow) {
							$actionData = (array) wfRequestModel::unserializeActionData($attackDataRow->actionData);
							$dataToSend[] = array(
								$attackDataRow->attackLogTime,
								$attackDataRow->ctime,
								wfUtils::inet_ntop($attackDataRow->IP),
								(array_key_exists('learningMode', $actionData) ? $actionData['learningMode'] : 0),
								(array_key_exists('paramKey', $actionData) ? base64_encode($actionData['paramKey']) : false),
								(array_key_exists('paramValue', $actionData) ? base64_encode($actionData['paramValue']) : false),
								(array_key_exists('failedRules', $actionData) ? $actionData['failedRules'] : ''),
								strpos($attackDataRow->URL, 'https') === 0 ? 1 : 0,
								(array_key_exists('fullRequest', $actionData) ? $actionData['fullRequest'] : ''),
							);
							if (array_key_exists('fullRequest', $actionData)) {
								unset($actionData['fullRequest']);
								$attackDataToUpdate[$attackDataRow->id] = array(
									'actionData' => wfRequestModel::serializeActionData($actionData),
								);
							}
							if ($attackDataRow->attackLogTime > $lastSendTime) {
								$lastSendTime = $attackDataRow->attackLogTime;
							}
						}

						$bodyLimit=self::ATTACK_DATA_BODY_LIMIT;
						$response=null;
						do {
							$bodyData=null;
							do {
								if($bodyData!==null)
									array_splice($dataToSend, floor(count($dataToSend)/2));
								$bodyData=json_encode($dataToSend);
							} while(strlen($bodyData)>$bodyLimit&&count($dataToSend)>1);

							$homeurl = wfUtils::wpHomeURL();
							$siteurl = wfUtils::wpSiteURL();
							$installType = wfUtils::wafInstallationType();
							$response = wp_remote_post(WFWAF_API_URL_SEC . "?" . http_build_query(array(
									'action' => 'send_waf_attack_data',
									'k'      => $waf->getStorageEngine()->getConfig('apiKey', null, 'synced'),
									's'      => $siteurl,
									'h'		 => $homeurl,
									't'		 => microtime(true),
									'c'		 => $installType,
									'lang'   => get_site_option('WPLANG'),
								), '', '&'),
								array(
									'body'    => $bodyData,
									'headers' => array(
										'Content-Type' => 'application/json',
										'Referer' => false,
									),
									'timeout' => 30,
								));
							$bodyLimit/=2;
						} while(wp_remote_retrieve_response_code($response)===413&&count($dataToSend)>1);

						if (!is_wp_error($response) && ($body = wp_remote_retrieve_body($response))) {
							$jsonData = json_decode($body, true);
							if (is_array($jsonData) && array_key_exists('success', $jsonData)) {
								wfConfig::set('lastAttackDataSendTime', $lastSendTime);
								$lastSendIndex=count($dataToSend)-1;
								if($lastSendIndex>=0){
									$lastSendId = $attackData[$lastSendIndex]->id;
									wfConfig::set('lastAttackDataSendId', $lastSendId);
									// Successfully sent data, remove the full request from the table to reduce storage size
									foreach ($attackDataToUpdate as $hitID => $dataToUpdate) {
										if ($hitID <= $lastSendId) {
											$wpdb->update($table_wfHits, $dataToUpdate, array(
												'id' => $hitID,
											));
										}
									}
								}
								if (count($dataToSend) < $totalRows) {
									self::scheduleSendAttackData();
								}

								if (array_key_exists('data', $jsonData) && array_key_exists('watchedIPList', $jsonData['data'])) {
									$waf->getStorageEngine()->setConfig('watchedIPs', $jsonData['data']['watchedIPList'], 'transient');
								}
							}
						}
						else{
							//Delay interactions for 30 minutes if an error occurs
							self::delaySendAttackData(time() + 30*60);
						}
					}

					//Send false positives and WAF failures
					$lastSendTime = wfConfig::get('lastFalsePositiveSendTime');
					$whitelistedURLParams = (array) wfWAF::getInstance()->getStorageEngine()->getConfig('whitelistedURLParams', array(), 'livewaf');
					$wafFailures = self::loadWafFailures($purgeWafFailures);
					if (count($whitelistedURLParams) || !empty($wafFailures)) {
						$falsePositives = array();
						$mostRecentWhitelisting = $lastSendTime;
						foreach ($whitelistedURLParams as $urlParamKey => $rules) {
							list($path, $paramKey) = explode('|', $urlParamKey);
							$ruleData = array();
							foreach ($rules as $ruleID => $whitelistedData) {
								if ($whitelistedData['timestamp'] > $lastSendTime && (!isset($whitelistedData['disabled']) || !$whitelistedData['disabled'])) {
									if (isset($whitelistedData['source'])) {
										$source = $whitelistedData['source'];
									}
									else if ($whitelistedData['description'] == 'Allowlisted via false positive dialog') {
										$source = 'false-positive';
									}
									else if ($whitelistedData['description'] == 'Allowlisted via Live Traffic') {
										$source = 'live-traffic';
									}
									else if ($whitelistedData['description'] == 'Allowlisted while in Learning Mode.') {
										$source = 'learning-mode';
									}
									else { //A user-entered description or Whitelisted via Firewall Options page
										$source = 'waf-options';
									}

									$ruleData[] = array(
										$ruleID,
										$whitelistedData['timestamp'],
										$source,
										$whitelistedData['description'],
										$whitelistedData['ip'],
										isset($whitelistedData['userID']) ? $whitelistedData['userID'] : 0,
									);

									if ($whitelistedData['timestamp'] > $mostRecentWhitelisting) {
										$mostRecentWhitelisting = $whitelistedData['timestamp'];
									}
								}
							}

							if (count($ruleData)) {
								$falsePositives[] = array(
									base64_decode($path),
									base64_decode($paramKey),
									$ruleData,
								);
							}
						}

						$data = [];
						if (!empty($wafFailures))
							$data['waf_failures'] = $wafFailures;
						if (!empty($falsePositives))
							$data['false_positives'] = $falsePositives;

						if (count($data)) {
							$homeurl = wfUtils::wpHomeURL();
							$siteurl = wfUtils::wpSiteURL();
							$installType = wfUtils::wafInstallationType();
							$response = wp_remote_post(WFWAF_API_URL_SEC . "?" . http_build_query(array(
									'action' => 'send_waf_false_positives',
									'k'      => $waf->getStorageEngine()->getConfig('apiKey', null, 'synced'),
									's'      => $siteurl,
									'h'		 => $homeurl,
									't'		 => microtime(true),
									'c'		 => $installType,
									'lang'   => get_site_option('WPLANG'),
								), '', '&'),
								array(
									'body'    => json_encode($data),
									'headers' => array(
										'Content-Type' => 'application/json',
										'Referer' => false,
									),
									'timeout' => 30,
								));

							if (!is_wp_error($response) && ($body = wp_remote_retrieve_body($response))) {
								$jsonData = json_decode($body, true);
								if (is_array($jsonData) && array_key_exists('success', $jsonData)) {
									$purgeWafFailures();
									wfConfig::set('lastFalsePositiveSendTime', $mostRecentWhitelisting);
								}
							}
						}
					}
				}
				else if (is_string($okToSendBody) && preg_match('/next check in: ([0-9]+)/', $okToSendBody, $matches)) {
					self::delaySendAttackData(time() + $matches[1]);
				}
			}
			else { // Could be that the server is down, so hold off on sending data for a little while
				self::delaySendAttackData(time() + 7200);
			}
		}
		else if (!wfConfig::get('other_WFNet', true)) {
			wfConfig::set('lastAttackDataSendTime', time());
			wfConfig::set('lastFalsePositiveSendTime', time());
			self::truncateWafFailures();
		}

		self::trimWfHits();
	}

	public static function syncAttackData($exit = true) {
		global $wpdb;
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		$log = self::getLog();
		$waf = wfWAF::getInstance();
		$table_wfHits = wfDB::networkTable('wfHits');
		if ($waf->getStorageEngine() instanceof wfWAFStorageMySQL) {
			$lastAttackMicroseconds = floatval($waf->getStorageEngine()->getConfig('lastAttackDataTruncateTime'));
		} else {
			$lastAttackMicroseconds = $wpdb->get_var("SELECT MAX(attackLogTime) FROM {$table_wfHits}");
		}

		if ($waf->getStorageEngine()->hasNewerAttackData($lastAttackMicroseconds)) {
			$attackData = $waf->getStorageEngine()->getNewestAttackDataArray($lastAttackMicroseconds);
			if ($attackData) {
				foreach ($attackData as $request) {
					if (count($request) !== 9 && count($request) !== 10 /* with metadata */ && count($request) !== 11) {
						continue;
					}

					list($logTimeMicroseconds, $requestTime, $ip, $learningMode, $paramKey, $paramValue, $failedRules, $ssl, $requestString) = $request;
					$metadata = null;
					$recordID = null;
					if (array_key_exists(9, $request)) {
						$metadata = $request[9];
					}
					if (array_key_exists(10, $request)) {
						$recordID = $request[10];
					}

					// Skip old entries and hits in learning mode, since they'll get picked up anyways.
					if ($logTimeMicroseconds <= $lastAttackMicroseconds || $learningMode) {
						continue;
					}

					$statusCode = 403;

					$hit = new wfRequestModel();
					if (is_numeric($recordID)) {
						$hit->id = $recordID;
					}

					$hit->attackLogTime = $logTimeMicroseconds;
					$hit->ctime = $requestTime;
					$hit->IP = wfUtils::inet_pton($ip);

					if (preg_match('/user\-agent:(.*?)\n/i', $requestString, $matches)) {
						$hit->UA = trim($matches[1]);
						$hit->isGoogle = wfCrawl::isGoogleCrawler($hit->UA);
					}

					if (preg_match('/Referer:(.*?)\n/i', $requestString, $matches)) {
						$hit->referer = trim($matches[1]);
					}

					if (preg_match('/^[a-z]+\s+(.*?)\s+/i', $requestString, $uriMatches) && preg_match('/Host:(.*?)\n/i', $requestString, $hostMatches)) {
						$hit->URL = 'http' . ($ssl ? 's' : '') . '://' . trim($hostMatches[1]) . trim($uriMatches[1]);
					}

					$hit->jsRun = (int) wfLog::isHumanRequest($ip, $hit->UA);
					$isHuman = !!$hit->jsRun;

					if (preg_match('/cookie:(.*?)\n/i', $requestString, $matches)) {
						$authCookieName = $waf->getAuthCookieName();
						$hasLoginCookie = strpos($matches[1], $authCookieName) !== false;
						if ($hasLoginCookie && preg_match('/' . preg_quote($authCookieName) . '=(.*?);/', $matches[1], $cookieMatches)) {
							$authCookie = rawurldecode($cookieMatches[1]);
							$decodedAuthCookie = $waf->parseAuthCookie($authCookie);
							if ($decodedAuthCookie !== false) {
								$hit->userID = $decodedAuthCookie['userID'];
								$isHuman = true;
							}
						}
					}

					$path = '/';
					if (preg_match('/^[A-Z]+ (.*?) HTTP\\/1\\.1/', $requestString, $matches)) {
						if (($pos = strpos($matches[1], '?')) !== false) {
							$path = substr($matches[1], 0, $pos);
						} else {
							$path = $matches[1];
						}
					}

					$metadata = ($metadata != null ? (array) $metadata : array());
					if (isset($metadata['finalAction']) && $metadata['finalAction']) { // The request was blocked/redirected because of its IP based on the plugin's blocking settings. WAF blocks should be reported but not shown in live traffic with that as a reason.
						$action = $metadata['finalAction']['action'];
						$actionDescription = $action;
						if (class_exists('wfWAFIPBlocksController')) {
							if ($action == wfWAFIPBlocksController::WFWAF_BLOCK_UAREFIPRANGE) {
								wfActivityReport::logBlockedIP($ip, null, 'advanced');
							}
							else if ($action == wfWAFIPBlocksController::WFWAF_BLOCK_COUNTRY_BYPASS_REDIR) {
								/* Handled below */
							}
							else if ($action == wfWAFIPBlocksController::WFWAF_BLOCK_COUNTRY_REDIR) {
								$actionDescription .= ' (' . wfConfig::get('cbl_redirURL') . ')';
								wfConfig::inc('totalCountryBlocked');
								wfActivityReport::logBlockedIP($ip, null, 'country');
							}
							else if ($action == wfWAFIPBlocksController::WFWAF_BLOCK_COUNTRY) {
								wfConfig::inc('totalCountryBlocked');
								wfActivityReport::logBlockedIP($ip, null, 'country');
							}
							else if ($action == wfWAFIPBlocksController::WFWAF_BLOCK_WFSN) {
								wordfence::wfsnReportBlockedAttempt($ip, 'login');
								wfActivityReport::logBlockedIP($ip, null, 'brute');
							}
							else if (defined('wfWAFIPBlocksController::WFWAF_BLOCK_BADPOST') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_BADPOST) {
								wfActivityReport::logBlockedIP($ip, null, 'badpost');
							}
							else if (defined('wfWAFIPBlocksController::WFWAF_BLOCK_BANNEDURL') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_BANNEDURL) {
								wfActivityReport::logBlockedIP($ip, null, 'bannedurl');
							}
							else if (defined('wfWAFIPBlocksController::WFWAF_BLOCK_FAKEGOOGLE') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_FAKEGOOGLE) {
								wfActivityReport::logBlockedIP($ip, null, 'fakegoogle');
							}
							else if ((defined('wfWAFIPBlocksController::WFWAF_BLOCK_LOGINSEC') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_LOGINSEC) ||
							         (defined('wfWAFIPBlocksController::WFWAF_BLOCK_LOGINSEC_FORGOTPASSWD') && strpos($action, wfWAFIPBlocksController::WFWAF_BLOCK_LOGINSEC_FORGOTPASSWD) === 0) ||
							         (defined('wfWAFIPBlocksController::WFWAF_BLOCK_LOGINSEC_FAILURES') && strpos($action, wfWAFIPBlocksController::WFWAF_BLOCK_LOGINSEC_FAILURES) === 0)) {
								wfActivityReport::logBlockedIP($ip, null, 'brute');
							}
							else if ((defined('wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLEGLOBAL') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLEGLOBAL) ||
							         (defined('wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLESCAN') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLESCAN) ||
							         (defined('wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLECRAWLER') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLECRAWLER) ||
							         (defined('wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLECRAWLERNOTFOUND') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLECRAWLERNOTFOUND) ||
							         (defined('wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLEHUMAN') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLEHUMAN) ||
							         (defined('wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLEHUMANNOTFOUND') && $action == wfWAFIPBlocksController::WFWAF_BLOCK_THROTTLEHUMANNOTFOUND)
							) {
								wfConfig::inc('totalIPsThrottled');
								wfActivityReport::logBlockedIP($ip, null, 'throttle');
							}
							else { //Manual block
								wfActivityReport::logBlockedIP($ip, null, 'manual');
							}

							if (isset($metadata['finalAction']['id']) && $action != wfWAFIPBlocksController::WFWAF_BLOCK_COUNTRY_BYPASS_REDIR) {
								$id = $metadata['finalAction']['id'];
								$block = new wfBlock($id);
								$block->recordBlock(1, (int) $requestTime);
							}
						}

						if (strlen($actionDescription) == 0) {
							$actionDescription = 'Blocked by Wordfence';
						}

						if (empty($failedRules)) { // Just a plugin block
							$statusCode = 503;
							$hit->action = 'blocked:wordfence';
							if (class_exists('wfWAFIPBlocksController')) {
								if ($action == wfWAFIPBlocksController::WFWAF_BLOCK_COUNTRY_BYPASS_REDIR) {
									$statusCode = 302;
									$hit->action = 'cbl:redirect';
								}
								else if ($action == wfWAFIPBlocksController::WFWAF_BLOCK_WFSN) {
									$hit->action = 'blocked:wfsnrepeat';
									wordfence::wfsnReportBlockedAttempt($ip, 'waf');
								}
								else if (isset($metadata['finalAction']['lockout'])) {
									$hit->action = 'lockedOut';
								}
								else if (isset($metadata['finalAction']['block'])) {
									//Do nothing
								}
							}
							$hit->actionDescription = $actionDescription;
						}
						else if (preg_match('/\blogged\b/i', $failedRules)) {
							$statusCode = 200;
							$hit->action = 'logged:waf';
						}
						else { // Blocked by the WAF but would've been blocked anyway by the plugin settings so that message takes priority
							$hit->action = 'blocked:waf-always';
							$hit->actionDescription = $actionDescription;
						}
					}
					else {
						if (preg_match('/\blogged\b/i', $failedRules)) {
							$statusCode = 200;
							$hit->action = 'logged:waf';
						}
						else {
							$hit->action = 'blocked:waf';

							$type = null;
							if ($failedRules == 'blocked') {
								$type = 'blacklist';
							}
							else if (is_numeric($failedRules)) {
								$type = 'waf';
							}
							wfActivityReport::logBlockedIP($hit->IP, null, $type);
						}
					}

					/** @var wfWAFRule $rule */
					$ruleIDs = explode('|', $failedRules);
					$actionData = array(
						'learningMode' => $learningMode,
						'failedRules'  => $failedRules,
						'paramKey'     => $paramKey,
						'paramValue'   => $paramValue,
						'path'         => $path,
					);
					if ($ruleIDs && $ruleIDs[0]) {
						$rule = $waf->getRule($ruleIDs[0]);
						if ($rule) {
							if ($hit->action == 'logged:waf' || $hit->action == 'blocked:waf') { $hit->actionDescription = $rule->getDescription(); }
							$actionData['category'] = $rule->getCategory();
							$actionData['ssl'] = $ssl;
							$actionData['fullRequest'] = base64_encode($requestString);
						}
						else if ($ruleIDs[0] == 'logged' && isset($ruleIDs[1]) && ($rule = $waf->getRule($ruleIDs[1]))) {
							if ($hit->action == 'logged:waf' || $hit->action == 'blocked:waf') { $hit->actionDescription = $rule->getDescription(); }
							$actionData['category'] = $rule->getCategory();
							$actionData['ssl'] = $ssl;
							$actionData['fullRequest'] = base64_encode($requestString);
						}
						else if ($ruleIDs[0] == 'logged') {
							if ($hit->action == 'logged:waf' || $hit->action == 'blocked:waf') { $hit->actionDescription = 'Watched IP Traffic: ' . $ip; }
							$actionData['category'] = 'logged';
							$actionData['ssl'] = $ssl;
							$actionData['fullRequest'] = base64_encode($requestString);
						}
						else if ($ruleIDs[0] == 'blocked') {
							$actionData['category'] = 'blocked';
							$actionData['ssl'] = $ssl;
							$actionData['fullRequest'] = base64_encode($requestString);
						}
					}

					$hit->actionData = wfRequestModel::serializeActionData($actionData, array('fullRequest', 'ssl', 'category', 'learningMode', 'paramValue'));
					$hit->statusCode = $statusCode;
					$hit->save();

					self::scheduleSendAttackData();
				}
			}
			$waf->getStorageEngine()->truncateAttackData();
		}
		update_site_option('wordfence_syncingAttackData', 0);
		update_site_option('wordfence_syncAttackDataAttempts', 0);
		update_site_option('wordfence_lastSyncAttackData', time());
		if ($exit) {
			exit;
		}
	}

	public static function addSyncAttackDataAjax() {
		$URL = home_url('/?wordfence_syncAttackData=' . microtime(true));
		$URL = esc_url(preg_replace('/^https?:/i', '', $URL));
		// Load as external script async so we don't slow page down.
		echo "<script type=\"text/javascript\" src=\"$URL\" async></script>";
	}

	/**
	 * This is the only hook I see to tie into WP's core update process.
	 * Since we hide the readme.html to prevent the WordPress version from being discovered, it breaks the upgrade
	 * process because it cannot copy the previous readme.html.
	 *
	 * @param string $string
	 * @return string
	 */
	public static function restoreReadmeForUpgrade($string) {
		static $didRun;
		if (!isset($didRun)) {
			$didRun = true;
			wfUtils::showReadme();
			register_shutdown_function('wfUtils::hideReadme');
		}

		return $string;
	}


	public static function showCentralConfigurationIssueNotice() {
		?>
		<div class="fade error">
			<p><?php echo wp_kses(sprintf(__('An error was detected with this site\'s configuration that is preventing a successful connection to Wordfence Central. Disconnecting from Central <a href="%s">on the Wordfence Dashboard</a> and reconnecting may resolve it. If the issue persists, please contact Wordfence support.', 'wordfence'), network_admin_url('admin.php?page=Wordfence#wf-central-status')), array('a' => array('href' => array()))) ?></p>
		</div>
		<?php
	}

	public static function wafAutoPrependNotice() {
		$url = network_admin_url('admin.php?page=WordfenceWAF&subpage=waf_options#configureAutoPrepend');
		echo '<div class="update-nag" id="wf-extended-protection-notice">' . __('To make your site as secure as possible, take a moment to optimize the Wordfence Web Application Firewall:', 'wordfence') . ' &nbsp;<a class="wf-btn wf-btn-default wf-btn-sm" href="' . esc_url($url) . '">' . __('Click here to configure', 'wordfence') . '</a>
		<a class="wf-btn wf-btn-default wf-btn-sm wf-dismiss-link" href="#"  onclick="wordfenceExt.setOption(\'dismissAutoPrependNotice\', 1); jQuery(\'#wf-extended-protection-notice\').fadeOut(); return false;" role="button">' . __('Dismiss', 'wordfence') . '</a>
		<br>
		<em style="font-size: 85%;">' . wp_kses(sprintf(/* translators: Support URL. */ __('If you cannot complete the setup process, <a target="_blank" rel="noopener noreferrer" href="%s">click here for help<span class="screen-reader-text"> (opens in new tab)</span></a>.', 'wordfence'), wfSupportController::esc_supportURL(wfSupportController::ITEM_FIREWALL_WAF_INSTALL_MANUALLY)), array('a' => array('href' => array(), 'target' => array(), 'rel' => array()), 'span' => array('class' => array()))) . '</em>
		</div>';
	}

	public static function wafAutoPrependVerify() {
		if (WFWAF_AUTO_PREPEND && !WFWAF_SUBDIRECTORY_INSTALL) {
			echo '<div class="updated is-dismissible"><p>' . __('Nice work! The firewall is now optimized.', 'wordfence') . '</p></div>';
		} else {
			echo '<div class="notice notice-error"><p>' . __('The changes have not yet taken effect. If you are using LiteSpeed or IIS as your web server or CGI/FastCGI interface, you may need to wait a few minutes for the changes to take effect since the configuration files are sometimes cached. You also may need to select a different server configuration in order to complete this step, but wait for a few minutes before trying. You can try refreshing this page.', 'wordfence') . '</p></div>';
		}
	}

	public static function wafAutoPrependRemoved() {
		if (!WFWAF_AUTO_PREPEND) {
			echo '<div class="updated is-dismissible"><p>' . __('Uninstallation was successful!', 'wordfence') . '</p></div>';
		}
		else if (WFWAF_SUBDIRECTORY_INSTALL) {
			echo '<div class="notice notice-warning"><p>' . __('Uninstallation from this site was successful! The Wordfence Firewall is still active because it is installed in another WordPress installation.', 'wordfence') . '</p></div>';
		}
		else {
			echo '<div class="notice notice-error"><p>' . __('The changes have not yet taken effect. If you are using LiteSpeed or IIS as your web server or CGI/FastCGI interface, you may need to wait a few minutes for the changes to take effect since the configuration files are sometimes cached. You also may need to select a different server configuration in order to complete this step, but wait for a few minutes before trying. You can try refreshing this page.', 'wordfence') . '</p></div>';
		}
	}

	public static function wafUpdateSuccessful() {
		echo '<div class="updated is-dismissible"><p>' . __('The update was successful!', 'wordfence') . '</p></div>';
	}

	public static function getWAFBootstrapPath() {
		if (WF_IS_PRESSABLE || WF_IS_FLYWHEEL) {
			return WP_CONTENT_DIR . '/wordfence-waf.php';
		}
		return ABSPATH . 'wordfence-waf.php';
	}

	public static function getWAFBootstrapContent($currentAutoPrependedFile = null) {
		$bootstrapPath = dirname(self::getWAFBootstrapPath());
		$currentAutoPrepend = '';
		if ($currentAutoPrependedFile && is_file($currentAutoPrependedFile) && !WFWAF_SUBDIRECTORY_INSTALL) {
			$currentAutoPrepend = sprintf('
// This file was the current value of auto_prepend_file during the Wordfence WAF installation (%2$s)
if (file_exists(%1$s)) {
	include_once %1$s;
}', var_export($currentAutoPrependedFile, true), date('r'));
		}
		return sprintf('<?php
// Before removing this file, please verify the PHP ini setting `auto_prepend_file` does not point to this.
%3$s
if (file_exists(__DIR__.%1$s)) {
	define("WFWAF_LOG_PATH", __DIR__.%2$s);
	include_once __DIR__.%1$s;
}',
			var_export(wfUtils::relativePath(WORDFENCE_PATH . 'waf/bootstrap.php', $bootstrapPath, true), true),
			var_export(wfUtils::relativePath((WFWAF_SUBDIRECTORY_INSTALL ? WP_CONTENT_DIR . '/wflogs/' : WFWAF_LOG_PATH), $bootstrapPath, true), true),
			$currentAutoPrepend);
	}
	/**
	 * @param string $adminURL
	 * @param string $homePath
	 * @param bool $relaxedFileOwnership
	 * @param bool $output Whether or not to output the credentials collection form. If false, this function only returns the status.
	 * @return bool Returns true if the path is writable, otherwise false.
	 */
	public static function requestFilesystemCredentials($adminURL, $homePath = null, $relaxedFileOwnership = true, $output = true) {
		if ($homePath === null) {
			$homePath = wfUtils::getHomePath();
		}

		if (!$output) { ob_start(); }
		if (false === ($credentials = request_filesystem_credentials($adminURL, '', false, $homePath, array('version', 'locale'), $relaxedFileOwnership))) {
			if (!$output) { ob_end_clean(); }
			return false;
		}

		if (!WP_Filesystem($credentials, $homePath, $relaxedFileOwnership)) { // Failed to connect, Error and request again
			request_filesystem_credentials($adminURL, '', true, ABSPATH, array('version', 'locale'), $relaxedFileOwnership);
			if (!$output) { ob_end_clean(); }
			return false;
		}

		global $wp_filesystem;
		if ($wp_filesystem->errors->get_error_code()) {
			if (!$output) { ob_end_clean(); }
			return false;
		}

		if (!$output) { ob_end_clean(); }
		return true;
	}

	public static function queueCentralConfigurationSync() {
		static $hasRun;
		if ($hasRun) {
			return;
		}
		$hasRun = true;
		add_action('shutdown', 'wfCentral::requestConfigurationSync');
	}

}


class wfWAFAutoPrependHelper {

	private $serverConfig;
	/**
	 * @var string
	 */
	private $currentAutoPrependedFile;

	public static function helper($serverConfig = null, $currentAutoPrependedFile = null) {
		return new wfWAFAutoPrependHelper($serverConfig, $currentAutoPrependedFile);
	}

	public static function isValidServerConfig($serverConfig) {
		$validValues = array(
			"apache-mod_php",
			"apache-suphp",
			"cgi",
			"litespeed",
			"nginx",
			"iis",
			'manual',
		);
		return in_array($serverConfig, $validValues);
	}

	/**
	 * Verifies the .htaccess block for mod_php if present, returning true if no changes need to happen, false
	 * if something needs to update.
	 *
	 * @return bool
	 */
	public static function verifyHtaccessMod_php() {
		if (WFWAF_AUTO_PREPEND && PHP_MAJOR_VERSION > 5) {
			return true;
		}

		$serverInfo = wfWebServerInfo::createFromEnvironment();
		if (!$serverInfo->isApacheModPHP()) {
			return true;
		}

		$htaccessPath = wfUtils::getHomePath() . '.htaccess';
		if (file_exists($htaccessPath)) {
			$htaccessContent = file_get_contents($htaccessPath);
			$regex = '/# Wordfence WAF.*?# END Wordfence WAF/is';
			if (preg_match($regex, $htaccessContent, $matches)) {
				$wafBlock = $matches[0];
				$hasPHP5 = preg_match('/<IfModule mod_php5\.c>\s*php_value auto_prepend_file \'.*?\'\s*<\/IfModule>/is', $wafBlock);
				$hasPHP7 = preg_match('/<IfModule mod_php7\.c>\s*php_value auto_prepend_file \'.*?\'\s*<\/IfModule>/is', $wafBlock);
				$hasPHP8 = preg_match('/<IfModule mod_php\.c>\s*php_value auto_prepend_file \'.*?\'\s*<\/IfModule>/is', $wafBlock);
				if ($hasPHP5 && (!$hasPHP7 || !$hasPHP8)) { //Check if PHP 5 is configured, but not 7 or 8.
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * @param string|null $serverConfig
	 * @param string|null $currentAutoPrependedFile
	 */
	public function __construct($serverConfig = null, $currentAutoPrependedFile = null) {
		$this->serverConfig = $serverConfig;
		$this->currentAutoPrependedFile = $currentAutoPrependedFile;
	}

	public function getFilesNeededForBackup() {
		$backups = array();
		$htaccess = $this->getHtaccessPath();
		switch ($this->getServerConfig()) {
			case 'apache-mod_php':
			case 'apache-suphp':
			case 'litespeed':
			case 'cgi':
				if (file_exists($htaccess)) {
					$backups[] = $htaccess;
				}
				break;
		}
		if ($userIni = ini_get('user_ini.filename')) {
			$userIniPath = $this->getUserIniPath();
			switch ($this->getServerConfig()) {
				case 'cgi':
				case 'apache-suphp':
				case 'nginx':
				case 'litespeed':
				case 'iis':
					if (file_exists($userIniPath)) {
						$backups[] = $userIniPath;
					}
					break;
			}
		}
		return $backups;
	}

	public function downloadBackups($index = 0) {
		$backups = $this->getFilesNeededForBackup();
		if ($backups && array_key_exists($index, $backups)) {
			$url = site_url();
			$url = preg_replace('/^https?:\/\//i', '', $url);
			$url = preg_replace('/[^a-zA-Z0-9\.]+/', '_', $url);
			$url = preg_replace('/^_+/', '', $url);
			$url = preg_replace('/_+$/', '', $url);
			header('Content-Type: application/octet-stream');
			$backupFileName = ltrim(basename($backups[$index]), '.');
			header('Content-Disposition: attachment; filename="' . $backupFileName . '_Backup_for_' . $url . '.txt"');
			readfile($backups[$index]);
			die();
		}
	}

	/**
	 * @return mixed
	 */
	public function getServerConfig() {
		return $this->serverConfig;
	}

	/**
	 * @param mixed $serverConfig
	 */
	public function setServerConfig($serverConfig) {
		$this->serverConfig = $serverConfig;
	}

	/**
	 * @param WP_Filesystem_Base $wp_filesystem
	 * @throws wfWAFAutoPrependHelperException
	 */
	public function performInstallation($wp_filesystem) {
		$bootstrapPath = wordfence::getWAFBootstrapPath();
		if (!$wp_filesystem->put_contents($bootstrapPath, wordfence::getWAFBootstrapContent($this->currentAutoPrependedFile))) {
			throw new wfWAFAutoPrependHelperException(__('We were unable to create the <code>wordfence-waf.php</code> file in the root of the WordPress installation. It\'s possible WordPress cannot write to the <code>wordfence-waf.php</code> file because of file permissions. Please verify the permissions are correct and retry the installation.', 'wordfence'));
		}

		$serverConfig = $this->getServerConfig();

		$htaccessPath = $this->getHtaccessPath();
		$homePath = dirname($htaccessPath);

		$userIniPath = $this->getUserIniPath();
		$userIni = ini_get('user_ini.filename');

		$userIniHtaccessDirectives = '';
		if ($userIni) {
			$userIniHtaccessDirectives = sprintf('<Files "%s">
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
	Order deny,allow
	Deny from all
</IfModule>
</Files>
', addcslashes($userIni, '"'));
		}


		// .htaccess configuration
		switch ($serverConfig) {
			case 'apache-mod_php':
				$autoPrependDirective = sprintf("# Wordfence WAF
<IfModule mod_php5.c>
	php_value auto_prepend_file '%1\$s'
</IfModule>
<IfModule mod_php7.c>
	php_value auto_prepend_file '%1\$s'
</IfModule>
<IfModule mod_php.c>
	php_value auto_prepend_file '%1\$s'
</IfModule>
$userIniHtaccessDirectives
# END Wordfence WAF
", addcslashes($bootstrapPath, "'"));
				break;

			case 'litespeed':
				$escapedBootstrapPath = addcslashes($bootstrapPath, "'");
				$autoPrependDirective = sprintf("# Wordfence WAF
<IfModule LiteSpeed>
php_value auto_prepend_file '%s'
</IfModule>
<IfModule lsapi_module>
php_value auto_prepend_file '%s'
</IfModule>
$userIniHtaccessDirectives
# END Wordfence WAF
", $escapedBootstrapPath, $escapedBootstrapPath);
				break;

			case 'apache-suphp':
				$autoPrependDirective = sprintf("# Wordfence WAF
$userIniHtaccessDirectives
# END Wordfence WAF
", addcslashes($homePath, "'"));
				break;

			case 'cgi':
				if ($userIniHtaccessDirectives) {
					$autoPrependDirective = sprintf("# Wordfence WAF
$userIniHtaccessDirectives
# END Wordfence WAF
", addcslashes($homePath, "'"));
				}
				break;

		}

		if (!empty($autoPrependDirective)) {
			// Modify .htaccess
			$htaccessContent = $wp_filesystem->get_contents($htaccessPath);

			if ($htaccessContent) {
				$regex = '/# Wordfence WAF.*?# END Wordfence WAF/is';
				if (preg_match($regex, $htaccessContent, $matches)) {
					$htaccessContent = preg_replace($regex, $autoPrependDirective, $htaccessContent);
				} else {
					$htaccessContent .= "\n\n" . $autoPrependDirective;
				}
			} else {
				$htaccessContent = $autoPrependDirective;
			}

			if (!$wp_filesystem->put_contents($htaccessPath, $htaccessContent)) {
				throw new wfWAFAutoPrependHelperException(__('We were unable to make changes to the .htaccess file. It\'s possible WordPress cannot write to the .htaccess file because of file permissions, which may have been set by another security plugin, or you may have set them manually. Please verify the permissions allow the web server to write to the file, and retry the installation.', 'wordfence'));
			}
			if ($serverConfig == 'litespeed') {
				// sleep(2);
				$wp_filesystem->touch($htaccessPath);
			}

		}
		if ($userIni) {
			// .user.ini configuration
			switch ($serverConfig) {
				case 'cgi':
				case 'nginx':
				case 'apache-suphp':
				case 'litespeed':
				case 'iis':
					$autoPrependIni = sprintf("; Wordfence WAF
auto_prepend_file = '%s'
; END Wordfence WAF
", addcslashes($bootstrapPath, "'"));

					break;
			}

			if (!empty($autoPrependIni)) {

				// Modify .user.ini
				$userIniContent = $wp_filesystem->get_contents($userIniPath);
				if (is_string($userIniContent)) {
					$userIniContent = str_replace('auto_prepend_file', ';auto_prepend_file', $userIniContent);
					$regex = '/; Wordfence WAF.*?; END Wordfence WAF/is';
					if (preg_match($regex, $userIniContent, $matches)) {
						$userIniContent = preg_replace($regex, $autoPrependIni, $userIniContent);
					} else {
						$userIniContent .= "\n\n" . $autoPrependIni;
					}
				} else {
					$userIniContent = $autoPrependIni;
				}

				if (!$wp_filesystem->put_contents($userIniPath, $userIniContent)) {
					throw new wfWAFAutoPrependHelperException(sprintf(/* translators: File path. */ __('We were unable to make changes to the %1$s file. It\'s possible WordPress cannot write to the %1$s file because of file permissions. Please verify the permissions are correct and retry the installation.', 'wordfence'), basename($userIniPath)));
				}
			}
		}
	}

	/**
	 * @param WP_Filesystem_Base $wp_filesystem
	 * @throws wfWAFAutoPrependHelperException
	 *
	 * @return bool Whether or not the .user.ini still has a commented-out auto_prepend_file setting
	 */
	public function performIniRemoval($wp_filesystem) {
		$serverConfig = $this->getServerConfig();

		$htaccessPath = $this->getHtaccessPath();

		$userIniPath = $this->getUserIniPath();
		$userIni = ini_get('user_ini.filename');

		// Modify .htaccess
		$htaccessContent = $wp_filesystem->get_contents($htaccessPath);

		if (is_string($htaccessContent)) {
			$htaccessContent = preg_replace('/# Wordfence WAF.*?# END Wordfence WAF/is', '', $htaccessContent);
		} else {
			$htaccessContent = '';
		}

		if (!$wp_filesystem->put_contents($htaccessPath, $htaccessContent)) {
			throw new wfWAFAutoPrependHelperException(__('We were unable to make changes to the .htaccess file. It\'s possible WordPress cannot write to the .htaccess file because of file permissions, which may have been set by another security plugin, or you may have set them manually. Please verify the permissions allow the web server to write to the file, and retry the installation.', 'wordfence'));
		}
		if ($serverConfig == 'litespeed') {
			// sleep(2);
			$wp_filesystem->touch($htaccessPath);
		}

		if ($userIni) {
			// Modify .user.ini
			$userIniContent = $wp_filesystem->get_contents($userIniPath);
			if (is_string($userIniContent)) {
				$userIniContent = preg_replace('/; Wordfence WAF.*?; END Wordfence WAF/is', '', $userIniContent);
				$userIniContent = str_replace('auto_prepend_file', ';auto_prepend_file', $userIniContent);
			} else {
				$userIniContent = '';
			}

			if (!$wp_filesystem->put_contents($userIniPath, $userIniContent)) {
				throw new wfWAFAutoPrependHelperException(sprintf(/* translators: File path. */ __('We were unable to make changes to the %1$s file. It\'s possible WordPress cannot write to the %1$s file because of file permissions. Please verify the permissions are correct and retry the installation.', 'wordfence'), basename($userIniPath)));
			}

			return strpos($userIniContent, 'auto_prepend_file') !== false;
		}

		return false;
	}

	/**
	 * @param WP_Filesystem_Base $wp_filesystem
	 * @throws wfWAFAutoPrependHelperException
	 */
	public function performAutoPrependFileRemoval($wp_filesystem) {
		$bootstrapPath = wordfence::getWAFBootstrapPath();
		if (!$wp_filesystem->delete($bootstrapPath)) {
			throw new wfWAFAutoPrependHelperException(__('We were unable to remove the <code>wordfence-waf.php</code> file in the root of the WordPress installation. It\'s possible WordPress cannot remove the <code>wordfence-waf.php</code> file because of file permissions. Please verify the permissions are correct and retry the removal.', 'wordfence'));
		}
	}

	public function getHtaccessPath() {
		return wfUtils::getHomePath() . '.htaccess';
	}

	public function getUserIniPath() {
		$userIni = ini_get('user_ini.filename');
		if ($userIni) {
			return wfUtils::getHomePath() . $userIni;
		}
		return false;
	}

	public function usesUserIni() {
		$userIni = ini_get('user_ini.filename');
		if (!$userIni) {
			return false;
		}
		switch ($this->getServerConfig()) {
			case 'cgi':
			case 'apache-suphp':
			case 'nginx':
			case 'litespeed':
			case 'iis':
				return true;
		}
		return false;
	}

	public function uninstall() {
		/** @var WP_Filesystem_Base $wp_filesystem */
		global $wp_filesystem;

		$htaccessPath = $this->getHtaccessPath();
		$userIniPath = $this->getUserIniPath();

		$adminURL = admin_url('/');
		$allow_relaxed_file_ownership = true;
		$homePath = dirname($htaccessPath);

		ob_start();
		if (false === ($credentials = request_filesystem_credentials($adminURL, '', false, $homePath,
				array('version', 'locale'), $allow_relaxed_file_ownership))
		) {
			ob_end_clean();
			return false;
		}

		if (!WP_Filesystem($credentials, $homePath, $allow_relaxed_file_ownership)) {
			// Failed to connect, Error and request again
			request_filesystem_credentials($adminURL, '', true, ABSPATH, array('version', 'locale'),
				$allow_relaxed_file_ownership);
			ob_end_clean();
			return false;
		}

		if ($wp_filesystem->errors->get_error_code()) {
			ob_end_clean();
			return false;
		}
		ob_end_clean();

		if ($wp_filesystem->is_file($htaccessPath)) {
			$htaccessContent = $wp_filesystem->get_contents($htaccessPath);
			$regex = '/# Wordfence WAF.*?# END Wordfence WAF/is';
			if (preg_match($regex, $htaccessContent, $matches)) {
				$htaccessContent = preg_replace($regex, '', $htaccessContent);
				if (!$wp_filesystem->put_contents($htaccessPath, $htaccessContent)) {
					return false;
				}
			}
		}

		if ($wp_filesystem->is_file($userIniPath)) {
			$userIniContent = $wp_filesystem->get_contents($userIniPath);
			$regex = '/; Wordfence WAF.*?; END Wordfence WAF/is';
			if (preg_match($regex, $userIniContent, $matches)) {
				$userIniContent = preg_replace($regex, '', $userIniContent);
				if (!$wp_filesystem->put_contents($userIniPath, $userIniContent)) {
					return false;
				}
			}
		}

		$bootstrapPath = wordfence::getWAFBootstrapPath();
		if ($wp_filesystem->is_file($bootstrapPath)) {
			$wp_filesystem->delete($bootstrapPath);
		}
		return true;
	}
}

class wfWAFAutoPrependHelperException extends Exception {
}
