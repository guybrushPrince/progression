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
     * Constructor.
     * @param string|null $id The id.
     * @param string|null $condition The condition.
     * @param string[]|null $relatedUI A set of related UI elements (if available).
     * @throws UnserializableObjectException
     */
    public function __construct(?string $id = null, ?string $condition = null, ?array $relatedUI = null) {
        parent::__construct($id, $relatedUI);
        if (!is_null($condition)) $this->condition = $condition;
    }

    /**
     * Evaluate the condition.
     * @param array $context
     * @return bool
     */
    public function isFulfilled(array $context) : bool {
        $fulfilled = false;
        $condition = $this->getCondition();
        $condition = str_replace('hasErrors', '(array_key_exists(CPTask::EXCEPTIONS, $context) && count($context[CPTask::EXCEPTIONS]) > 0)', $condition);
        $condition = str_replace('}', '"]', str_replace('{', '$context["', $condition));
        $code = '$fulfilled = ' . $condition . ';';
        eval($code);
        return $fulfilled;
    }

}
?>