<?php

$BASE_DIR = realpath(dirname(__FILE__) . "/../");

// This is the zstandard dictionary used to compress new test results
// Note: it must be in the `dict_info` table in the DB for decompression to work
$ZSTD_DICT = "$BASE_DIR/include/zstd-dict/magpie-dict-2025";

////////////////////////////////////////////////////////////////////////////////
require("$BASE_DIR/include/krumo/class.krumo.php");
////////////////////////////////////////////////////////////////////////////////
require("$BASE_DIR/include/dbquery/db_query.class.php");
////////////////////////////////////////////////////////////////////////////////
require("$BASE_DIR/include/sluz/sluz.class.php");
$s   = new sluz();
////////////////////////////////////////////////////////////////////////////////
require("$BASE_DIR/include/global.inc.php");
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
