<?php

/**
 * Class CPIntermediateEvent.
 *
 * An intermediate event node.
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
class CPIntermediateEvent extends CPEvent {

    use CPIntermediateEventPersistentTrait;

    /**
     * Constructor.
     * @param string|null $id The id if available.
     * @param int $direction The direction.
     * @param int $type The type.
     */
    public function __construct(?string $id = null, int $direction = CPEventDirection::NONE,
                                int $type = CPEventType::NONE) {
        parent::__construct($direction, $type);
        if (!is_null($id)) $this->id = $id;
    }

}
?>