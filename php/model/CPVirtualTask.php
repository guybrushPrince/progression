<?php

/**
 * Class CPVirtualTask.
 *
 * A task that does nothing.
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
class CPVirtualTask extends CPTask {

    use CPVirtualTaskPersistentTrait;

    /**
     * Constructor.
     * @param string|null $id The id.
     * @param string[]|null $relatedUI A set of related UI elements (if available).
     * @throws UnserializableObjectException
     */
    public function __construct(?string $id = null, ?array $relatedUI = null) {
        parent::__construct($id, $relatedUI);
    }

    /**
     * @inheritDoc
     */
    public function execute(array $context) : array {
        // It does nothing
        return $context;
    }

    /**
     * @inheritDoc
     */
    public function isTerminated(array $context): array|PendingResult {
        return $context;
    }

    /**
     * @inheritDoc
     */
    public function cancel(array $context) : void { }
}
?>