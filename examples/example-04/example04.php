<?php

include_once __DIR__ . '/../../php/engine/Engine.php';

// The process model
$process = new CPProcessModel('Exclusive-PHP-Scripts-Process', __DIR__ . '/example04.bpmn');

// The nodes
$startEvent = new CPStartEvent('Event_011ii0y');
$phpScriptTask01 = new CPPHPExecuteTask('Activity_1uvnau8', '$x = intval(readline("Please enter an integer: ")); $this->export("x");');
$xorSplit = new CPXORGateway('Gateway_009hgqn');
$phpScriptTask02 = new CPPHPExecuteTask('Activity_1xos86o', 'echo $x . " is greater than 5" . PHP_EOL;');
$phpScriptTask03 = new CPPHPExecuteTask('Activity_1fqu72k', 'echo $x . " is lower or equal to 5" . PHP_EOL;');
$xorJoin = new CPXORGateway('Gateway_0442vim');
$endEvent = new CPEndEvent('Event_1gyzhrw');

// The flows
$flow01 = new CPFlow('Flow_0azwytj', $startEvent, $phpScriptTask01);
$flow02 = new CPFlow('Flow_135rcsv', $phpScriptTask01, $xorSplit);
$flow03 = new CPFlow('Flow_1uqpz76', $xorSplit, $phpScriptTask02, null, new CPCondition('Flow_1uqpz76_condition','{x} > 5'));
$flow04 = new CPFlow('Flow_12aen40', $xorSplit, $phpScriptTask03);
$flow05 = new CPFlow('Flow_0y99vio', $phpScriptTask02, $xorJoin);
$flow06 = new CPFlow('Flow_1rxfnjo', $phpScriptTask03, $xorJoin);
$flow07 = new CPFlow('Flow_0y7z08e', $xorJoin, $endEvent);

$process->setElements([
    $startEvent,
    $phpScriptTask01,
    $xorSplit,
    $phpScriptTask02,
    $phpScriptTask03,
    $xorJoin,
    $endEvent
]);
$process->setFlows([
    $flow01,
    $flow02,
    $flow03,
    $flow04,
    $flow05,
    $flow06,
    $flow07
]);

SimplePersistence::instance()->startTransaction();
$process->createPermanentObject();
SimplePersistence::instance()->endTransaction();

?>