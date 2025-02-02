<?php

/**
 * Class Incident.
 *
 * @package progression
 * @subpackge php/engines/tokens
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 *
 * @persistent
 */
class Incident extends KindOfToken {

    use IncidentPersistentTrait;

    /**
     * The type of the incident.
     * @var int
     * @type int
     */
    protected int $type = CPEventType::NONE;

    /**
     * A recipient of this incident.
     * @type ProcessInstance
     * @nullable
     * @var ProcessInstance|Closure|null
     */
    protected ProcessInstance|Closure|null $recipient = null;

    /**
     * The sender of the event.
     * @type CPEvent
     * @var CPEvent|Closure|null
     * @crucial
     */
    protected CPEvent|Closure|null $sender = null;

    /**
     * The receiver of the event.
     * @type CPEvent
     * @var CPEvent|Closure|null
     * @crucial
     */
    protected CPEvent|Closure|null $receiver = null;

}
?>