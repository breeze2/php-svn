<?php
namespace BL\LibSvn;

use BL\LibSvn\Exceptions\SvnException;
use SimpleXMLElement;

class SvnClient extends SvnBase
{

    private $_svn = null;

    public function __construct($svn_binary)
    {
        parent::__construct();
        $this->_svn = $svn_binary;
    }

    public function mkdir($path, array $options = null)
    {
        if (empty($path)) {
            throw new SvnException('Empty path parameter for "svn mkdir" command.');
        }

        $dirname = $path;
        if (is_string($path)) {
            $dirname = $this->encodeUrlPath($path);
        } else if (is_array($path)) {
            for ($i = 0; $i < count($path); ++$i) {
                $path[$i] = $this->encodeUrlPath($path[$i]);
            }
            $dirname = implode(' ', $path);
        } else {
            throw new SvnException('Invalid path parameter');
        }

        $args = array(
            '--quiet'   => '',
            '--message' => 'Created folder.',
        );
        if ($options) {
            isset($options['message']) && $options['message'] && $args['--message']          = $options['message'];
            isset($options['parents']) && $args['--parents']                                 = '';
            isset($options['force-log']) && $args['--force-log']                             = '';
            isset($options['config-dir']) && $options['config-dir'] && $args['--config-dir'] = $options['config-dir'];
        }

        $command = self::makeCommand($this->_svn, 'mkdir ' . $dirname, $args);

        $output = null;
        $code   = 0;

        self::executeCommand($command, $output, $code);

        return true;
    }

    public function log($path, array $options = null)
    {
        if (empty($path)) {
            throw new SvnException('Empty path parameter for "svn log" command.');
        }

        $dirname = $path;
        if (is_string($path)) {
            $dirname = $this->encodeUrlPath($path);
        } else if (is_array($path)) {
            for ($i = 0; $i < count($path); ++$i) {
                $path[$i] = $this->encodeUrlPath($path[$i]);
            }
            $dirname = implode(' ', $path);
        } else {
            throw new SvnException('Invalid path parameter');
        }

        $args = array();
        if ($options) {
            isset($options['xml']) && $args['--xml']                                         = '';
            isset($options['verbose']) && $args['--verbose']                                 = '';
            isset($options['limit']) && $args['--limit']                                     = $options['limit'];
            isset($options['revision']) && $options['revision'] && $args['--revision']       = $options['revision'];
            isset($options['config-dir']) && $options['config-dir'] && $args['--config-dir'] = $options['config-dir'];
        }

        $command = self::makeCommand($this->_svn, 'log ' . $dirname, $args);

        $output = null;
        $code   = 0;

        self::executeCommand($command, $output, $code);

        if (isset($args['--xml'])) {
            // return simplexml_load_string(utf8_encode(trim(implode(' ', $output))));
            $obj = new SimpleXMLElement(trim(implode(' ', $output)));
            return $this->parseLogXML($obj);
        } else {
            return $output;
        }
    }

    public function ls($path, array $options = null)
    {
        if (empty($path)) {
            throw new SvnException('Empty path parameter for "svn ls" command.');
        }

        $dirname = $path;
        if (is_string($path)) {
            $dirname = $this->encodeUrlPath($path);
        } else if (is_array($path)) {
            for ($i = 0; $i < count($path); ++$i) {
                $path[$i] = $this->encodeUrlPath($path[$i]);
            }
            $dirname = implode(' ', $path);
        } else {
            throw new SvnException('Invalid path parameter');
        }

        $args = array();
        if ($options) {
            isset($options['xml']) && $args['--xml']                                         = '';
            isset($options['verbose']) && $args['--verbose']                                 = '';
            isset($options['recursive']) && $args['--recursive']                             = '';
            isset($options['revision']) && $options['revision'] && $args['--revision']       = $options['revision'];
            isset($options['config-dir']) && $options['config-dir'] && $args['--config-dir'] = $options['config-dir'];
        }

        $command = self::makeCommand($this->_svn, 'ls ' . $dirname, $args);

        $output = null;
        $code   = 0;

        self::executeCommand($command, $output, $code);

        if (isset($args['--xml'])) {
            // return simplexml_load_string(utf8_encode(trim(implode(' ', $output))));
            $obj = new SimpleXMLElement(trim(implode(' ', $output)));
            return $this->parseLsXML($obj);
        } else {
            return $output;
        }
    }

    protected function parseLsXML($obj)
    {
        $arr = array(
            'lists' => array(),
        );

        $lists    = $obj->list;
        $list_cnt = $lists->count();
        for ($i = 0; $i < $list_cnt; $i++) {
            $list     = $lists[$i];
            $arr_list = array(
                'path'    => (string) $list['path'],
                'entries' => array(),
            );
            $entries   = $list->entry;
            $entry_cnt = $entries->count();
            for ($j = 0; $j < $entry_cnt; $j++) {
                $entry     = $entries[$j];
                $arr_entry = array(
                    'kind'   => (string) $entry['kind'],
                    'name'   => (string) $entry->name,
                    'commit' => array(
                        'revision' => (string) $entry->commit['revision'],
                        'author'   => (string) $entry->commit->author,
                        'date'     => (string) $entry->commit->date,
                    ),
                );
                $arr_list['entries'][] = $arr_entry;
            }
            $arr['lists'][] = $arr_list;
        }
        return $arr;
    }

    protected function parseLogXML($obj)
    {
        $arr = array(
            'logentries' => array(),
        );

        $logentries   = $obj->logentry;
        $logentry_cnt = $logentries->count();
        for ($i = 0; $i < $logentry_cnt; $i++) {
            $logentry     = $logentries[$i];
            $arr_logentry = array(
                'revision' => (int) $logentry['revision'],
                'author'   => (string) $logentry->author,
                'date'     => (string) $logentry->date,
                'msg'      => (string) $logentry->msg,
                'paths'    => array(),
            );
            $paths    = $logentry->paths->path;
            if($paths) {
                $path_cnt = $paths->count();
                for ($j = 0; $j < $path_cnt; $j++) {
                    $path     = $paths[$j];
                    $arr_path = array(
                        'kind'      => (string) $path['kind'],
                        'action'    => (string) $path['action'],
                        'prop-mods' => (string) $path['prop-mods'],
                        'text-mods' => (string) $path['text-mods'],
                        'content'   => (string) $path,
                    );
                    $arr_logentry['paths'][] = $arr_path;
                }
            }
            
            $arr['logentries'][] = $arr_logentry;
        }
        return $arr;
    }
}
