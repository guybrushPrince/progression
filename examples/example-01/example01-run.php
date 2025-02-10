<?php
include_once __DIR__ . '/../../php/engine/Engine.php';
Persistence::instance()->initiateDefault();

$persistence = SimplePersistence::instance();

$processModel = CPProcessModel::getPermanentObject('Report-Send-Process', 'CPProcessModel');
$systemProcessModel = CPProcessModel::getPermanentObject('__system', 'CPProcessModel');
$systemProcessInstance = ProcessInstance::getPermanentObjectsWhere('processModel', $systemProcessModel, ProcessInstance::class);

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
$incident->setReceiver(CPStartEvent::getPermanentObject('Event_0k02j3c', CPStartEvent::class));

$project = Project::getPermanentObject(995118617246154161, Project::class);
$incident->setContext(ContextSerializer::serialize([
    SomethingJobTask::RELATED_OBJECT => $project,
    'cluster' => $project->getLayout()->getClusterFilters()[0],
    'language' => Language::GERMAN,
]));

$persistence->startTransaction();
$incident->createPermanentObject();
$persistence->endTransaction();

$instance = Engine::instance()->instantiate($processModel, $incident, null, 'thomas', 'ULe');

echo 'Created process instance: ' . $instance->getPermanentId() . PHP_EOL
?>