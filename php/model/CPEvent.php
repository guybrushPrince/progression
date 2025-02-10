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
     * @param string|null $id The id of the event if available.
     * @param int $direction Sets the direction.
     * @param int $type Sets the type.
     * @param string[]|null $relatedUI A set of related UI elements (if available).
     * @throws UnserializableObjectException
     */
    public function __construct(?string $id = null, int $direction = CPEventDirection::NONE,
                                int $type = CPEventType::NONE, ?array $relatedUI = null) {
        parent::__construct($id, $relatedUI);
        $this->direction = $direction;
        $this->type = $type;
    }

}
?>