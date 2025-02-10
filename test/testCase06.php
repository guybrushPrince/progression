<?php

include_once __DIR__ . '/../php/engine/Engine.php';
Persistence::instance()->initiateDefault();

Engine::instance(null, CPLogger::LEVEL_DEBUG);

foreach (Token::getAll() as $token) $token->delete();
foreach (Incident::getAll() as $incident) $incident->delete();
foreach (LocalState::getAll() as $localState) $localState->delete();
foreach (ProcessInstance::getAll() as $processInstance) $processInstance->delete();

$persistence = SimplePersistence::instance();

$processModel = CPProcessModel::getPermanentObject('testCase06', 'CPProcessModel');
$systemProcessModel = CPProcessModel::getPermanentObject('__system', 'CPProcessModel');
$systemProcessInstance = ProcessInstance::getPermanentObjectsWhere('processModel', $systemProcessModel, ProcessInstance::class);
if (!$processModel) {

    $processModel = new CPProcessModel('testCase06');
    $startEvent = new CPStartEvent('testCase06-startEvent');
    $reportTask = new SomethingJobTask('testCase06-report', ReportJob::class);
    $mailTask = new SomethingJobTask('testCase06-mail', MailJob::class);
    $endEvent = new CPEndEvent('testCase06-endEvent');
    $flow01 = new CPFlow('testCase06-F01', $startEvent, $reportTask);
    $flow02 = new CPFlow('testCase06-F02', $reportTask, $mailTask);
    $flow03 = new CPFlow('testCase06-F03', $mailTask, $endEvent);

    $processModel->setElements([$startEvent, $reportTask, $mailTask, $endEvent]);
    $processModel->setFlows([$flow01, $flow02, $flow03]);

    $persistence->startTransaction();
    $processModel->createPermanentObject();
    foreach ($processModel->getElements() as $element) $element->createPermanentObject();
    foreach ($processModel->getFlows() as $flow) $flow->createPermanentObject();
    $persistence->endTransaction();

    $processModel = CPProcessModel::getPermanentObject('testCase06', 'CPProcessModel');
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
$incident->setReceiver(CPStartEvent::getPermanentObject('testCase06-startEvent', CPStartEvent::class));
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

$instance = Engine::instance()->instantiate($processModel, $incident, null, 'thomas', 'ULe');
//Engine::instance()->executeUntilDone();
while (Engine::instance()->tick()) {
    echo $instance->getProcessModel()->asDotGraph(['states' => $instance->getInstanceStates()]);
}


?>