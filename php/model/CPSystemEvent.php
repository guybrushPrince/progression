<?php

/**
 * Class CPSystemEvent.
 *
 * A system event (this is not thrown by a process instance).
 *
 * @package progression
 * @subpackge php/model
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 *
 * @persistent
 * @inParent
 */
class CPSystemEvent extends CPEvent {

    use CPSystemEventPersistentTrait;

    /**
     * Constructor.
     * @param string|null $id The id of the event (if available).
     * @param int $type The event type.
     * @throws UnserializableObjectException
     */
    public function __construct(?string $id = null, int $type = CPEventType::NONE) {
        parent::__construct($id, CPEventDirection::THROWING, $type);
    }
}
?>