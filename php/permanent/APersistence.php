<?php

/**
 * Abstract Class APersistence.
 *
 * @package progression
 * @subpackge php/permanent
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 */
abstract class APersistence {

    /**
     * Get an instance of the persistence layer.
     * @return APersistence
     */
    public abstract static function instance() : APersistence;

    /**
     * Start a database transaction.
     * @return bool
     */
    public abstract function startTransaction() : bool;

    /**
     * Terminate a database transaction.
     * @return bool
     */
    public abstract function endTransaction() : bool;
}
?>