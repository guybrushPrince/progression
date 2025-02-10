<?php

/**
 * Class Token.
 *
 * @package progression
 * @subpackge php/engines/tokens
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 *
 * @persistent
 */
class Token extends KindOfToken {

    use TokenPersistentTrait;

    /**
     * The condition when live should be assigned (if available).
     * @type CPCondition
     * @nullable
     * @var CPCondition|Closure|null
     */
    protected CPCondition|Closure|null $condition = null;

    /**
     * The corresponding flow to this token (if available).
     * @type CPFlow
     * @nullable
     * @var CPFlow|Closure|null
     */
    protected CPFlow|Closure|null $flow = null;

    /**
     * Checks if this token is conditional.
     * @return bool
     */
    public function isConditional() : bool {
        return $this->getCondition() !== null;
    }

    /**
     * Checks if the condition is fulfilled.
     * @param array $context
     * @return bool
     */
    public function isFulfilled(array $context) : bool {
        return $this->getCondition()->isFulfilled($context);
    }

}
?>