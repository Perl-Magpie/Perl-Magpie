<?php

////////////////////////////////////////////////////////

require("../../include/magpie.inc.php");
include "lib/Server.php";

$server = new Lightbulb\Json\Rpc2\Server;

$raw_post    = trim(file_get_contents('php://input', false, null, 0, 15));
$is_json_rpc = (substr($raw_post, 0, 1) == "{");

if (!$is_json_rpc) {
	print $s->fetch("tpls/index.stpl");
	exit;
}

// Methods to expose
$server->echo_data = 'echo_data';
$server->math      = new math;
$server->dist      = new dist;

// Process any JSON-RPC requests
$server->handle();

////////////////////////////////////////////////////////

function echo_data() {
	$data = func_get_args();

	return $data;
}

#[\AllowDynamicProperties]
class math {
    public function pi() {
        return 3.1415926;
    }
}

#[\AllowDynamicProperties]
class dist {
	public function get_test($uuid) {
		global $dbq;

		$sql = "SELECT (test_ts, guid, tester, grade, perl_version, osname, arch_id, distribution_id)
			FROM test
			WHERE guid = ?";

		$x = $dbq->query($sql, [$uuid], 'one_row');

		return $x;
	}

    public function add_test($test_epoch, $test_uuid, $dist_name, $dist_version, $tester_name, $grade, $perl_version, $os_name, $os_version, $test_body) {
		global $dbq;

		$exists = $this->get_test($test_uuid);
		if ($exists) {
			$ret = [
				'error_count'   => 1,
				'error_message' => "Test '$test_uuid' already in DB",
				'error_num'     => 48414
			];

			return $ret;
		}

		$test_time_str = date("Y-m-d H:i:s", $test_epoch);
		$tester_uuid   = get_tester_uuid($tester_name);
		$dist_id       = get_distribution_id($dist_name, $dist_version);
		$arch_id       = get_arch_id($os_version);
		$grade         = strtoupper($grade);

		$sql = "INSERT INTO test (test_ts, guid, tester, grade, perl_version, osname, arch_id, distribution_id)
			VALUES (?,?,?,?,?,?,?,?);";

		$obj = [
			$test_time_str,
			$test_uuid,
			$tester_uuid,
			$grade,
			$perl_version,
			$os_name,
			$arch_id,
			$dist_id,
		];

		$ok = $dbq->query($sql, $obj);

		$obj2 = [
			'grade'                => $grade,
			'distribution_name'    => $dist_name,
			'distribution_version' => $dist_version,
		];

		$ok2 = write_test_to_db($test_uuid, $test_body, $obj);

		return $test_uuid;
    }
}

// vim: tabstop=4 shiftwidth=4 noexpandtab autoindent softtabstop=4
