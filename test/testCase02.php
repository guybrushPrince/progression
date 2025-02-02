<?php

include_once __DIR__ . '/../../cliff/php/Persistence.php';
include_once __DIR__ . '/../php/permanent/SimplePersistence.php';

Engine::instance(null, CPLogger::LEVEL_INFO);

foreach (Token::getAll() as $token) $token->delete();
foreach (Incident::getAll() as $incident) $incident->delete();
foreach (LocalState::getAll() as $localState) $localState->delete();
foreach (ProcessInstance::getAll() as $processInstance) $processInstance->delete();

$processModel = CPProcessModel::getPermanentObject('testCase02', 'CPProcessModel');
if (!$processModel) {
    $persistence = SimplePersistence::instance();

    $processModel = new CPProcessModel('testCase02');
    $startEvent = new CPStartEvent('testCase02-startEvent');
    $andSplit = new CPANDGateway('testCase02-andSplit');
    $phpTask1 = new CPPHPExecuteTask('testCase02-php-01', 'echo "Hello World 1\n";' . PHP_EOL);
    $phpTask2 = new CPPHPExecuteTask('testCase02-php-02', 'echo "Hello World 2\n";' . PHP_EOL);
    $andJoin = new CPANDGateway('testCase02-andJoin');
    $endEvent = new CPEndEvent('testCase02-endEvent');
    $flow01 = new CPFlow('testCase02-F01', $startEvent, $andSplit);
    $flow02 = new CPFlow('testCase02-F02', $andSplit, $phpTask1);
    $flow03 = new CPFlow('testCase02-F03', $andSplit, $phpTask2);
    $flow04 = new CPFlow('testCase02-F04', $phpTask1, $andJoin);
    $flow05 = new CPFlow('testCase02-F05', $phpTask2, $andJoin);
    $flow06 = new CPFlow('testCase02-F06', $andJoin, $endEvent);

    $processModel->setElements([$startEvent, $andSplit, $phpTask1, $phpTask2, $andJoin, $endEvent]);
    $processModel->setFlows([$flow01, $flow02, $flow03, $flow04, $flow05, $flow06]);

    $persistence->startTransaction();
    $processModel->createPermanentObject();
    $persistence->endTransaction();

    $processModel = CPProcessModel::getPermanentObject('testCase02', 'CPProcessModel');
}

/*var_dump($processModel->getElements());
var_dump($processModel->getFlows());*/

$instance = Engine::instance()->instantiate($processModel);
//Engine::instance()->executeUntilDone();
while (Engine::instance()->tick()) {
    echo $instance->getProcessModel()->asDotGraph(['states' => $instance->getInstanceStates()]);
}

?>