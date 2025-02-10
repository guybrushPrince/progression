<?php

/**
 * Class ContextVariable.
 *
 * @package progression
 * @subpackge php/engine/serialize
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 */
class ContextVariable {

    /**
     * The name of the context variable.
     * @var string
     */
    private string $name;

    /**
     * Constructor.
     * @param string $name The name of the context variable.
     */
    public function __construct(string $name) {
        $this->name = $name;
    }

    /**
     * Get the name.
     * @return string
     */
    public function getName() : string {
        return $this->name;
    }

    /**
     * Create a context variable.
     * @param string $name The name of the context variable.
     * @return ContextVariable
     */
    public static function create(string $name) : ContextVariable {
        return new ContextVariable($name);
    }

}
?>