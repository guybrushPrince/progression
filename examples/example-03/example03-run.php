<?php
include_once __DIR__ . '/../../php/engine/Engine.php';

$persistence = SimplePersistence::instance();

$processModel = CPProcessModel::getPermanentObject('Parallel-PHP-Scripts-Process', 'CPProcessModel');
$systemProcessModel = CPProcessModel::getPermanentObject('__system', 'CPProcessModel');
$systemProcessInstance = ProcessInstance::getPermanentObjectsWhere('processModel', $systemProcessModel, ProcessInstance::class);

if (count($systemProcessInstance) >= 1) {
    $systemProcessInstance = array_shift($systemProcessInstance);
}

$instance = Engine::instance()->instantiate($processModel, null, null);

echo 'Created process instance: ' . $instance->getPermanentId() . PHP_EOL
?>