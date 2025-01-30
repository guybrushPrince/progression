<?php

/**
 * Class CPGateway.
 *
 * A gateway.
 *
 * @package progression
 * @subpackge php/model
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 *
 * @persistent
 * @keyGiven
 * @inParent
 */
abstract class CPGateway extends CPNode {

    /**
     * Constructor.
     * @param string|null $id The id of the event (if available).
     */
    public function __construct(?string $id = null) {
        if ($id !== null) $this->id = $id;
    }

}
?>