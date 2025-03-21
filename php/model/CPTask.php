<?php

/**
 * Class CPTask.
 *
 * A traditional task performing some actions (e.g., requiring some information from the user, call a service, etc.).
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
abstract class CPTask extends CPNode {

    public const EXCEPTIONS = '__exceptions';

    /**
     * Executes this task in the given context (and extends the context when results are available).
     * @param array $context The context as key-value-pairs.
     * @return array|PendingResult
     */
    abstract public function execute(array $context) : array|PendingResult;

    /**
     * Checks if a pending task is terminated and if so, then it starts the finishing of the execution.
     * @param array $context The context as key-value-pairs.
     * @return array|PendingResult
     */
    abstract public function isTerminated(array $context) : array|PendingResult;

    /**
     * Cancel the task.
     * @param array $context The context as key-value-pairs.
     * @return void
     */
    abstract public function cancel(array $context) : void;

}
?>