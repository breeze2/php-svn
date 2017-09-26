<?php
use BL\LibSvn\Exceptions\SvnException as SvnException;
use BL\LibSvn\SvnAdmin as SvnAdmin;
use BL\LibSvn\SvnClient as Svn;
use BL\LibSvn\SvnConfAuthz as SvnAuthz;

require '../vendor/autoload.php';

$config = require __DIR__ . '/config.php';

var_dump(__DIR__);
$svnclient_bin = $config['SVN']['SVNCLIENT'];
$svn = new Svn($svnclient_bin);
