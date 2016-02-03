<?php
require_once('access.php');
require_once('dal.php');
require_once('utils.php');
require_once('email.php');

$dal = new DAL($opts['hn'], $opts['db'], $opts['un'], $opts['pw']);

$dal->logout();

header('Location: .');
