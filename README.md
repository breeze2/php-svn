# php-svn
svn in php

## install

```cmd
$ composer require breeze2/php-svn

```

## usage

```php
<?php

use BL\LibSvn\Exceptions\SvnException as SvnException;
use BL\LibSvn\SvnAdmin as SvnAdmin;
use BL\LibSvn\SvnClient as Svn;
use BL\LibSvn\SvnConfAuthz as SvnAuthz;

require 'path/to/vendor/autoload.php';

try {
    $parent_dir    = '/path/to/svn/repos';
    $svnclient_bin = '/path/to/svn/bin';
    $svnadmin_bin  = '/path/to/svnadmin/bin';

    $svnadmin = new SvnAdmin($svnadmin_bin);
    $svn      = new Svn($svnclient_bin);
    $authz    = new SvnAuthz();

    // create svn repo
    $repo_name = 'your_repo_name';
    $repo_path = $parent_dir . '/' . $repo_name;
    $result    = $svnadmin->create($repo_path);

    // make svn dir
    $svn->mkdir('/path/to/svn/repos/your_repo_name/dir');
    $svn->mkdir(array(
        '/path/to/svn/repos/your_repo_name/dir1',
        '/path/to/svn/repos/your_repo_name/dir2/dir3',
        '/path/to/svn/repos/your_repo_name/dir4/dir5/dir6',
    ));

    // list svn dir
    $list = $svn->ls('/path/to/svn/repos/your_repo_name/dir', array(
        'xml' => ''
    ));

    // get svn log
    $rev = '1';
    $log = $svn->log('/path/to/svn/repos/your_repo_name', array(
        'xml' => '',
        'revision' => $rev,
        'verbose' => ''
    ));

    // svn auth
    $authz_conf = '/path/to/svn/repos/your_repo_name/conf/authz';
    $authz->open($authz_conf);

    // create group
    $authz->createGroup('group_name');
    // delete group
    $authz->deleteGroup('group_name');
    // list groups
    $list = $authz->groups();
    // add member to group
    $authz->addUserToGroup('group_name', 'member_name');
    // remove member from group
    $authz->removeUserFromGroup('group_name', 'member_name');
    // list member of group
    $list = $authz->usersOfGroup('group_name');
    // create path
    $authz->addRepositoryPath('path_name');
    // delete path
    $authz->removeRepositoryPath('path_name');
    // list paths
    $list = $authz->repositoryPaths();
    // add member or group in path 
    $authz->addUserToRepositoryPath('path_name', 'member_name', 'rw');
    $authz->addUserToRepositoryPath('path_name', '@group_name', 'r');
    $authz->addUserToRepositoryPath('path_name', 'member_name1', '');
    // remove member or group in path
    $authz->removeUserFromRepositoryPath('path_name', 'member_name');
    $authz->removeUserFromRepositoryPath('path_name', '@group_name');
    // list member or group in path
    $list = $authz->membersOfRepositoryPath('path_name');
    // get permission of member or group in path
    $permission = $authz->permissionOfUserInRepositoryPath('member_name', 'path_name');
    $permission = $authz->permissionOfUserInRepositoryPath('@group_name', 'path_name');
    // change permission of member or group in path
    $authz->addUserToRepositoryPath('path_name', 'member_name', 'r');
    $authz->addUserToRepositoryPath('path_name', '@group_name', 'rw');

    // save the changes of svn auth
    $authz->save();

} catch (SvnException $e) {
    echo $e->getOutput();
}

```

## API

```php

SvnAdmin::create(string $svn_repo_path, [array $option]);
SvnClient::mkdir(string $svn_repo_path_dir, [array $option]);
SvnClient::mkdir(array $svn_repo_path_dirs, [array $option]);
SvnClient::ls(string $svn_repo_path_dir, [array $option]);
SvnClient::ls(array $svn_repo_path_dirs, [array $option]);
SvnClient::log(string $svn_repo_path, array $option);
...

```
