<?php

/**
 * Class CPModel.
 *
 * Represents a part of a progression model element.
 *
 * @package progression
 * @subpackge php/model
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 */
abstract class CPModel extends APermanentObject {

    /**
     * The id of the flow.
     * @type string
     * @length 255
     * @var string
     * @key
     */
    protected string $id;

    /**
     * Get the id.
     * @return string
     */
    public function getId() : string {
        return $this->getKey();
    }

}
?>