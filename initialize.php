<?php

/**
 * Initializes Progression by creating the system process.
 * @package progression
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 * @version 1.0.0
 */

include_once __DIR__ . '/php/progloader.php';

(new Installer())->createSystemProcess();

?>