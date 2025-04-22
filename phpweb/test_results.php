<?php

require("include/magpie.inc.php");
$ZSTD_DICT = "/var/tmp/magpie-dict";

sw();
$debug = $_GET['debug'] ?? 0;
$json  = $_GET['json']  ?? 0;
$uuid  = $_GET['uuid']  ?? 0;

if (!$uuid) {
	error_out("Missing test UUID", 12931);
} elseif (!is_uuid($uuid)) {
	error_out("Invalid UUID", 13031);
}

$FROM_CACHE = false;

////////////////////////////////////////////////////////

$info = get_test_info($uuid);
if (!$info) {
	error_out("Unable to find $uuid", 69035);
}

// Highlight some strings in HTML to make reading easier
$test_body               = $info['text_report'];
$info['text_report_fmt'] = highlight_body($test_body);

$ms = sw();
$s->assign('page_ms' , $ms);
$s->assign('uuid'    , $uuid);
$s->assign('info'    , $info);
$s->assign('cached'  , $FROM_CACHE);

///////////////////////////////////

if ($json) {
	send_json($s->tpl_vars);
} elseif ($debug) {
	$s->assign('debug_output', k($s->tpl_vars, KRUMO_RETURN));
}

print $s->fetch("tpls/test_results.stpl");

////////////////////////////////////////////////////////
////////////////////////////////////////////////////////

// Write the brotli compressed test to the DB
function write_test_to_db($uuid, $test_str) {
	global $dbq;

	$brotli_str = brotli_compress($test_str, 9, BROTLI_TEXT);

	$sql = "UPDATE test SET text_report = :data WHERE guid = :uuid;";
    $sth = $dbq->dbh->prepare($sql);

    $sth->bindParam(':uuid', $uuid, PDO::PARAM_STR);
    $sth->bindParam(':data', $brotli_str, PDO::PARAM_LOB); // Use LOB for bytea

    $sth->execute();

	$len      = strlen($brotli_str);
	$len_orig = strlen($test_str);
	mplog("Wrote $len bytes ($len_orig) to DB for $uuid");

	////////////////////////////////////////////////////////////////////

	$use_zstd = 1;

	if ($use_zstd) {
		$dict     = file_get_contents($GLOBALS['ZSTD_DICT']);
		$zstd_str = zstd_compress_dict($test_str, $dict);

		//kd($dict);

		$sql = "INSERT INTO test_results (guid, txt_zstd) VALUES (:uuid, :data);";
		$sth = $dbq->dbh->prepare($sql);

		$sth->bindParam(':uuid', $uuid, PDO::PARAM_STR);
		$sth->bindParam(':data', $zstd_str, PDO::PARAM_LOB); // Use LOB for bytea

		$sth->execute();
	}

	return 1;
}

// Highlight via HTML some important strings in the test body
//
// Note: We need to be careful with these and anchor the regexps with ^ and $ if possible
// otherwise these can get pretty slow as we're parsing 8k of text for each one.
// As of 2025-03-31 I can parse a test output in about 4ms with the 11 rules that are in place.
function highlight_body($test_body) {
	// Green/success
	$test_body = preg_replace("/\b(Result: PASS)\b/","<span class=\"status_pass\">$1</span>", $test_body);
	$test_body = preg_replace("/^(All tests successful\.)$/m","<span class=\"status_pass\">$1</span>", $test_body);
	$test_body = preg_replace("/^(x?t.*\. ok)$/m","<span class=\"status_pass\">$1</span>", $test_body);

	// Red/fail
	$test_body = preg_replace("/^((#\s+)?Failed.*)/mi","<span class=\"status_fail\">$1</span>", $test_body);
	$test_body = preg_replace("/\b(Result: FAIL)\b/i","<span class=\"status_fail\">$1</span>", $test_body);
	$test_body = preg_replace("/(Parse errors.*)$/mi","<span class=\"status_fail\">$1</span>", $test_body);
	// Matches 'ExeAsDll.xs:1125:41: error:'
	$test_body = preg_replace("/^(.+\w+\.\w{1,3}.*error:)/mi","<span class=\"status_fail\">$1</span>", $test_body);
	$test_body = preg_replace("/^(Compilation failed in require.*)$/mi","<span class=\"status_fail\">$1</span>", $test_body);

	// Gray/NA
	$test_body = preg_replace("/(Perl v.*required.*this is only.*)/mi","<span class=\"status_na\">$1</span>", $test_body);
	$test_body = preg_replace("/(! perl.*v\d.*)$/mi","<span class=\"status_na\">$1</span>", $test_body);
	$test_body = preg_replace("/^(x?t.*\. skipped:.*)$/m","<span class=\"status_na\">$1</span>", $test_body);
	$test_body = preg_replace("/^(.+\w+\.\w{1,3}.*note:)/mi","<span class=\"status_na\">$1</span>", $test_body);

	// Orange/Unknown
	$test_body = preg_replace("/^(\s*[gdc]?make:.*error.*)$/mi","<span class=\"status_unknown\">$1</span>", $test_body);
	$test_body = preg_replace("/^(.+\w+\.\w{1,3}.*warning:)/mi","<span class=\"status_unknown\">$1</span>", $test_body);
	$test_body = preg_replace("/^(Warning:.*)/mi","<span class=\"status_unknown\">$1</span>", $test_body);

	return $test_body;
}

