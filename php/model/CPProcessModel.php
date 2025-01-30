<?php

/**
 * Class CPProcessModel.
 *
 * Represents a process model that is acyclic!
 *
 * @package progression
 * @subpackge php/model
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 *
 * @persistent
 */
class CPProcessModel extends CPModel implements Graph {

    use CPProcessModelPersistentTrait;

    /**
     * Constructor.
     * @param string|null $id The id (if available).
     */
    public function __construct(?string $id = null) {
        if ($id !== null) $this->id = $id;
    }

    /**
     * An array of nodes.
     * @type [CPNode
     * @nonviable
     * @var CPNode[]|Closure
     */
    protected array|Closure $elements = [];

    /**
     * An array of flows
     * @type [CPFlow
     * @nonviable
     * @var CPFlow[]|Closure
     */
    protected array|Closure $flows = [];

    /**
     * A corresponding bpmn model.
     * @var string|null
     * @nullable
     * @type string
     * @length MEDIUM
     */
    protected string|null $bpmnModel;

    /**
     * @inheritDoc
     */
    public function getNodes() : array {
        return $this->getElements();
    }

    /**
     * @inheritDoc
     */
    public function getEdges() : array {
        return $this->getFlows();
    }

    /**
     * @inheritDoc
     */
    public function determineStartNodes() : array {
        return array_filter($this->getElements(), function (CPNode $node) {
            return $node instanceof CPStartEvent;
        });
    }

    /**
     * @inheritDoc
     */
    public function determineEndNodes() : array {
        return array_filter($this->getElements(), function (CPNode $node) {
            return $node instanceof CPEndEvent;
        });
    }

    /**
     * @inheritDoc
     */
    public function asDotGraph(array $properties = []) : string {
        $graph = 'digraph ' . CPLogger::slug($this->getPermanentId()) . ' {' . PHP_EOL;
        foreach ($this->getElements() as $element) {
            $graph .= 'n' . CPLogger::slug($element->getPermanentId()) . '[label="' . $element->getPermanentId() . '",shape="' .
                [
                    CPPHPExecuteTask::class => 'box',
                    CPRExecuteTask::class => 'box',
                    CPExecuteTask::class => 'box',
                    CPTask::class => 'box',
                    CPStartEvent::class => 'circle',
                    CPEndEvent::class => 'doublecircle',
                    CPIntermediateEvent::class => 'Mcircle',
                    CPANDGateway::class => 'diamond',
                    CPXORGateway::class => 'diamond',
                    CPORGateway::class => 'diamond',
                    CPGateway::class => 'diamond'
                ][get_class($element)] . '"]' . PHP_EOL;
        }
        foreach ($this->getFlows() as $flow) {
            $graph .= 'n' . CPLogger::slug($flow->getSource()->getPermanentId()) . ' -> n' . CPLogger::slug($flow->getTarget()->getPermanentId()) . '' . PHP_EOL;
        }
        $graph .= '}' . PHP_EOL;
        return $graph;
    }

    /**
     * Checks whether the given model element is part of this process model.
     * @param CPModel $element The model elemen
     * @return bool
     * @throws NotImplementedException
     */
    public function contains(CPModel $element) : bool {
        foreach ($this->getElements() as $el) {
            if ($el->getPermanentId() === $element->getPermanentId()) return true;
        }
        foreach ($this->getFlows() as $flow) {
            if ($flow->getPermanentId() === $element->getPermanentId()) return true;
        }
        return false;
    }
}
?>