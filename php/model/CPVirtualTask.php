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
 */
class CPVirtualTask extends CPTask {

    use CPVirtualTaskPersistentTrait;

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
}
?>