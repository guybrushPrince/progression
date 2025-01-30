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

    /**
     * Executes this task in the given context (and extends the context when results are available).
     * @param array $context The context as key-value-pairs.
     * @return array
     */
    abstract public function execute(array $context) : array;

}
?>