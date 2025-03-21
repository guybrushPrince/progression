<?php

include_once __DIR__ . '/../../php/engine/Engine.php';

// The main process model
$process01 = new CPProcessModel('Simple-Loop-Main-Process', __DIR__ . '/example06.bpmn');

// The nodes
$startEvent = new CPStartEvent('Event_0u7n7en');
$phpScriptTask01 = new CPPHPExecuteTask('Activity_1lzzoqv', '$this->export("x"); $x = intval(readline("Please enter the number of iterations of the loop: "));');
$phpScriptTask02 = new CPPHPExecuteTask('Activity_0wkx0an', 'echo "This is the " . $x . ". iteration." . PHP_EOL; $x--; $this->export("x");');
$interEvent01 = new CPIntermediateEvent('Gateway_0s2a23y-Throw', CPEventDirection::THROWING, CPEventType::MESSAGE);
$interEvent02 = new CPIntermediateEvent('Gateway_0s2a23y-Catch', CPEventDirection::CATCHING, CPEventType::MESSAGE);
$endEvent = new CPEndEvent('Event_1o2q6cj');

// The flows
$flow01 = new CPFlow('Flow_016ebht', $startEvent, $phpScriptTask01);
$flow02 = new CPFlow('Flow_016ebht-Flow_1nhzdxa', $phpScriptTask01, $phpScriptTask02);
$flow03 = new CPFlow('Flow_0fbphfj-Gateway_0s2a23y-Throw', $phpScriptTask02, $interEvent01);
$flow04 = new CPFlow('Gateway_0s2a23y-Throw-Gateway_0s2a23y-Catch', $interEvent01, $interEvent02);
$flow05 = new CPFlow('Flow_1bhtfyk-Loop-Quit', $interEvent02, $endEvent);

$process01->setElements([
    $startEvent,
    $phpScriptTask01,
    $phpScriptTask02,
    $interEvent01,
    $interEvent02,
    $endEvent
]);
$process01->setFlows([
    $flow01,
    $flow02,
    $flow03,
    $flow04,
    $flow05
]);

// The side-kick process model
$process02 = new CPProcessModel('Simple-Loop-Loop-Process', __DIR__ . '/example06.bpmn');

// The nodes
$startEvent_loop = new CPStartEvent('Event-Gateway_0s2a23y', CPEventType::MESSAGE);
$xorSplit_loop = new CPXORGateway('Gateway_0s2a23y');
$endEvent01_loop = new CPEndEvent('Gateway_0s2a23y-Loop-Exit', CPEventType::MESSAGE);
$phpScriptTask01_loop = new CPPHPExecuteTask('Activity_0wkx0an-Loop', 'echo "This is the " . $x . ". iteration." . PHP_EOL; $x--; $this->export("x");');
$endEvent02_loop = new CPEndEvent('Gateway_0s2a23y-Loop-Repeat', CPEventType::MESSAGE);

// The flows
$flow01_loop = new CPFlow('Flow_1nhzdxa', $startEvent_loop, $xorSplit_loop);
$flow02_loop = new CPFlow('Flow_1bhtfyk', $xorSplit_loop, $endEvent01_loop, null, new CPCondition('','{x} <= 0'));
$flow03_loop = new CPFlow('Flow_19pr4gc', $xorSplit_loop, $phpScriptTask01_loop);
$flow04_loop = new CPFlow('Flow_0fbphfj', $phpScriptTask01_loop, $endEvent02_loop);

$process02->setElements([
    $startEvent_loop,
    $xorSplit_loop,
    $endEvent01_loop,
    $phpScriptTask01_loop,
    $endEvent02_loop
]);
$process02->setFlows([
    $flow01_loop,
    $flow02_loop,
    $flow03_loop,
    $flow04_loop
]);

// Message flows
$interEvent01->addEventRecipient($startEvent_loop);
$endEvent01_loop->addEventRecipient($interEvent02);
$endEvent02_loop->addEventRecipient($startEvent_loop);

SimplePersistence::instance()->startTransaction();
$process01->createPermanentObject();
$process02->createPermanentObject();
SimplePersistence::instance()->endTransaction();

?>