<?php

/**
 * Class EchoLogger.
 *
 * A simple logger printing logging information to the console (by echoing stuff).
 *
 * @package progression
 * @subpackge php/tools
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 */
class EchoLogger extends CPLogger {

    /**
     * The name of the logger.
     * @var string
     */
    private string $name;

    /**
     * The log level.
     * @var int
     */
    private int $logLevel;

    /**
     * The echo logger instance to use.
     * @var EchoLogger|null
     */
    private static ?EchoLogger $inst = null;

    /**
     * Constructor.
     * @param string $name The name of the logger.
     * @param int $logLevel The logging level.
     */
    private function __construct(string $name, int $logLevel) {
        $this->name = $name;
        $this->logLevel = $logLevel;
    }

    /**
     * @inheritDoc
     */
    public static function instance(string $name = 'default', int $logLevel = self::LEVEL_ALL) : CPLogger {
        if (!self::$inst) {
            self::$inst = new EchoLogger($name, $logLevel);
        }
        return self::$inst;
    }

    /**
     * @inheritDoc
     */
    public function log(...$messages) : void {
        if ($this->logLevel <= self::LEVEL_INFO) {
            array_unshift($messages, self::LEVEL_INFO);
            $log = call_user_func_array([$this, 'printIt'], $messages);
            echo $this->name . ' INFO: ' . $log . PHP_EOL;
        }
    }

    /**
     * @inheritDoc
     */
    public function debug(...$messages) : void {
        if ($this->logLevel <= self::LEVEL_DEBUG) {
            array_unshift($messages, self::LEVEL_DEBUG);
            $log = call_user_func_array([$this, 'printIt'], $messages);
            echo $this->name . ' DEBUG: ' . $log . PHP_EOL;
        }
    }

    /**
     * @inheritDoc
     */
    public function error(...$messages) : void {
        if ($this->logLevel <= self::LEVEL_ERROR) {
            array_unshift($messages, self::LEVEL_ERROR);
            $log = call_user_func_array([$this, 'printIt'], $messages);
            echo $this->name . ' ERROR: ' . $log . PHP_EOL;
        }
    }

    /**
     * Creates a string out of the messages.
     * @param mixed ...$messages The messages.
     * @return string
     */
    private function printIt(...$messages) : string {
        return implode(' ', array_map(function($message) {
            if (is_object($message) || is_array($message) || is_null($message)) {
                if ($message === null) return "<NULL>";
                if (is_array($message) && count($message) === 0) return "<EMPTY_ARRAY>";
                ob_start();
                var_dump($message);
                $message = ob_get_clean();
                return $message;
            }
            return $message . ' (' . gettype($message) . ')';
        }, $messages));
    }
}
?>