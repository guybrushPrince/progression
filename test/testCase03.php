<?php

include_once __DIR__ . '/../../cliff/php/Persistence.php';
include_once __DIR__ . '/../../vendor/autoload.php';
include_once __DIR__ . '/../php/permanent/SimplePersistence.php';

Engine::instance(null, CPLogger::LEVEL_INFO);

foreach (Token::getAll() as $token) $token->delete();
foreach (Incident::getAll() as $incident) $incident->delete();
foreach (LocalState::getAll() as $localState) $localState->delete();
foreach (ProcessInstance::getAll() as $processInstance) $processInstance->delete();

$processModel = CPProcessModel::getPermanentObject('testCase03', 'CPProcessModel');
$systemProcessModel = CPProcessModel::getPermanentObject('__system', 'CPProcessModel');
$systemProcessInstance = ProcessInstance::getPermanentObjectsWhere('processModel', $systemProcessModel, ProcessInstance::class);
if (!$processModel || !$systemProcessModel || count($systemProcessInstance) === 0) {
    $persistence = SimplePersistence::instance();

    $processModel = new CPProcessModel('testCase03');
    $startEvent = new CPStartEvent('testCase03-startEvent');
    $phpTask = new CPPHPExecuteTask('testCase03-php', 'echo "\n\nHello " . $name , "!\n\n";' . PHP_EOL);
    $endEvent = new CPEndEvent('testCase03-endEvent');
    $flow01 = new CPFlow('testCase03-F01', $startEvent, $phpTask);
    $flow02 = new CPFlow('testCase03-F02', $phpTask, $endEvent);

    $processModel->setElements([$startEvent, $phpTask, $endEvent]);
    $processModel->setFlows([$flow01, $flow02]);

    $systemEvent = new CPSystemEvent('__system_general_start_event');
    $systemEvent->addEventRecipient($startEvent);

    $systemProcessModel = new CPProcessModel('__system');
    $systemProcessModel->addElement($systemEvent);
    $systemProcessInstance = new ProcessInstance();
    $systemProcessInstance->setProcessModel($systemProcessModel);
    $systemProcessInstance->setState(ProcessState::RUNNING);

    $persistence->startTransaction();
    $processModel->createPermanentObject();
    $systemProcessModel->createPermanentObject();
    $systemProcessInstance->createPermanentObject();
    $persistence->endTransaction();

    $processModel = CPProcessModel::getPermanentObject('testCase03', 'CPProcessModel');
    $systemProcessModel = CPProcessModel::getPermanentObject('__system', 'CPProcessModel');
    $systemProcessInstance = ProcessInstance::getPermanentObjectsWhere('processModel', $systemProcessModel, ProcessInstance::class);
}

if (count($systemProcessInstance) >= 1) $systemProcessInstance = array_shift($systemProcessInstance);

/*var_dump($processModel->getElements());
var_dump($processModel->getFlows());*/

$incident = new Incident();
$incident->setProcessInstance($systemProcessInstance);
$incident->setSender(CPSystemEvent::getPermanentObject('__system_general_start_event', CPSystemEvent::class));
$incident->setContext(ContextSerializer::serialize(['name' => 'Thomas']));

$persistence = SimplePersistence::instance();
$persistence->startTransaction();
$incident->createPermanentObject();
$persistence->endTransaction();

Engine::instance()->instantiate($processModel, $incident);
Engine::instance()->executeUntilDone();


?>