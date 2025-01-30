<?php

/**
 * Abstract class CPLogger.
 *
 * @package progression
 * @subpackge php/tools
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 */
abstract class CPLogger {

    const LEVEL_ALL   = 1;
    const LEVEL_DEBUG = 2;
    const LEVEL_INFO  = 3;

    /**
     * Get or create an instance of the logger.
     * @param string $name The name of the logger.
     * @param int $logLevel The log level.
     * @return CPLogger
     */
    public abstract static function instance(string $name = 'default', int $logLevel = self::LEVEL_ALL) : CPLogger;

    /**
     * Log some information.
     * @param mixed ...$messages The messages to log.
     * @return void
     */
    public abstract function log(...$messages) : void;

    /**
     * Debug some information.
     * @param mixed ...$messages The messages to debug.
     * @return void
     */
    public abstract function debug(...$messages) : void;

    /**
     * Provides the slugged version of a string.
     * @param string $z The string to slug.
     * @return string
     */
    public static function slug(string $z) : string {
        $z = strtolower($z);
        $z = preg_replace('/[^a-z0-9 -]+/', '', $z);
        $z = str_replace(' ', '', $z);
        $z = str_replace('-', '', $z);
        return trim($z, '-');
    }
}
?>