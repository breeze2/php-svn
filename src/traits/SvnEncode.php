<?php
namespace BL\LibSvn\Traits;

trait SvnEncode
{

    private $is_windows_server = false;

    public function encodeUrlPath($uri, $escape = true)
    {
        // Replace \ against /
        $uri = str_replace(DIRECTORY_SEPARATOR, '/', $uri);

        // Encode to UTF-8.
        $uri = $this->encodeString($uri, 'UTF-8');

        // Use per cent encoding for url path.
        // Skip encoding of 'svn+ssh://' part.
        $parts      = explode('/', $uri);
        $partsCount = count($parts);
        for ($i = 0; $i < $partsCount; $i++) {
            if ($i != 0 || $parts[$i] != 'svn+ssh:') {
                $parts[$i] = rawurlencode($parts[$i]);
            }
        }
        $uri = implode('/', $parts);
        $uri = str_replace('%3A', ':', $uri); // Subversion bug?

        // Quick fix for Windows share names.
        if ($this->is_windows_server) {
            // If the $uri now starts with '//', it points to a network share.
            // We must replace the first two '//' with '\\'.
            if (substr($uri, 0, 2) == '//') {
                $uri = '\\' . substr($uri, 2);
            }

            if (substr($uri, 0, 10) == 'file://///') {
                $uri = 'file:///\\\\' . substr($uri, 10);
            }
        }

        // Automatic prepend the 'file://' prefix (if nothing else is given).
        // 不影响中文目录
        $pattern = '/^[a-z0-9+]+:\/\//i';
        if (preg_match($pattern, $uri) == 0) {
            if (strpos($uri, '/') === 0) {
                $uri = 'file://' . $uri;
            } else {
                $uri = 'file:///' . $uri;
            }

        }
        $escape && ($uri = escapeshellarg($uri));

        return $uri;
    }

    /**
     * Prepares a path (URI) for command line usage. Does the following steps.
     *
     * <ul>
     *   <li>Replace backslash with slash (\ => /)</li>
     *   <li>Encode the input string <code>$uri</code> with UTF-8</li>
     *   <li><i>(Windows only)</i> Add one leading slash and two leading backslashes for network drive mappings.</li>
     *   <li>Add leading and trailing slashes.</li>
     * </ul>
     *
     * @param unknown_type $local_path
     */
    public function encodeLocalPath($local_path, $escape = true)
    {
        $local_path = str_replace(DIRECTORY_SEPARATOR, '/', $local_path);
        $local_path = $this->encodeString($local_path, 'UTF-8');

        // Quick fix for Windows share names.
        if ($this->is_windows_server) {
            // If the $uri now starts with '//', it points to a network share.
            // We must replace the first two '//' with '\\'.
            if (substr($local_path, 0, 2) == '//') {
                $local_path = '\\\\' . substr($local_path, 2);
            }
        }
        $escape && ($local_path = escapeshellarg($local_path));

        return $local_path;
    }

    public function encodeString($str, $dest_enc = 'UTF-8')
    {
        if (function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding')) {
            $str = mb_convert_encoding($str, $dest_enc, mb_detect_encoding($str));
        }
        return $str;
    }

}
