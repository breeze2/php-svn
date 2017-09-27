<?php
use BL\LibSvn\Exceptions\SvnException as SvnException;
use BL\LibSvn\SvnAdmin as SvnAdmin;
use BL\LibSvn\SvnClient as Svn;
use BL\LibSvn\SvnConfAuthz as SvnAuthz;

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/config.php';

var_dump(__DIR__);
$parent_dir    = $config['SVN']['PARENT'];
$svnclient_bin = $config['SVN']['SVNCLIENT'];
$svnadmin_bin  = $config['SVN']['SVNADMIN'];

$svn = $svnclient = new Svn($svnclient_bin);
$svnadmin = new SvnAdmin($svnadmin_bin);

$name = 'test123';
$parent_dir = __DIR__;
$repo = $parent_dir . '/' . $name;
$result = $svnadmin->create($repo);
