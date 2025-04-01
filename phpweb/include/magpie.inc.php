<?php

require("include/krumo/class.krumo.php");
require("include/dbquery/db_query.class.php");
require("include/sluz/sluz.class.php");
require("include/global.inc.php");

$dbq = db_init();
$s   = new sluz();

$mc = new Memcached();
$mc->addServer('127.0.0.1', 11211);
$mc->setOption(Memcached::OPT_COMPRESSION, true);

# Syslog
openlog("MagpieWeb", LOG_PID | LOG_PERROR, LOG_LOCAL7);
