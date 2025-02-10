<?php

/**
 * Class CPFlow.
 *
 * A sequence flow within the model.
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
class CPFlow extends CPModel implements GraphEdge {

    use CPFlowPersistentTrait;

    /**
     * Constructor.
     * @param string|null $id The id (if available).
     * @param CPNode|null $source The source.
     * @param CPNode|null $target The target.
     * @param string[]|null $relatedUI A set of related UI elements (if available).
     * @throws UnserializableObjectException
     */
    public function __construct(?string $id = null, ?CPNode $source = null, ?CPNode $target = null,
                                ?array $relatedUI = null, ?CPCondition $condition = null) {
        parent::__construct($id, $relatedUI);
        if (!is_null($source)) $this->source = $source;
        if (!is_null($target)) $this->target = $target;
        if (!is_null($condition)) $this->setUseCondition($condition);
    }

    /**
     * The source of the flow.
     * @type CPNode
     * @crucial
     * @var CPNode|Closure|null
     */
    protected CPNode|Closure|null $source;

    /**
     * The target of the flow.
     * @type CPNode
     * @crucial
     * @var CPNode|Closure|null
     */
    protected CPNode|Closure|null $target;

    /**
     * The condition when to take this flow.
     * @type CPCondition
     * @var CPCondition|Closure|null
     * @nonviable
     */
    protected CPCondition|Closure|null $useCondition = null;

    /**
     * @inheritDoc
     */
    public function getFrom() : CPNode {
        return $this->getSource();
    }

    /**
     * @inheritDoc
     */
    public function getTo() : CPNode {
        return $this->getTarget();
    }

    /**
     * @inheritDoc
     */
    public function getCondition() : ?CPCondition {
        return $this->getUseCondition();
    }
}
?>