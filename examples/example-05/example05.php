<?php

include_once __DIR__ . '/../../php/engine/Engine.php';

// The main process model
$process01 = new CPProcessModel('Message-Send-Main-Process', __DIR__ . '/example05.bpmn');

// The nodes
$startEvent = new CPStartEvent('Event_1qeumty');
$phpScriptTask01 = new CPPHPExecuteTask('Activity_12dhe3q', 'echo "I call my sidekick to ask for x" . PHP_EOL;');
$interEvent01 = new CPIntermediateEvent('Event_1xmecnx', CPEventDirection::THROWING, CPEventType::MESSAGE);
$interEvent02 = new CPIntermediateEvent('Event_0bhlt6v', CPEventDirection::CATCHING, CPEventType::MESSAGE);
$xorSplit = new CPXORGateway('Gateway_1195arz');
$phpScriptTask02 = new CPPHPExecuteTask('Activity_0w4spfn', 'echo $context["x"] . " is greater than 5" . PHP_EOL;');
$phpScriptTask03 = new CPPHPExecuteTask('Activity_0h2vru0', 'echo $context["x"] . " is lower or equal to 5" . PHP_EOL;');
$xorJoin = new CPXORGateway('Gateway_07l7i5f');
$endEvent = new CPEndEvent('Event_1679jyw');

// The flows
$flow01 = new CPFlow('Flow_1hw0nt7', $startEvent, $phpScriptTask01);
$flow02 = new CPFlow('Flow_0l9dpc6', $phpScriptTask01, $interEvent01);
$flow03 = new CPFlow('Flow_17i9k8p', $interEvent01, $interEvent02);
$flow04 = new CPFlow('Flow_1xef69p', $interEvent02, $xorSplit);
$flow05 = new CPFlow('Flow_0js1dzs', $xorSplit, $phpScriptTask02, null, new CPCondition('Flow_0js1dzs_condition','{x} > 5'));
$flow06 = new CPFlow('Flow_0b0erdp', $xorSplit, $phpScriptTask03);
$flow07 = new CPFlow('Flow_1n098l1', $phpScriptTask02, $xorJoin);
$flow08 = new CPFlow('Flow_1iyargp', $phpScriptTask03, $xorJoin);
$flow09 = new CPFlow('Flow_1se89cl', $xorJoin, $endEvent);

$process01->setElements([
    $startEvent,
    $phpScriptTask01,
    $interEvent01,
    $interEvent02,
    $xorSplit,
    $phpScriptTask02,
    $phpScriptTask03,
    $xorJoin,
    $endEvent
]);
$process01->setFlows([
    $flow01,
    $flow02,
    $flow03,
    $flow04,
    $flow05,
    $flow06,
    $flow07,
    $flow08,
    $flow09
]);

// The side-kick process model
$process02 = new CPProcessModel('Message-Send-Sidekick-Process', __DIR__ . '/example05.bpmn');

// The nodes
$startEvent_sk = new CPStartEvent('Event_0ia7wo0', CPEventType::MESSAGE);
$phpScriptTask01_sk = new CPPHPExecuteTask('Activity_1tw3l6v', '$context["x"] = intval(readline("Please enter an integer: "));');
$endEvent_sk = new CPEndEvent('Event_01vryov', CPEventType::MESSAGE);

// The flows
$flow01_sk = new CPFlow('Flow_1vc2ewa', $startEvent_sk, $phpScriptTask01_sk);
$flow02_sk = new CPFlow('Flow_0akjlmt', $phpScriptTask01_sk, $endEvent_sk);

$process02->setElements([
    $startEvent_sk,
    $phpScriptTask01_sk,
    $endEvent_sk
]);
$process02->setFlows([
    $flow01_sk,
    $flow02_sk
]);

// Message flows
$interEvent01->addEventRecipient($startEvent_sk);
$endEvent_sk->addEventRecipient($interEvent02);

SimplePersistence::instance()->startTransaction();
$process01->createPermanentObject();
$process02->createPermanentObject();
SimplePersistence::instance()->endTransaction();

?>