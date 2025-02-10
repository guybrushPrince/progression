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
     * @param string[]|null $relatedUI A set of related UI elements (if available).
     * @throws UnserializableObjectException
     */
    public function __construct(?string $id = null, int $direction = CPEventDirection::NONE,
                                int $type = CPEventType::NONE, ?array $relatedUI = null) {
        parent::__construct($id, $direction, $type, $relatedUI);
    }

}
?>