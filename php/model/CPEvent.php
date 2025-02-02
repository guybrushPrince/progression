<?php

/**
 * Class CPEvent.
 *
 * An event node (intermediate, start, or end).
 *
 * @package progression
 * @subpackge php/model
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 *
 * @persistent
 * @keyGiven
 * @inParent
 */
abstract class CPEvent extends CPNode {

    use CPEventPersistentTrait;

    /**
     * The direction of the event.
     * @type int
     * @var int
     */
    protected int $direction = CPEventDirection::NONE;

    /**
     * The direction of the event.
     * @type int
     * @var int
     */
    protected int $type = CPEventType::NONE;

    /**
     * Recipients of the message thrown by the event.
     * @type [CPEvent
     * @var CPEvent[]|Closure|null
     */
    protected array|Closure $eventRecipients = [];

    /**
     * Process model recipients of the message thrown by the event.
     * @type [CPProcessModel
     * @var CPProcessModel[]|Closure|null
     */
    protected array|Closure $processRecipients = [];

    /**
     * Constructor.
     * @param int $direction Sets the direction.
     * @param int $type Sets the type.
     */
    public function __construct(int $direction = CPEventDirection::NONE, int $type = CPEventType::NONE) {
        $this->direction = $direction;
        $this->type = $type;
    }

}
?>