<?php

require("include/magpie.inc.php");

sw();
$debug  = $_GET['debug']  ?? 0;
$json   = $_GET['json']   ?? 0;
$count  = $_GET['count']  ?? 500;
$offset = $_GET['offset'] ?? 0;

if ($count > 10000) { die; }

////////////////////////////////////////////////////////

$log = get_log($count, $offset);

$ms = intval(sw());
$s->assign('page_ms', $ms);
$s->assign('log', $log);

///////////////////////////////////

if ($json) {
	send_json($s->tpl_vars);
} elseif ($debug) {
	$s->assign('debug_output', k($s->tpl_vars, KRUMO_RETURN));
}

print $s->fetch("tpls/log.stpl");

////////////////////////////////////////////////////////

function get_log($count, $offset) {
	global $dbq;

	$sql = "SELECT distribution_name, grade, EXTRACT(EPOCH FROM test_ts) as unixtime, distribution_version,
					osname, guid, octet_length(text_report) as test_bytes
		FROM test
		INNER JOIN distribution_info USING (distribution_id)
		ORDER BY test_ts DESC
		LIMIT ?
		OFFSET ?;";

	$ret = $dbq->query($sql, [$count, $offset]);

	// Normalize OS names
	foreach ($ret as &$x) {
		$x['osname_fmt'] = os_normalize($x['osname']);
	}

	return $ret;
}

// vim: tabstop=4 shiftwidth=4 noexpandtab autoindent softtabstop=4
