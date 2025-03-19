<?php
/**
 * Trait GraphTrait.
 * <p>The trait <i>GraphTrait</i> provides default implementations for the interface methods
 * <ul>
 *     <li>{@link Graph::determineStartNodes()}</li>
 *     <li>{@link Graph::determineEndNodes()}</li>
 *     <li>{@link Graph::asDotGraph()}</li>
 * </ul>
 * of {@link Graph}.</p>
 * <p>It provides a further method {@link GraphTrait::asJsonGraph()}, which generates a JSON representation of the
 * graph.</p>
 *
 * @package rope
 * @subpackage model
 * @see Graph
 *
 * @version 1.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 */
trait GraphTrait {

    /**
     * @inheritDoc
     */
    public function determineStartNodes() : array {
        // Initialize
        $incoming = [];
        foreach ($this->getNodes() as $n) {
            if ($n instanceof GraphNode) $incoming[$n->getId()] = [];
        }
        // Determine the incoming edges for each node
        foreach ($this->getEdges() as $e) {
            if ($e instanceof GraphEdge) {
                $to = $e->getTo();
                $incoming[$to->getId()][] = $e;
            }
        }
        // Analyze
        $starts = array_filter($this->getNodes(), function (GraphNode $node) use ($incoming) {
            return count($incoming[$node->getId()]) === 0;
        });
        return $starts;
    }

    /**
     * @inheritDoc
     */
    public function determineEndNodes() : array {
        // Initialize
        $outgoing = [];
        foreach ($this->getNodes() as $n) {
            if ($n instanceof GraphNode) $outgoing[$n->getId()] = [];
        }
        // Determine the incoming edges for each node
        foreach ($this->getEdges() as $e) {
            if ($e instanceof GraphEdge) {
                $from = $e->getFrom();
                $outgoing[$from->getId()][] = $e;
            }
        }
        // Analyze
        $ends = array_filter($this->getNodes(), function (GraphNode $node) use ($outgoing) {
            return count($outgoing[$node->getId()]) === 0;
        });
        return $ends;
    }

    /**
     * @inheritDoc
     */
    public function asDotGraph(array $properties = []) : string {
        // Node properties
        $showId = (array_key_exists('show id', $properties) ? $properties['show id'] : false);
        $width = (array_key_exists('width', $properties) ? 'width="' . $properties['width'] . '", ' : '');
        $height = (array_key_exists('height', $properties) ? 'height="' . $properties['height'] . '", ' : '');
        $shape = (array_key_exists('shape', $properties) ? 'shape="' . $properties['shape'] . '", ' : '');
        $fixedSize = (array_key_exists('fixed size', $properties) ? 'fixedsize="' . $properties['fixed size'] . '", ' : '');

        // Graph properties
        $rankSep = (array_key_exists('separation', $properties) ? 'ranksep="' . $properties['separation'] . '";' : '');
        $weight = (array_key_exists('weight', $properties) ? 'weight="' . $properties['weight'] . '";' : '');

        $dot = "digraph EasyGraph {\n";
        $dot .= $rankSep . "\n";
        $dot .= $weight . "\n";
        // Write the nodes
        $dot .= implode("\n", array_map(function (GraphNode $node) use
            ($showId, $width, $height, $shape, $fixedSize) {
            $label = 'label="' . $this->getInternId($node) . '"';
            $before = '';
            $after = '';
            $nodeCode = 'n' . $this->getInternId($node) .
                '[' .
                $width .
                $height .
                $shape .
                $fixedSize .
                $label .
                ']';
            return $before . $nodeCode . $after;
        }, $this->getNodes())) . "\n";

        // Determine outgoing edges
        $outgoing = array_reduce($this->getNodes(), function(array $out, GraphNode $node){
            $out[$this->getInternId($node)] = [];
            return $out;
        }, []);
        foreach ($this->getEdges() as $edge) {
            $outgoing[$this->getInternId($edge->getFrom())][] = $edge;
        }

        // Write the edges
        $dot .= implode("\n", array_map(function (GraphEdge $edge) {
            $before = '';
            $after = '';
            return $before . 'n' . $this->getInternId($edge->getFrom()) . '->' . 'n' . $this->getInternId($edge->getTo()) . ($edge->getCondition() ? '[label="C"]' : '') . $after;
        }, $this->getEdges())) . "\n";
        $dot .= "}";
        return $dot;
    }

    /**
     * Converts the graph into a json graph.
     * @return string
     */
    public function asJsonGraph() : string {
        $nodes = array();
        // Write the nodes
        foreach ($this->getNodes() as $node) {
            if ($node instanceof GraphNode) {
                $nodes[$this->getInternId($node)] = $this->getInternId($node);
            }
        }
        $edges = array();
        // Write the edges
        foreach ($this->getEdges() as $edge) {
            if ($edge instanceof GraphEdge) {
                $edges[] = array(
                    "from" => $this->getInternId($edge->getFrom()),
                    "to" =>   $this->getInternId($edge->getTo())
                );
            }
        }
        $graph = array(
            "nodes" => $nodes,
            "edges" => $edges
        );
        return CPTools::jsonEncode($graph);
    }

    /**
     * @var array
     * @internal
     */
    private $__intern_ids__ = array();

    /**
     * @param GraphPart $obj
     * @return float|int|string
     * @internal
     */
    private function getInternId(GraphPart $obj) : float|int|string {
        if ($obj->getId() < 0) {
            $hash = spl_object_hash($obj);
            if (!key_exists($hash, $this->__intern_ids__)) $this->__intern_ids__[$hash] = count($this->__intern_ids__);
            return $this->__intern_ids__[$hash];
        } else return $obj->getId();
    }
}
?>