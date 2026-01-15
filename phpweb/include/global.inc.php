<?php

//check_valid();

function check_valid() {
	$ip    = $_SERVER['REMOTE_ADDR']     ?? "";
	$agent = $_SERVER['HTTP_USER_AGENT'] ?? "";

	$block_file = "/tmp/block.txt";
	if (is_readable($block_file)) {
		$lines = file($block_file);

		foreach ($lines as $line) {
			$line = trim($line);

			if (preg_match("/$line/", $ip)) {
				teapot();
			}
		}
	}

}

function teapot() {
	//http_response_code(418);
	//print "<h1>#418: Sorry... I am a teapot</h1>";

	http_response_code(429);
	print "<h1>#429: Too Many Requests";

	die;
}

function db_init() {
	global $BASE_DIR;
	$ini_file = "$BASE_DIR/include/magpie.config.ini";

	if (!is_readable($ini_file)) {
		error_out("Unable to read DB credentials from <code>$ini_file</code>", 98573);
	}

	$x   = parse_ini_file($ini_file, true);
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

function time_this($name = "end") {
	static $data;
	$time = microtime(1);

	$data[] = [
		'name' => $name,
		'time' => $time,
	];

	$ret = [];
	if ($name === "end") {
		$items  = array_column($data, 'name');
		$maxlen = max(array_map('strlen', $items));

		if ($maxlen < 5) {
			$maxlen = 5;
		}

		$start  = $data[0]['time'];
		$total  = $time - $start;

		mplog(str_repeat("-", 40));
		foreach ($data as $x) {
			$name = $x['name'];
			$time = $x['time'];

			if (!empty($prev_time)) {
				$str = sprintf("TIME_THIS: %-{$maxlen}s = %4d ms", $name, ($time - $prev_time) * 1000);
				mplog($str);

				$ret[$name] = sprintf("%0.3f", $time - $prev_time);
			}

			$prev_time = $time;
			$prev_name = $name;
		}

		mplog(str_repeat("-", 40));
		$total_str = sprintf("TIME_THIS: %-{$maxlen}s = %4d ms", 'total', $total * 1000);
		mplog($total_str);
		mplog(str_repeat("-", 40));

		return $ret;
	}
}

// Split the URI parts into an array
// /dist/Foo-Bar/v0.2.0 => ['dist', 'Foo-Bar', 'v0.2.0']
function get_uri_parts($uri = "") {
	if (!$uri) {
		$uri = $_SERVER['REQUEST_URI'] ?? "";
	}

	// Sometimes GET stuff gets mixed the URI in so we filter out
	// anything after a '?'
	$uri = preg_replace("/\?.+/", "", $uri);

	// Break apart the the URI at the '/` boundaries
	$parts = preg_split('/\//', $uri, 0, PREG_SPLIT_NO_EMPTY);

	return $parts;
}

// Write the zstd compressed test to the DB
function write_test_to_db($uuid, $test_str, $obj) {
	global $dbq;

	$dict_file = $GLOBALS['ZSTD_DICT'];
	$sql       = "SELECT dict_id FROM dict_info WHERE dict_file = ?;";
	$dict_id   = $dbq->query($sql, [basename($dict_file)], 'one_data');

	if (!$dict_id) {
		error_out("Could not find info in dict_info for $dict_file", 65902);
	}

	$zstd_level = 12; // ZSTD compression level
	$len_orig   = strlen($test_str);
	$dict       = file_get_contents($dict_file);
	$zstd_str   = zstd_compress_dict($test_str, $dict, $zstd_level);
	$len        = strlen($zstd_str);

	$grade = strtoupper($obj['grade']     ?? "");
	$dist  = $obj['distribution_name']    ?? "";
	$distv = $obj['distribution_version'] ?? "";

	mplog("Wrote $len bytes ($len_orig) to DB for $uuid $dist/$distv ($grade)");

	$sql = "INSERT INTO test_results (guid, txt_zstd, dict_id) VALUES (:uuid, :data, :dict_id);";
	$sth = $dbq->dbh->prepare($sql);

	$sth->bindParam(':uuid'   , $uuid    , PDO::PARAM_STR);
	$sth->bindParam(':data'   , $zstd_str, PDO::PARAM_LOB); // Use LOB for bytea
	$sth->bindParam(':dict_id', $dict_id , PDO::PARAM_STR);

	$sth->execute();

	return 1;
}


// vim: tabstop=4 shiftwidth=4 noexpandtab autoindent softtabstop=4
