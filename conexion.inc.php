<?php

$GLOBALS['redis'] = new Redis();
$GLOBALS['redis']->connect($GLOBALS['CONF']['redis_host'], $GLOBALS['CONF']['redis_port']);

?>
