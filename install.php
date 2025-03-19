<?php
/**
 * Installs Progression.
 *
 * Run this by CLI: php install.php
 *
 * @package progression
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 * @version 1.0.0
 */

include_once __DIR__ . '/php/progloader.php';

if (class_exists(APersistence::class, true)) {
    (new Installer())->install();

    exec('php initialize.php');
}

?>