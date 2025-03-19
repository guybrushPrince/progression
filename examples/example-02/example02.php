<?php

include_once __DIR__ . '/../../php/engine/Engine.php';

// The process model
$process = new CPProcessModel('Execute-PHP-Script-Process', __DIR__ . '/example02.bpmn');

// The nodes
$startEvent = new CPStartEvent('StartEvent_11lydgf');
$phpScriptTask = new CPPHPExecuteTask('Activity_187itr1', 'echo "Hello World" . PHP_EOL;');
$endEvent = new CPEndEvent('Event_1rxxtu2');

// The flows
$flow01 = new CPFlow('Flow_0oqnyrs', $startEvent, $phpScriptTask);
$flow02 = new CPFlow('Flow_12i95iv', $phpScriptTask, $endEvent);

$process->setElements([
    $startEvent,
    $phpScriptTask,
    $endEvent
]);
$process->setFlows([
    $flow01,
    $flow02
]);

SimplePersistence::instance()->startTransaction();
$process->createPermanentObject();
SimplePersistence::instance()->endTransaction();

?>