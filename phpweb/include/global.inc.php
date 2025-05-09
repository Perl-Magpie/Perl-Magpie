<?php

function db_init() {
	// FIXME: This should not be hardcoded
	$ini_file = "/home/bakers/db/magpie.ini";

	if (!is_readable($ini_file)) {
		error_out("Unable to read DB credentials from <code>$ini_file</code>", 98573);
	}

	$x   = parse_ini_file("/home/bakers/db/magpie.ini", true);
	$dsn = "pgsql:host={$x['db']['host']};port={$x['db']['port']};dbname={$x['db']['dbname']}";
	$dbq = new DBQuery($dsn, $x['db']['username'], $x['db']['password']);

	return $dbq;
}

function hello_world() {
	global $dbq;
	$ver = $dbq->query("SELECT version()", 'one_data');

	return $ver;
}

function send_json($hash) {
	header('Content-type: application/json');
	$json = json_encode($hash);

	print $json;
	exit(0);
}

// Stopwatch function: returns milliseconds
function sw() {
	static $start = null;

	if (!$start) {
		$start = hrtime(1);
	} else {
		$ret   = (hrtime(1) - $start) / 1000000;
		$start = null; // Reset the start time
		return $ret;
	}
}

// Increment a variable (E_NOTICE compatible)
function incr(&$i, $value = 1) {
	// If the value is already there add to it
	if (isset($i)) {
		$i += $value;
	// If the value isn't there, just set it initially
	} else {
		$i = $value;
	}
}

function human_time(int $seconds) {
	$num  = 0;
	$unit = "";

	if ($seconds < 300) {
		$ret = "just now";
	} elseif ($seconds < 3600) {
		$num  = intval($seconds / 60);
		$unit = "minute";
	} elseif ($seconds < 86400) {
		$num  = intval($seconds / 3600);
		$unit = "hour";
	} elseif ($seconds < 86400 * 30) {
		$num  = intval($seconds / 86400);
		$unit = "day";
	} elseif ($seconds < (86400 * 365)) {
		$num  = intval($seconds / (86400 * 30));
		$unit = "month";
	} else {
		$num  = intval($seconds / (86400 * 365));
		$unit = "year";
	}

	if ($num > 1) {
		$unit .= "s";
	}

	if ($unit) {
		$ret = "$num $unit";
	}

	return $ret;
}

function human_time_diff(int $unixtime, $suffix = '') {
	$num  = 0;
	$unit = "";

	$seconds = time() - $unixtime;

	if ($seconds < 300) {
		$ret = "just now";
	} elseif ($seconds < 3600) {
		$num  = intval($seconds / 60);
		$unit = "minute";
	} elseif ($seconds < 86400) {
		$num  = intval($seconds / 3600);
		$unit = "hour";
	} elseif ($seconds < 86400 * 30) {
		$num  = intval($seconds / 86400);
		$unit = "day";
	} elseif ($seconds < (86400 * 365)) {
		$num  = intval($seconds / (86400 * 30));
		$unit = "month";
	} else {
		$num  = intval($seconds / (86400 * 365));
		$unit = "year";
	}

	if ($num > 1) {
		$unit .= "s";
	}

	if ($unit) {
		$ret = "$num $unit";
	}

	if ($suffix && $seconds >= 300) {
		$ret .= $suffix;
	}

	return $ret;
}

function dist_to_human($str) {
	$ret = str_replace("-", "::", $str);

	return $ret;
}

function os_normalize($str) {
	if (strtolower($str) === "bsd") {
		$ret = "BSD";
	} else {
		$ret = ucfirst($str);
	}

	return $ret;
}

function format_date($ut) {
	$ret = date("Y-m-d H:i:s", $ut);

	return $ret;
}

function run_cmd($cmd) {
	$start   = hrtime(true);
	$cmd     = escapeshellcmd($cmd);
	$process = proc_open($cmd, [
		1 => ['pipe', 'w'], // STDOUT
		2 => ['pipe', 'w'], // STDERR
	], $pipes);

	if (!is_resource($process)) { return []; }

	$stdout = stream_get_contents($pipes[1]);
	fclose($pipes[1]);
	$stderr = stream_get_contents($pipes[2]);
	fclose($pipes[2]);
	$exit   = proc_close($process);

	$ret = [
		'exit_code' => $exit,
		'stdout'    => trim($stdout),
		'stderr'    => trim($stderr),
		'cmd'       => $cmd,
		'exec_ms'   => (hrtime(true) - $start) / 1000000,
	];

	return $ret;
}

function is_admin() {
	$ip = $_SERVER['REMOTE_ADDR'] ?? "";
	if ($ip === '67.22.241.17') {
		$ret = true;
	} else {
		$ret = false;
	}

	return $ret;
}

function is_uuid(string $uuid) {
    $ret = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);

	return $ret;
}

function human_size($size) {
	# If the size is 0 or less, return 0 B this stops math errors from occurring
	if ($size <= 0) {
		return '0B';
	} else {
		$unit=array('B','K','M','G','T','P');
		return @round($size/pow(1024,($i=floor(log($size,1024)))),2) . $unit[$i];
	}
}

function mplog($str) {
	$str = trim($str);
	syslog(LOG_INFO, $str);
}

function error_out($msg, $errno) {
	global $s;

	$s->assign('message', $msg);
	$s->assign('errno', $errno);

	print $s->fetch("tpls/error.stpl");
	exit(9);
}

// vim: tabstop=4 shiftwidth=4 noexpandtab autoindent softtabstop=4
