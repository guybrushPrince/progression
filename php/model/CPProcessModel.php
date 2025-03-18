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
     * @param string|null $bpmnModelOrFile A path to a BPMN file or the content of a BPMN file.
     * @param string[]|null $relatedUI A set of related UI elements (if available).
     * @throws UnserializableObjectException
     */
    public function __construct(?string $id = null, ?string $bpmnModelOrFile = null, ?array $relatedUI = null) {
        parent::__construct($id, $relatedUI);
        if (!is_null($bpmnModelOrFile)) {
            if (is_file($bpmnModelOrFile)) {
                $bpmnModelOrFile = file_get_contents($bpmnModelOrFile);
            }
            $this->bpmnModel = $bpmnModelOrFile;
        }
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
        if (array_key_exists('states', $properties)) {
            $states = $properties['states'];
            $statesTmp = array_reduce($states, function (array $states, Token|Incident|LocalState $state) {
                if ($state instanceof LocalState) {
                    $states[$state->getNode()->getPermanentId()] = $state;
                    // Its in-tokens and in-incidents are known
                    foreach (array_merge($state->getInTokens(), $state->getInIncidents()) as $token) {
                        if (!array_key_exists($token->getPermanentId(), $states)) {
                            $states[$token->getPermanentId()] = ['to' => $state->getNode()->getPermanentId(), 'object' => $token];
                        } else $states[$token->getPermanentId()]['to'] = $state->getNode()->getPermanentId();
                    }
                    // Its out-tokens and out-incidents are known
                    foreach (array_merge($state->getOutTokens(), $state->getOutIncidents()) as $token) {
                        if (!array_key_exists($token->getPermanentId(), $states)) {
                            $states[$token->getPermanentId()] = ['from' => $state->getNode()->getPermanentId(), 'object' => $token];
                        } else $states[$token->getPermanentId()]['from'] = $state->getNode()->getPermanentId();
                    }
                }
                return $states;
            }, []);
            $states = [];
            foreach ($statesTmp as $key => $state) {
                if (is_array($state) && array_key_exists('from', $state) && array_key_exists('to', $state)) {
                    $states[$state['from'] . '-' . $state['to']] = $state['object'];
                } else $states[$key] = $state;
            }
        } else $states = [];
        $graph = 'digraph ' . CPLogger::slug($this->getPermanentId()) . (array_key_exists('id', $properties) ? $properties['id'] : '') . ' {' . PHP_EOL;
        foreach ($this->getElements() as $element) {
            $graph .= 'n' . CPLogger::slug($element->getPermanentId()) . '[label="' . $element->getPermanentId() . '" ' .
                'fixedsize="true" width="1" shape="' . $this->getShape($element) . '"';
            if (array_key_exists($element->getPermanentId(), $states)) {
                $localState = $states[$element->getPermanentId()];
                $graph .= ' color="' . $this->getColor($localState->getState()) . '"';
            }
            $graph .= ']' . PHP_EOL;
        }
        foreach ($this->getFlows() as $flow) {
            $id = $flow->getSource()->getPermanentId() . '-' . $flow->getTarget()->getPermanentId();
            if (array_key_exists($id, $states)) {
                $color = 'color="' . $this->getColor($states[$id]->getState()) . '"';
            } else $color = '';
            if ($flow->getCondition()) {
                $condition = ' label="' . $flow->getUseCondition()->getCondition() . '"';
            } else $condition = '';
            $graph .= 'n' . CPLogger::slug($flow->getSource()->getPermanentId()) . ' -> n' . CPLogger::slug($flow->getTarget()->getPermanentId()) . '[' . $color . $condition . ']' . PHP_EOL;
        }
        $graph .= '}' . PHP_EOL;
        return $graph;
    }

    /**
     * Get the shape for a given node.
     * @param CPNode $node The node.
     * @return string
     */
    private function getShape(CPNode $node) : string {
        return match (get_class($node)) {
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
            CPGateway::class => 'diamond',
            default => 'box',
        };
    }

    /**
     * @param int $state
     * @return string
     */
    private function getColor(int $state) : string {
        return [
            TokenState::CLEAR => 'floralwhite',
            TokenState::LIVE => 'darkolivegreen1',
            TokenState::DEAD => 'firebrick1',
            TokenState::PREVIOUSLY_LIVE => 'forestgreen',
            TokenState::PREVIOUSLY_DEAD => 'firebrick4',
            TokenState::PENDING => 'gold',
            TokenState::CANCELED => 'red'
        ][$state];
    }

    /**
     * Checks whether the given model element is part of this process model.
     * @param CPModel|null $element The model element.
     * @return bool
     * @throws NotImplementedException
     */
    public function contains(?CPModel $element) : bool {
        if (is_null($element)) return false;
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