// Get test body from CPT via shell/Perl (slow)
function get_test_body_perl($uuid) {
	putenv("PERL5LIB=/home/bakers/perl5/lib/perl5/");
	$cmd = "/usr/bin/perl /home/bakers/bin/get_test_body.pl $uuid";
	$cmd = escapeshellcmd($cmd);

	$x      = run_cmd($cmd);
	$json   = $x['stdout'];
	$exit   = $x['exit_code'];
	$stderr = $x['stderr'];

	global $FROM_CACHE;

	if ($exit === 0) {
		$x   = json_decode($json, true);
		$ret = $x['body'] ?? 'ERROR 193871';

		$FROM_CACHE = $x['cache'] === 1;
	} else {
		$ret = "Error running command. Exit: '$exit' Error: $stderr";
	}

	return $ret;
}

// Get information about a test from the DB
function get_test_info($uuid) {
	global $dbq;

	$sql = "SELECT *, EXTRACT(EPOCH FROM test_ts) as test_unixtime, tester.name as tester_name, txt_zstd
		FROM test
		LEFT  JOIN test_results USING (guid)
		INNER JOIN tester ON (test.tester = tester.uuid)
		INNER JOIN os_arch USING (arch_id)
		INNER JOIN distribution_info USING (distribution_id)
		WHERE guid = ?;";

	$ret = $dbq->query($sql, [$uuid], 'one_row');

	$rawz = $ret['txt_zstd']    ?? null;
	$rawb = $ret['text_report'] ?? null;

	$str = "";
	if ($rawz) {
		$zst  = @stream_get_contents($rawz);
		$dict = file_get_contents($GLOBALS['ZSTD_DICT']);
		$str  = zstd_uncompress_dict($zst, $dict);
	} elseif ($rawb) {
		$bro = stream_get_contents($rawb);
		$str = @brotli_uncompress($bro);
		$str = $str ?? "";
	}

	$ret['text_report'] = trim($str);

	//$db_len = strlen($bro);
	//if (($db_len > 0) && (strlen($str) == 0)) {
	//    mplog("$db_len bytes in DB but is not valid brotli");
	//}

	// If we don't have the text report in the DB we fetch it via HTTP from CPT
	if (!$ret['text_report']) {
		$ret['text_report'] = fetch_test_body_from_cpt($uuid);
	}

	return $ret;
}

// Fetch a test based on UUID from cpantesters.org via HTTPS
function fetch_test_body_from_cpt($uuid) {
	$start = microtime(1);
	$url   = "http://www.cpantesters.org/cpan/report/$uuid";

	$ckey = "uuid:$uuid";
	$data = $GLOBALS['mc']->get($ckey);

	$curl_errno = 0;
	$http_code  = 0;

	if ($data) {
		$GLOBALS['FROM_CACHE'] = true;
		$body                  = $data;
	} else {
		$body = http_get_with_timeout($url, 2, $curl_errno, $http_code);
		$body = trim($body);

		if (strlen($body) > 256) {
			$GLOBALS['mc']->set($ckey, $body);
		}
	}

	$ret = $body;
	if (preg_match("/<pre>(.+?)<\/pre>/ms", $body, $m)) {
		$ret = trim($m[1]);
		$ret = strip_tags($ret);
    }

	$ms = intval((microtime(1) - $start) * 1000);

	if ($curl_errno == 28) {
		mplog("Timeout fetching $uuid from CPT after $ms ms");
	} elseif ($http_code != 200) {
		mplog("Non-OK HTTP code $http_code fetching $uuid from CPT");
	} else {
		mplog("Fetched $uuid from CPT in $ms ms");
		$ok = write_test_to_db($uuid, $ret);
	}

	return $ret;
}

// Fetch HTML from a remote URL
function http_get_with_timeout(string $url, int $timeout, &$curl_errno, &$http_code) {
	$ch = curl_init();

	curl_setopt_array($ch, [
		CURLOPT_URL            => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT        => $timeout, // Timeout after 2 seconds
		CURLOPT_FOLLOWLOCATION => true,     // Follow redirects
		CURLOPT_SSL_VERIFYPEER => true,     // Verify SSL certificates
		CURLOPT_SSL_VERIFYHOST => 2,        // Verify host
		CURLOPT_USERAGENT      => 'Perl Magpie Client'
	]);

	$response   = curl_exec($ch);
	$http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curl_errno = curl_errno($ch);

	if ($curl_errno) {
		$ret = "???";

		if (curl_errno($ch) == 28) {
			$ret = "Timeout fetching test from cpantesters.org";
		}

		if ($http_code >= 500 && $http_code < 600) {
			$ret = "cpantesters.org failed to fetch test";
		}

		//k(curl_error($ch), curl_errno($ch), $http_code);
		curl_close($ch);
		return $ret;
	}

	curl_close($ch);
	return $response;
}

// vim: tabstop=4 shiftwidth=4 noexpandtab autoindent softtabstop=4
