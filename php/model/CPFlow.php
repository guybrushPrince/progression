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
     */
    public function __construct(?string $id = null, ?CPNode $source = null, ?CPNode $target = null) {
        if ($id !== null) $this->id = $id;
        if ($source) $this->source = $source;
        if ($target) $this->target = $target;
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