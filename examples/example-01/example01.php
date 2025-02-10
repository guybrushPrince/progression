<?php

include_once __DIR__ . '/../../php/engine/Engine.php';

// The process model
$process = new CPProcessModel('Report-Send-Process', __DIR__ . '/example01.bpmn');

// The nodes
$startEvent = new CPStartEvent('Event_0k02j3c');
$setPropertiesTask = new CPContextTask('Activity_1338yrd', [
    'sender' => 'Thomas.Prinz@uni-jena.de',
    'sender name' => 'Coast',
    'subject' => 'Bericht fertiggestellt',
    'message' => 'Coast hat deinen Bericht fertiggestellt. Du findest ihn im Anhang dieser E-Mail.',
    'recipients' => [
        'Thomas.Prinz@uni-jena.de'
    ],
    'attachments' => [
        ContextVariable::create('result')
    ]
], []);
$reportTask = new SomethingJobTask('Activity_0xutz4q', ReportJob::class);
$implicitXORSplit = new CPXORGateway('Activity_0xutz4q-Event_0xoeq6j');
$setErrorsTask = new CPContextTask('Activity_1k376cs', [
    'subject' => 'Fehler bei der Berichtslegung',
    'message' => 'Coast konnte deinen Bericht nicht fertigstellen, da es bei der Berichtslegung zu einem Fehler kam.',
    'attachments' => []
]);
$virtualTask = new CPVirtualTask('Activity_0xutz4q-Activity_1c91sne');
$implicitXORJoin = new CPXORGateway('Activity_1c91sne-xor-join');
$mailTask = new SomethingJobTask('Activity_1c91sne', MailJob::class);
$endEvent = new CPEndEvent('Event_0l2ppg7');

$process->setElements([
    $startEvent,
    $setPropertiesTask,
    $reportTask,
    $implicitXORSplit,
    $setErrorsTask,
    $virtualTask,
    $implicitXORJoin,
    $mailTask,
    $endEvent
]);

// The flows
$flow01 = new CPFlow('Flow_13dmxbg', $startEvent, $setPropertiesTask);
$flow02 = new CPFlow('Flow_15ss55y', $setPropertiesTask, $reportTask);
$flow03 = new CPFlow('Vir_Flow_Activity_0xutz4q', $reportTask, $implicitXORSplit, [ 'Activity_0xutz4q' ]);
$flow04 = new CPFlow('Flow_09vnigc', $implicitXORSplit, $virtualTask);
$flow05 = new CPFlow('Flow_0a8kwe3', $implicitXORSplit, $setErrorsTask, [ 'Event_0xoeq6j', 'Flow_0a8kwe3' ], new CPCondition('Flow_0a8kwe3_condition', 'hasErrors'));
$flow06 = new CPFlow('Vir_Flow_09vnigc', $virtualTask, $implicitXORJoin, [ 'Flow_09vnigc' ]);
$flow07 = new CPFlow('Flow_1obs6uk', $setErrorsTask, $implicitXORJoin);
$flow08 = new CPFlow('Vir_Flow_1obs6uk', $implicitXORJoin, $mailTask, [ 'Flow_1obs6uk' ]);
$flow09 = new CPFlow('Flow_1cv9op7', $mailTask, $endEvent);

$process->setFlows([
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

SimplePersistence::instance()->startTransaction();
$process->createPermanentObject();
SimplePersistence::instance()->endTransaction();

?>