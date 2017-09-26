<?php
namespace BL\LibSvn;

use BL\LibSvn\Exceptions\SvnCommandExecutionException;
use BL\LibSvn\Traits\SvnEncode;

class SvnBase
{
    use SvnEncode;

    public function __construct()
    {
        $soft = PHP_OS;
        $soft = strtoupper($soft);

        if (stripos($soft, 'WIN') !== false) {
            $this->is_windows_server = true;
        }

    }

    public static function makeCommand($binary, $action, $options)
    {
        $command = '"' . $binary . '" ' . $action;
        if (!empty($options)) {
            foreach ($options as $key => &$val) {
                $command .= ' ' . escapeshellarg($key);
                if (!empty($val)) {
                    $command .= ' ' . escapeshellarg($val);
                }
                // old line: $command.= ' '.($val);
            }
        }
        return escapeshellcmd($command);

    }

    public static function executeCommand($command, &$output, &$code)
    {
        if (true) {
            printf("%s\n",$command);
        }
        $result = exec($command, $output, $code);
        if ($code != 0) {
            throw new SvnCommandExecutionException(json_encode(array('command'=> $command, 'return'=> $code, 'output'=> $output)));
        }
        return $result;
    }

    public static function openProcess($command, $descriptorspec, &$pipes, $cwd = null, array $env = null, array $other_options = null)
    {
        if (true) {
            printf("%s\n",$command);
        }
        $resource = proc_open($command, $descriptorspec, $pipes, $cwd, $env, $other_options);
        if (!is_resource($resource)) {
            throw new SvnCommandExecutionException(json_encode(array('command'=> $command)));
        }

        $error_handle = $pipes[2];
        $error_message = '';
        while (!feof($error_handle)) {
            $error_message.= fgets($error_handle);
        }
        if (!empty($error_message)) {
            throw new SvnCommandExecutionException(json_encode(array('output'=> $output)));
        }
        return $resource;
    }

    public static function closeProcess($resource)
    {
        return proc_close($resource);
    }

}
