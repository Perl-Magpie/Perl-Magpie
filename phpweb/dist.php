<?php

require("include/magpie.inc.php");

sw();
$debug   = $_GET['debug']   ?? 0;
$dist    = $_GET['dist']    ?? "";
$version = $_GET['version'] ?? "";
$time    = $_GET['time']    ?? 0;
$json    = $_GET['json']    ?? 0;
$action  = $_GET['action']  ?? "";
$filter  = $_GET['filter']  ?? "";

$uri       = $_SERVER['REQUEST_URI'] ?? "";
$json_link = "$uri&json=1";

// Allow Module::Sub in the URL also
$dist  = str_replace("::", "-", $dist);

if (!$dist) {
	error_out("Module not specified", 19034);
}

////////////////////////////////////////////////////////

$version_list = get_dist_versions($dist);
if (!$version) {
	$version = $version_list[0] ?? "";
}

$results   = get_test_results($dist, $version, $time);
$grade_cnt = get_grade_count($results);
$os_cnt    = get_os_count($results);
$group     = group_test_results($results);
$dist_fmt  = str_replace("-", "::", $dist);

$s->assign('dist', $dist);
$s->assign('dist_fmt', $dist_fmt);
$s->assign('dist_ver', $version);
$s->assign('grade_count', $grade_cnt);
$s->assign('os_count', $os_cnt);
$s->assign('version_list', $version_list);
$s->assign('results', $group);
$s->assign('result_count', count($results));
$s->assign('json_link', $json_link);

// Use the raw results (not grouped)
if ($action === 'show_tests') {
	if ($filter) {
		$results = filter_results($filter, $results);
	}

	$s->assign('results', $results);
}

$ms = intval(sw());
$s->assign('page_ms', $ms);

////////////////////////////////////////////

if ($debug) {
	$s->assign('debug_output', k($s->tpl_vars, KRUMO_RETURN));
} elseif ($json) {
	send_json($s->tpl_vars);
}

if ($action === 'show_tests') {
	print $s->fetch("tpls/dist_results.stpl");
} else {
	print $s->fetch("tpls/dist.stpl");
}

////////////////////////////////////////////////////////

function get_dist_versions($dist) {
	global $dbq;

	$sql = "SELECT distinct(distribution_version) as distribution_version FROM distribution_info WHERE distribution_name = ? ORDER BY distribution_version DESC;";
	$ret = $dbq->query($sql, [$dist], 'one_column');

	usort($ret, 'version_compare');
	$ret = array_reverse($ret);

	return $ret;
}

function group_test_results($data) {
	$ret = [];
	foreach ($data as $x) {
		$pver  = $x['perl_version'];
		$os    = $x['osname'];
		$grade = $x['grade'];

		incr($ret[$pver][$os][$grade], 1);
	}

	foreach ($ret as $pv => $x) {
		foreach ($x as $os => $y) {
			$total = array_sum($y);
			foreach (array_keys($y) as $grade) {
				$num = $ret[$pv][$os][$grade];
				$per = sprintf("%0.1f", ($num / $total) * 100);
				$ret[$pv][$os][$grade] = $per;
			}
		}
	}

	uksort($ret, 'version_compare');
	$ret = array_reverse($ret);

	return $ret;
}

function get_os_count($data) {
	$oses = [
		'windows' => 0,
		'bsd'     => 0,
		'darwin'  => 0,
		'linux'   => 0,
		'solaris' => 0,
	];

	foreach ($data as $x) {
		$y = $x['osname'];

		incr($oses[$y]);
	}

	return $oses;
}

function get_grade_count($data) {
	$grades = [
		'PASS'    => 0,
		'FAIL'    => 0,
		'NA'      => 0,
		'UNKNOWN' => 0,
	];

	foreach ($data as $x) {
		$y = $x['grade'];

		incr($grades[$y]);
	}

	return $grades;
}

function version_sort($a, $b) {
	$aver = $a['perl_version'];
	$bver = $b['perl_version'];

	// Sort by version first, and then date
	if ($aver != $bver) {
		return version_compare($bver, $aver);
	} else {
		return ($b['unixtime'] <=> $a['unixtime']);
	}
}

function get_test_results($dist, $version, $time) {
	global $dbq;

	$time_str = date("Y-m-d H:i:s", $time);

	$sql = "SELECT arch_name, distribution_name, grade, guid, osname, perl_version, tester, EXTRACT(EPOCH FROM test_ts) as unixtime, tester.name as tester_name, octet_length(text_report) as x_test_bytes
		FROM test
		INNER JOIN tester ON (test.tester = tester.uuid)
		INNER JOIN distribution_info USING (distribution_id)
		INNER JOIN os_arch USING (arch_id)
		WHERE test_ts > ? AND distribution_name = ? AND distribution_version = ?
		ORDER BY perl_version desc";

	$ret = $dbq->query($sql, [$time_str, $dist, $version]);

	usort($ret, 'version_sort');

	return $ret;
}

function filter_results($filter, $data) {
	$ret = [];

	foreach ($data as $x) {
		$os     = $x['osname'];
		$grade  = strtolower($x['grade']);
		$pver   = $x['perl_version'];
		$tester = $x['tester_name'];
		$arch   = $x['arch_name'];

		// Build a string of all the pieces and run the filter against the string
		// This is ghetto but it works
		$str = "$pver;$os;$grade;$tester";

		if (preg_match("/$filter/i", $str)) {
			$ret[] = $x;
		}
	}

	return $ret;
}

function get_bad_test_uuids($data) {
	$review = [];
	foreach ($data as $x) {
		$grade = $x['grade'];
		$uuid  = $x['guid'];
		$bytes = $x['x_test_bytes'];

		if ($grade !== 'PASS' && $bytes < 128) {
			$review[] = $uuid;
		}
	}

	$ret = array_unique($review);

	return $ret;
}

// vim: tabstop=4 shiftwidth=4 noexpandtab autoindent softtabstop=4
