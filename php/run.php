<?php

/**
 * Runs the engine (performs a *tick*).
 *
 * @package progression
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 * @version 1.0.0
 */

if (!file_exists(__DIR__ . '/../../cliff/php/loader.php')) {
    include_once __DIR__ . '/progloader.php';
} else {
    include_once __DIR__ . '/../../cliff/php/loader.php';
}
include_once __DIR__ . '/permanent/SimplePersistence.php';
include_once __DIR__ . '/engine/Engine.php';

Engine::instance()->tick();

?>