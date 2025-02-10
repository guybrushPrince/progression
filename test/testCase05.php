<?php

include_once __DIR__ . '/../../cliff/php/Persistence.php';
include_once __DIR__ . '/../php/permanent/SimplePersistence.php';

Engine::instance(null, CPLogger::LEVEL_DEBUG);

foreach (Token::getAll() as $token) $token->delete();
foreach (Incident::getAll() as $incident) $incident->delete();
foreach (LocalState::getAll() as $localState) $localState->delete();
foreach (ProcessInstance::getAll() as $processInstance) $processInstance->delete();

$processModel1 = CPProcessModel::getPermanentObject('testCase05', 'CPProcessModel');
$processModel2 = CPProcessModel::getPermanentObject('testCase05-sidekick', 'CPProcessModel');
$systemEvent = CPSystemEvent::getPermanentObject('__system_general_start_event', 'CPSystemEvent');
$systemProcessModel = CPProcessModel::getPermanentObject('__system', 'CPProcessModel');
$systemProcessInstance = ProcessInstance::getPermanentObjectsWhere('processModel', $systemProcessModel, ProcessInstance::class);

if (!$processModel1 || !$processModel2) {
    $persistence = SimplePersistence::instance();

    $processModel1 = new CPProcessModel('testCase05');
    $startEvent = new CPStartEvent('testCase05-startEvent');
    $xorSplit = new CPXORGateway('testCase05-xorSplit');
    $inThrowEvent = new CPIntermediateEvent('testCase05-throw-01', CPEventDirection::THROWING, CPEventType::SIGNAL);
    $phpTask1 = new CPPHPExecuteTask('testCase05-php-01', 'echo "Hello World 1\n";' . PHP_EOL);
    $inCatchEvent = new CPIntermediateEvent('testCase05-catch-01', CPEventDirection::CATCHING, CPEventType::SIGNAL);
    $phpTask2 = new CPPHPExecuteTask('testCase05-php-02', 'echo "Hello World 2\n";' . PHP_EOL);
    $xorJoin = new CPANDGateway('testCase05-xorJoin');
    $endEvent = new CPEndEvent('testCase05-endEvent');
    $flow01 = new CPFlow('testCase05-F01', $startEvent, $xorSplit);
    $flow02 = new CPFlow('testCase05-F02', $xorSplit, $inThrowEvent);
    $condition = new CPCondition('textCase05-F02-cond', '{x} > 5');
    $flow02->setUseCondition($condition);
    $flow03 = new CPFlow('testCase05-F03', $xorSplit, $phpTask2);
    $flow04 = new CPFlow('testCase05-F04', $inCatchEvent, $xorJoin);
    $flow05 = new CPFlow('testCase05-F05', $phpTask2, $xorJoin);
    $flow06 = new CPFlow('testCase05-F06', $xorJoin, $endEvent);
    $flow07 = new CPFlow('testCase05-F07', $inThrowEvent, $phpTask1);
    $flow08 = new CPFlow('testCase05-F08', $phpTask1, $inCatchEvent);

    $processModel1->setElements([$startEvent, $xorSplit, $inThrowEvent, $phpTask1, $inCatchEvent, $phpTask2, $xorJoin, $endEvent]);
    $processModel1->setFlows([$flow01, $flow02, $flow03, $flow04, $flow05, $flow06, $flow07, $flow08]);

    $processModel2 = new CPProcessModel('testCase05-sidekick');
    $startEvent2 = new CPStartEvent('testCase05-sidekick-startEvent', CPEventType::SIGNAL);
    $inThrowEvent->addEventRecipient($startEvent2);
    $phpTask12 = new CPPHPExecuteTask('testCase05-sidekick-php-01', 'echo "Incoming: " . $message . "\n";' . PHP_EOL);
    $endEvent2 = new CPEndEvent('testCase05-sidekick-endEvent');
    $endEvent2->addEventRecipient($inCatchEvent);
    $flow201 = new CPFlow('testCase05-sidekick-F01', $startEvent2, $phpTask12);
    $flow202 = new CPFlow('testCase05-sidekick-F02', $phpTask12, $endEvent2);

    $processModel2->setElements([$startEvent2, $phpTask12, $endEvent2]);
    $processModel2->setFlows([$flow201, $flow202]);

    $systemEvent->addEventRecipient($startEvent);

    $persistence->startTransaction();
    $processModel1->updatePermanentObject();
    $processModel2->updatePermanentObject();
    $condition->updatePermanentObject();
    foreach ($processModel1->getFlows() as $flow) $flow->updatePermanentObject();
    $inThrowEvent->updatePermanentObject();
    $endEvent2->updatePermanentObject();
    $systemEvent->updatePermanentObject();
    $persistence->endTransaction();

    $processModel1 = CPProcessModel::getPermanentObject('testCase05', 'CPProcessModel');
    $processModel2 = CPProcessModel::getPermanentObject('testCase05-sidekick', 'CPProcessModel');
}

$persistence = SimplePersistence::instance();


if (count($systemProcessInstance) >= 1) {
    $systemProcessInstance = array_shift($systemProcessInstance);
} else {
    $systemProcessInstance = new ProcessInstance();
    $systemProcessInstance->setProcessModel($systemProcessModel);
    $systemProcessInstance->setState(ProcessState::RUNNING);

    $persistence->startTransaction();
    $systemProcessInstance->createPermanentObject();
    $persistence->endTransaction();
}

$incident = new Incident();
$incident->setProcessInstance($systemProcessInstance);
$incident->setSender(CPSystemEvent::getPermanentObject('__system_general_start_event', CPSystemEvent::class));
$incident->setContext(ContextSerializer::serialize(['x' => 6, 'message' => 'That is fantastic.']));

$persistence->startTransaction();
$incident->createPermanentObject();
$persistence->endTransaction();

$instance = Engine::instance()->instantiate($processModel1, $incident);
//Engine::instance()->executeUntilDone();
while (Engine::instance()->tick()) {
    echo $instance->getProcessModel()->asDotGraph(['states' => $instance->getInstanceStates()]);
}

?>