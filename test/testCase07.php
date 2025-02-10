<?php

include_once __DIR__ . '/../php/engine/Engine.php';
Persistence::instance()->initiateDefault();

Engine::instance(null, CPLogger::LEVEL_DEBUG);

foreach (Token::getAll() as $token) $token->delete();
foreach (Incident::getAll() as $incident) $incident->delete();
foreach (LocalState::getAll() as $localState) $localState->delete();
foreach (ProcessInstance::getAll() as $processInstance) $processInstance->delete();

$persistence = SimplePersistence::instance();

$processModel = CPProcessModel::getPermanentObject('testCase07', 'CPProcessModel');
$systemProcessModel = CPProcessModel::getPermanentObject('__system', 'CPProcessModel');
$systemProcessInstance = ProcessInstance::getPermanentObjectsWhere('processModel', $systemProcessModel, ProcessInstance::class);

if (!$processModel) {
    $processModel = new CPProcessModel('testCase07');
    $startEvent = new CPStartEvent('testCase07-startEvent');
    $reportTask = new SomethingJobTask('testCase07-report', ReportJob::class);
    $xorSplit = new CPXORGateway('testCase07-xor-split');
    $virtualTask = new CPVirtualTask('testCase07-virtual');
    $contextTask = new CPContextTask('testCase07-context', ['subject' => 'Berichtslegung schlug fehl', 'message' => 'Coast konnte deinen Bericht nicht fertigstellen', 'attachments' => [ContextVariable::create(CPTask::EXCEPTIONS)]]);
    $xorJoin = new CPXORGateway('testCase07-xor-join');
    $mailTask = new SomethingJobTask('testCase07-mail', MailJob::class);
    $endEvent = new CPEndEvent('testCase07-endEvent');
    $flow01 = new CPFlow('testCase07-F01', $startEvent, $reportTask);
    $flow02 = new CPFlow('testCase07-F02', $reportTask, $xorSplit);
    $flow03 = new CPFlow('testCase07-F03', $xorSplit, $virtualTask);
    $flow04 = new CPFlow('testCase07-F04', $xorSplit, $contextTask);
    $flow04->setUseCondition(new CPCondition('testCase07-F04-condition', 'hasErrors'));
    $flow05 = new CPFlow('testCase07-F05', $virtualTask, $xorJoin);
    $flow06 = new CPFlow('testCase07-F06', $contextTask, $xorJoin);
    $flow07 = new CPFlow('testCase07-F07', $xorJoin, $mailTask);
    $flow08 = new CPFlow('testCase07-F08', $mailTask, $endEvent);

    $processModel->setElements([$startEvent, $reportTask, $xorSplit, $virtualTask, $contextTask, $xorJoin, $mailTask, $endEvent]);
    $processModel->setFlows([$flow01, $flow02, $flow03, $flow04, $flow05, $flow06, $flow07, $flow08]);

    $persistence->startTransaction();
    $processModel->createPermanentObject();
    foreach ($processModel->getElements() as $element) $element->createPermanentObject();
    foreach ($processModel->getFlows() as $flow) $flow->createPermanentObject();
    $persistence->endTransaction();

    $processModel = CPProcessModel::getPermanentObject('testCase07', 'CPProcessModel');
}

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
$incident->setReceiver(CPStartEvent::getPermanentObject('testCase07-startEvent', CPStartEvent::class));
$project = Project::getPermanentObject(995118617246154161, Project::class);
$incident->setContext(ContextSerializer::serialize([
    SomethingJobTask::RELATED_OBJECT => $project,
    'cluster' => $project->getLayout()->getClusterFilters()[0],
    'language' => Language::GERMAN,
    'sender' => 'thomas.prinz@uni-jena.de',
    'sender name' => 'Thomas Prinz',
    'subject' => 'Bericht fertiggestellt',
    'message' => 'Coast hat deinen Bericht fertiggestellt',
    'recipients' => [
        'thomas.prinz@uni-jena.de'
    ],
    'attachments' => [
        new ContextVariable('result')
    ]
]));

$persistence = SimplePersistence::instance();
$persistence->startTransaction();
$incident->createPermanentObject();
$persistence->endTransaction();

echo $processModel->asDotGraph();

$instance = Engine::instance()->instantiate($processModel, $incident, null, 'thomas', 'ULe');
//Engine::instance()->executeUntilDone();
while (Engine::instance()->tick()) {
    echo $instance->getProcessModel()->asDotGraph(['states' => $instance->getInstanceStates()]);
}


?>