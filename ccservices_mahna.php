<?php
function dbCon() {
	$con = mysqli_connect("localhost", "gts", "opengts", "gts");
	// Check connection
	if (mysqli_connect_errno()) {
		return "420";
		//"Failed to connect to MySQL: " . mysqli_connect_error();
	}
	return $con;
}
function hex2str($hex) {
	$string   = '';
	$strSplit = str_split($hex);
	foreach ($strSplit as $key => $char)
		if (($key + 1) % 2 == 0)
			$string .= chr(hexdec($hex[$key - 1] . $hex[$key]));
	return $string;
}
function GetDeviceList() {
	$conDb  = dbCon();
	$query  = "
	          SELECT
			deviceID,
			equipmentType,
			uniqueID,
			deviceCode,
			simPhoneNumber,
			lastTotalConnectTime,
			ipAddressCurrent,
			remotePortCurrent,
			lastValidLatitude,
			lastValidLongitude,
			lastValidHeading,
			lastGPSTimestamp,
			isActive,
			HEX(description) AS 'description',
			lastUpdateTime,
			creationTime,
			lastEventTimestamp,
			lastStopTime,
			lastStartTime,
			lastValidSpeedKPH
		FROM
			Device
	;";
	$result = mysqli_query($conDb, $query);
	$ret    = array();
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		if (empty($row['deviceID']))
			$ret = '410';
		else {
			$row['description'] = hex2str($row['description']);
			$ret[]              = $row;
		}
	}
	return ($ret);
}
function GetEventList($timeS, $timeE, $limit = 30, $device = false) {
	$conDb  = dbCon();
	// Format the arguments {
	
	$subtractTime = true; // todo: set this to FALSE!!!
	$subtractSql = '';
	if( $subtractTime ){
		$subtractSql = '- 3600';
	}
	
	$device = ($device === false) ? ('%') : ($device);
	// }
	$query  = "
		 SELECT
			deviceID,
			FROM_UNIXTIME(`timestamp` $subtractSql) AS mytime,
			statusCode,
			latitude,
			longitude,
			speedKPH,
			heading,
			altitude,
			FROM_UNIXTIME(creationTime $subtractSql) AS ontime
		FROM
			EventData
		WHERE
			`timestamp` >= '$timeS'
		AND `timestamp` <= '$timeE'
		AND deviceID LIKE '$device'
		LIMIT $limit
	";
	//die($query);
	$query = "SELECT * FROM ($query) t1 ORDER BY t1.`mytime` DESC;";
	$result = mysqli_query($conDb, $query);
	$ret    = array();
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
		$ret[] = $row;
	
	return ($ret);
}
function GetLastEvent($device = false){
	$conDb  = dbCon();
	// Format the arguments {
	
	$subtractTime = true; // todo: set this to FALSE!!!
	$subtractSql = '';
	if( $subtractTime ){
		$subtractSql = '- 3600';
	}
	
	$device = ($device === false) ? ('%') : ($device);
	$query  = "
		 SELECT
			deviceID,
			FROM_UNIXTIME(`timestamp` $subtractSql) AS mytime,
			statusCode,
			latitude,
			longitude,
			speedKPH,
			heading,
			altitude,
			FROM_UNIXTIME(creationTime $subtractSql) AS ontime
		FROM
			EventData
		WHERE
			deviceID LIKE '$device'
		ORDER BY `mytime` DESC
		LIMIT 1
	";
	//die($query);
	//$query = "SELECT * FROM ($query) t1 ORDER BY t1.`mytime` DESC;";
	$result = mysqli_query($conDb, $query);
	$ret    = array();
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
		$ret[] = $row;
	
	return (@$ret[0]? $ret[0]: array());
}
// Handle requests
if (isset($_GET['method'])) {
	$func = $_GET['method'];
	if (!in_array($func, array(
		'GetDeviceList',
		'GetEventList',
		'GetLastEvent'
	)))
		$ret = array();
	else if (isset($_GET['params']))
		$ret = call_user_func_array($_GET['method'], $_GET['params']);
	else
		$ret = call_user_func($_GET['method']);
	// Write output
	ob_clean();
	header('Content-Type: application/json');
	echo json_encode($ret);
	exit;
}
?>
