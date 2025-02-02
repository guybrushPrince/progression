<?php

include_once __DIR__ . '/../../cliff/php/Persistence.php';
include_once __DIR__ . '/../php/permanent/SimplePersistence.php';

Engine::instance(null, CPLogger::LEVEL_INFO);

foreach (Token::getAll() as $token) $token->delete();
foreach (Incident::getAll() as $incident) $incident->delete();
foreach (LocalState::getAll() as $localState) $localState->delete();
foreach (ProcessInstance::getAll() as $processInstance) $processInstance->delete();

$processModel = CPProcessModel::getPermanentObject('testCase01', 'CPProcessModel');
if (!$processModel) {
    $persistence = SimplePersistence::instance();

    $processModel = new CPProcessModel('testCase01');
    $startEvent = new CPStartEvent('testCase01-startEvent');
    $phpTask = new CPPHPExecuteTask('testCase01-php', 'echo "Hello World";' . PHP_EOL);
    $endEvent = new CPEndEvent('testCase01-endEvent');
    $flow01 = new CPFlow('testCase01-F01', $startEvent, $phpTask);
    $flow02 = new CPFlow('testCase01-F02', $phpTask, $endEvent);

    $processModel->setElements([$startEvent, $phpTask, $endEvent]);
    $processModel->setFlows([$flow01, $flow02]);

    $persistence->startTransaction();
    $processModel->createPermanentObject();
    $persistence->endTransaction();

    $processModel = CPProcessModel::getPermanentObject('testCase01', 'CPProcessModel');
}

/*var_dump($processModel->getElements());
var_dump($processModel->getFlows());*/

$instance = Engine::instance()->instantiate($processModel);
//Engine::instance()->executeUntilDone();
while (Engine::instance()->tick()) {
    echo $instance->getProcessModel()->asDotGraph(['states' => $instance->getInstanceStates()]);
}


?>