<?php

require("include/magpie.inc.php");

sw();
$debug = $_GET['debug'] ?? 0;
$json  = $_GET['json']  ?? 0;

////////////////////////////////////////////////////////

$stats = get_stats();
$ms    = intval(sw());

$s->assign('page_ms', $ms);
$s->assign('stats'  , $stats);

///////////////////////////////////

if ($json) {
	send_json($s->tpl_vars);
} elseif ($debug) {
	$s->assign('query_summary', $dbq->query_summary());
	$s->assign('debug_output', k($s->tpl_vars, KRUMO_RETURN));
}

print $s->fetch("tpls/index.stpl");

////////////////////////////////////////////////////////

function get_stats() {
	global $mc;

	$ckey = 'index_stats';
	$data = $mc->get($ckey);

	if ($data) {
		$data['from_cache'] = true;
		return $data;
	}

	$ret["last_hour"] = get_top_x(10, time() - 3600);
	$ret["last_day"]  = get_top_x(10, time() - 86400);

	$ret['count_hour']  = get_count(time() - 3600         , time());
	$ret['count_day']   = get_count(time() - 86400        , time());
	$ret['count_week']  = get_count(time() - 86400 * 7    , time());
	$ret['count_month'] = get_count(time() - 86400 * 30.41, time());
	$ret['count_total'] = get_count(0, time());

	$ok = $mc->set($ckey, $ret, 180);

	$ret['from_cache'] = true;

	return $ret;
}

function get_count($start, $end) {
	global $dbq;

	$ds = date("Y-m-d H:i:s", $start);
	$de = date("Y-m-d H:i:s", $end);

	$sql = "SELECT count(guid)
		FROM test
		WHERE test_ts > ? and test_ts < ?";

	$ret = $dbq->query($sql, [$ds, $de], 'one_data');

	return $ret;
}

function get_top_x(int $number, int $time) {
	global $dbq;

	$time_str = date("Y-m-d H:i:s", $time);

	$sql = "SELECT count(guid), distribution_name
		FROM test
		INNER JOIN distribution_info USING (distribution_id)
		WHERE test_ts > ?
		GROUP BY distribution_name
		ORDER BY 1 desc
		LIMIT ?;";

	$ret = $dbq->query($sql, [$time_str, $number]);

	return $ret;
}

function get_test_results($dist, $time) {
	global $dbq;

	$time_str = date("Y-m-d H:i:s", $time);

	$sql = "SELECT *
		FROM test
		WHERE test_ts > ? AND distribution = ?
		ORDER BY 1 desc";

	$ret = $dbq->query($sql, [$time_str, $dist]);

	return $ret;
}

// vim: tabstop=4 shiftwidth=4 noexpandtab autoindent softtabstop=4
