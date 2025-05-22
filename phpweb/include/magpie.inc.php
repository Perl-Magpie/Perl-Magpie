<?php

$base_dir = realpath(dirname(__FILE__) . "/../");

// This is the zstandard dictionary used to compress new test results
// Note: it must be in the `dict_info` table in the DB for decompression to work
$ZSTD_DICT = "$base_dir/include/zstd-dict/magpie-dict-2025";

////////////////////////////////////////////////////////////////////////////////
require("$base_dir/include/krumo/class.krumo.php");
////////////////////////////////////////////////////////////////////////////////
require("$base_dir/include/dbquery/db_query.class.php");
////////////////////////////////////////////////////////////////////////////////
require("$base_dir/include/sluz/sluz.class.php");
$s   = new sluz();
////////////////////////////////////////////////////////////////////////////////
require("$base_dir/include/global.inc.php");
$dbq = db_init();
////////////////////////////////////////////////////////////////////////////////
$mc  = new Memcached();
$mc->addServer('127.0.0.1', 11211);

// Compression is off for now. This was slowing things down one some larger
// data set()'s
$mc->setOption(Memcached::OPT_COMPRESSION, false);
////////////////////////////////////////////////////////////////////////////////

# Syslog
openlog("MagpieWeb", LOG_PID | LOG_PERROR, LOG_LOCAL7);
