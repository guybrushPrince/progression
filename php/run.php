<?php

include_once __DIR__ . '/../../cliff/php/Persistence.php';
include_once __DIR__ . '/permanent/SimplePersistence.php';
include_once __DIR__ . '/engine/Engine.php';

Persistence::instance()->initiateDefault();

Engine::instance()->tick();
$instances = ProcessInstance::getAll();
foreach ($instances as $instance) {
    echo $instance->getProcessModel()->asDotGraph(['id' => $instance->getPermanentId(), 'states' => $instance->getInstanceStates()]);
}

?>