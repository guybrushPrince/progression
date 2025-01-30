<?php

/**
 * Class CPCondition.
 *
 * @package progression
 * @subpackge php/model
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 *
 * @persistent
 * @keyGiven
 */
class CPCondition extends CPModel {

    use CPConditionPersistentTrait;

    /**
     * The textual condition in which context variables are separated with {}, e.g., {report}.
     * @length 1000
     * @type string
     * @var string
     */
    protected string $condition;

    /**
     * Evaluate the condition.
     * @param array $context
     * @return bool
     */
    public function isFulfilled(array $context) : bool {
        $fulfilled = false;
        $condition = str_replace('}', '"]', str_replace('{', '$context["', $this->condition));
        $code = '$fulfilled = ' . $condition . ';';
        eval($code);
        return $fulfilled;
    }

}
?>