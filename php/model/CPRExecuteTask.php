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
 */
class CPRExecuteTask extends CPTask {

    use CPRExecuteTaskPersistentTrait;

    /**
     * The R code to execute.
     * @type string
     * @length MEDIUM
     * @var string
     * @crucial
     */
    protected string $code;

    /**
     * @inheritDoc
     */
    public function execute(array $context) : array {

        throw new NotImplementedException('Please implement the R Execute Task');

        return $context;
    }
}
?>