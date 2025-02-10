<?php

/**
 * Class CPRExecuteTask.
 *
 * A task that executes R code.
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
class CPRExecuteTask extends CPExecuteTask {

    use CPRExecuteTaskPersistentTrait;

    /**
     * Constructor.
     * @param string|null $id The id (if available).
     * @param string|null $code The R code (if available).
     * @param string[]|null $relatedUI A set of related UI elements (if available).
     * @throws UnserializableObjectException
     */
    public function __construct(?string $id = null, ?string $code = null, ?array $relatedUI = null) {
        parent::__construct($id, $relatedUI);
        if (!is_null($code)) $this->code = $code;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $context) : array|PendingResult {
        $context = self::prepareContext($context);

        throw new NotImplementedException('Please implement the R Execute Task');

        return $context;
    }

    /**
     * @inheritDoc
     */
    public function isTerminated(array $context) : array|PendingResult {
        return $context;
    }

    /**
     * @inheritDoc
     */
    public function cancel(array $context) : void { }
}
?>