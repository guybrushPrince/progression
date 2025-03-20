<?php

include_once __DIR__ . '/../../php/engine/Engine.php';

// The process model
$process = new CPProcessModel('Parallel-PHP-Scripts-Process', __DIR__ . '/example03.bpmn');

// The nodes
$startEvent = new CPStartEvent('Event_1celxvr');
$andSplit = new CPANDGateway('Gateway_0mafsbm');
$phpScriptTask01 = new CPPHPExecuteTask('Activity_126nptx', 'echo "Hello World 1" . PHP_EOL;');
$phpScriptTask02 = new CPPHPExecuteTask('Activity_1ilnpq8', 'echo "Hello World 2" . PHP_EOL;');
$andJoin = new CPANDGateway('Gateway_1f1ukq1');
$endEvent = new CPEndEvent('Event_0mq7wco');

// The flows
$flow01 = new CPFlow('Flow_1id4tmd', $startEvent, $andSplit);
$flow02 = new CPFlow('Flow_11fl4pf', $andSplit, $phpScriptTask01);
$flow03 = new CPFlow('Flow_0hjv8ul', $andSplit, $phpScriptTask02);
$flow04 = new CPFlow('Flow_1y3kw7y', $phpScriptTask01, $andJoin);
$flow05 = new CPFlow('Flow_0q0g8fh', $phpScriptTask02, $andJoin);
$flow06 = new CPFlow('Flow_1lszi76', $andJoin, $endEvent);

$process->setElements([
    $startEvent,
    $andSplit,
    $phpScriptTask01,
    $phpScriptTask02,
    $andJoin,
    $endEvent
]);
$process->setFlows([
    $flow01,
    $flow02,
    $flow03,
    $flow04,
    $flow05,
    $flow06
]);

SimplePersistence::instance()->startTransaction();
$process->createPermanentObject();
SimplePersistence::instance()->endTransaction();

?>