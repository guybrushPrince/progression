<?php

include_once __DIR__ . '/../../cliff/php/Persistence.php';
include_once __DIR__ . '/../php/permanent/SimplePersistence.php';

Engine::instance(null, CPLogger::LEVEL_INFO);

foreach (Token::getAll() as $token) $token->delete();
foreach (Incident::getAll() as $incident) $incident->delete();
foreach (LocalState::getAll() as $localState) $localState->delete();
foreach (ProcessInstance::getAll() as $processInstance) $processInstance->delete();

$processModel = CPProcessModel::getPermanentObject('testCase04', 'CPProcessModel');
$systemEvent = CPSystemEvent::getPermanentObject('__system_general_start_event', 'CPSystemEvent');
$systemProcessModel = CPProcessModel::getPermanentObject('__system', 'CPProcessModel');
$systemProcessInstance = ProcessInstance::getPermanentObjectsWhere('processModel', $systemProcessModel, ProcessInstance::class);
if (!$processModel) {
    $persistence = SimplePersistence::instance();

    $processModel = new CPProcessModel('testCase04');
    $startEvent = new CPStartEvent('testCase04-startEvent');
    $xorSplit = new CPXORGateway('testCase04-xorSplit');
    $phpTask1 = new CPPHPExecuteTask('testCase04-php-01', 'echo "Hello World 1\n";' . PHP_EOL);
    $phpTask2 = new CPPHPExecuteTask('testCase04-php-02', 'echo "Hello World 2\n";' . PHP_EOL);
    $xorJoin = new CPANDGateway('testCase04-xorJoin');
    $endEvent = new CPEndEvent('testCase04-endEvent');
    $flow01 = new CPFlow('testCase04-F01', $startEvent, $xorSplit);
    $flow02 = new CPFlow('testCase04-F02', $xorSplit, $phpTask1);
    $condition = new CPCondition('textCase04-F02-cond', '{x} > 5');
    $flow02->setUseCondition($condition);
    $flow03 = new CPFlow('testCase04-F03', $xorSplit, $phpTask2);
    $flow04 = new CPFlow('testCase04-F04', $phpTask1, $xorJoin);
    $flow05 = new CPFlow('testCase04-F05', $phpTask2, $xorJoin);
    $flow06 = new CPFlow('testCase04-F06', $xorJoin, $endEvent);

    $processModel->setElements([$startEvent, $xorSplit, $phpTask1, $phpTask2, $xorJoin, $endEvent]);
    $processModel->setFlows([$flow01, $flow02, $flow03, $flow04, $flow05, $flow06]);

    $systemEvent->addEventRecipient($startEvent);

    $persistence->startTransaction();
    $processModel->createPermanentObject();
    $condition->createPermanentObject();
    $flow02->updatePermanentObject();
    $systemEvent->updatePermanentObject();
    $persistence->endTransaction();

    $processModel = CPProcessModel::getPermanentObject('testCase04', 'CPProcessModel');
}

$persistence = SimplePersistence::instance();


if (count($systemProcessInstance) >= 1) {
    $systemProcessInstance = array_shift($systemProcessInstance);
} else {
    $systemProcessInstance = new ProcessInstance();
    $systemProcessInstance->setProcessModel($systemProcessModel);
    $systemProcessInstance->setState(ProcessState::RUNNING);

    $persistence->startTransaction();
    $processModel->updatePermanentObject();
    $systemProcessInstance->createPermanentObject();
    $persistence->endTransaction();
}

$incident = new Incident();
$incident->setProcessInstance($systemProcessInstance);
$incident->setSender(CPSystemEvent::getPermanentObject('__system_general_start_event', CPSystemEvent::class));
$incident->setContext(ContextSerializer::serialize(['x' => 3]));

$persistence->startTransaction();
$incident->createPermanentObject();
$persistence->endTransaction();

$instance = Engine::instance()->instantiate($processModel, $incident);
//Engine::instance()->executeUntilDone();
while (Engine::instance()->tick()) {
    echo $instance->getProcessModel()->asDotGraph(['states' => $instance->getInstanceStates()]);
}

?>