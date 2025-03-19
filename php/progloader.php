<?php
if (!headers_sent()) {
    header("Content-Type: text/html; charset=utf-8");
}
/**
 * This script adds functions <i>array_key_first</i>, <i>starts_with_upper</i>, and <i>get_magic_quotes_gpc</i> if they
 * are not available since the PHP version is too old.
 * The main task of this file is to search for all classes and make them automatically loadable.
 *
 * @package progression
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 * @version 1.0.0
 */
if (!function_exists('array_key_first')) {
    /**
     * Get the first key of an array.
     * @param array $arr The array.
     * @return int|string|null
     */
    function array_key_first(array $arr) : string|int|null {
        foreach($arr as $key => $unused) return $key;
        return null;
    }
}
if (!function_exists("get_magic_quotes_gpc")) {
    function get_magic_quotes_gpc() : bool {
        return false;
    }
}
if (!function_exists('starts_with_upper')) {
    function starts_with_upper(string $str) : bool {
        $chr = mb_substr ($str, 0, 1, "UTF-8");
        return mb_strtolower($chr, "UTF-8") != $chr;
    }
}

/**
 * Is called when a class cannot be found.
 */
spl_autoload_register(function ($class) {
    if (!array_key_exists("files", $GLOBALS)) {
        $files = array();
        $GLOBALS["files"] = &$files;
    } else {
        $files = &$GLOBALS["files"];
    }
    if (count($files) === 0) {
        determinePersistence(__DIR__, $files);
        determinePersistence(__DIR__ . '/../graph', $files);
        if (is_dir(__DIR__ . '/../traits')) {
            determinePersistence(__DIR__ . '/../traits', $files);
        }
    }
    if (array_key_exists($class . ".php", $files)) include_once $files[$class . ".php"];
});

/**
 * Determine all files starting at a folder.
 * @param string $folder The folder.
 * @param array $files The files.
 */
function determinePersistence(string $folder, array &$files) {
    $folder = realpath($folder);

    if ($folder) {
        $dir = new RecursiveDirectoryIterator($folder);
        $iterator = new RecursiveIteratorIterator($dir);
        $regex = new RegexIterator($iterator, "/^.+\.php$/i", RecursiveRegexIterator::GET_MATCH);
        $regex->next();
        while ($regex->valid()) {
            $file = $regex->current();
            $files[basename($file[0])] = $file[0];
            $regex->next();
        }
    }
}
?>
