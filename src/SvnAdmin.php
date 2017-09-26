<?php
namespace BL\LibSvn;

use BL\LibSvn\Exceptions\SvnException;

class SvnAdmin extends SvnBase
{

    private $_svnadmin = null;

    public function __construct($svnadmin_binary)
    {
        parent::__construct();
        $this->_svnadmin = $svnadmin_binary;
    }

    public function create($path, array $options = null)
    {
        if (empty($path)) {
            throw new SvnException('Empty path parameter for "svnadmin create" command.');
        }

        $pattern   = '/^([a-z0-9\_\-.]+)$/i';
        $repo_name = basename($path);

        if (!preg_match($pattern, $repo_name)) {
            throw new SvnException('Invalid repository name: ' . $repo_name . '');
        }

        $args = array(
            '--fs-type' => 'fsfs',
        );
        if ($options) {
            isset($options['bdb-txn-nosync']) && $args['--bdb-txn-nosync']                   = '';
            isset($options['bdb-log-keep']) && $args['--bdb-log-keep']                       = '';
            isset($options['config-dir']) && $options['config-dir'] && $args['--config-dir'] = $options['config-dir'];
            isset($options['fs-type']) && $options['fs-type'] && $args['--fs-type']          = $options['fs-type'];
        }
        $cmd = self::makeCommand($this->_svnadmin, "create " . $this->encodeLocalPath($path), $args);

        $output = null;
        $code   = 0;
        // var_dump($cmd);die();
        self::executeCommand($cmd, $output, $code);

        return true;
    }

}
