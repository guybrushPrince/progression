<?php

/**
 * Class CPSEndEvent.
 *
 * An end event.
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
class CPEndEvent extends CPEvent {

    use CPEndEventPersistentTrait;

    /**
     * Constructor.
     * @param string|null $id The id of the end event if available.
     * @param int $type The event type.
     */
    public function __construct(?string $id = null, int $type = CPEventType::NONE) {
        parent::__construct(CPEventDirection::THROWING, $type);
        if ($id !== null) $this->id = $id;
    }
}
?>