<?php

if (is_dir(__DIR__ . '/../../../cliff')) {

    if (!isset($_SESSION)) {
        $_SESSION = [

        ];
    }
    if (!isset($_SERVER)) {
        $_SERVER = [];
    }
    if (!array_key_exists('HTTP_HOST', $_SERVER)) {
        $_SERVER['HTTP_HOST'] = '';
    }
    if (!array_key_exists('REQUEST_URI', $_SERVER)) {
        $_SERVER['REQUEST_URI'] = '';
    }

    include_once __DIR__ . '/../../../cliff/php/Persistence.php';
    include_once __DIR__ . '/../../../vendor/autoload.php';

    /**
     * Class SimpleContextBuilder.
     *
     * A Coast-related implementation of {@link AContextBuilder}.
     *
     * @package progression
     * @subpackge php/context
     *
     * @version 1.0.0
     * @author Dr. Dipl.-Inf. Thomas M. Prinz
     */
    class SimpleContextBuilder extends AContextBuilder {

        /**
         * @inheritDoc
         */
        public static function loadContext() : void {}
    }
} else {
    if (!class_exists('SimpleContextBuilder')) {
        /**
         * Class SimpleContextBuilder.
         *
         * A default implementation of {@link AContextBuilder}.
         *
         * @package progression
         * @subpackge php/context
         *
         * @version 1.0.0
         * @author Dr. Dipl.-Inf. Thomas M. Prinz
         */
        class SimpleContextBuilder extends AContextBuilder {

            /**
             * @inheritDoc
             */
            public static function loadContext() : void { }
        }
    }
}
?>