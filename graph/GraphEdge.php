<?php
/**
 * Interface GraphEdge.
 * <p>The 'graphs' model is an abstraction layer for a graph-like structure. It allows
 * the usage of different graph-specific algorithms.</p>
 * <p>A <i>GraphEdge</i> connects two {@link GraphNode}s, one where the edge starts ({@link GraphEdge::getFrom()}) and
 * one where the edge ends ({@link GraphEdge::getTo()}). Furthermore, it can have a {@link Condition}
 * ({@link GraphEdge::getCondition()}).</p>
 *
 * {@inheritdoc}
 *
 * @package rope
 * @subpackage model
 * @link https://www.thinkmind.org/download.php?articleid=soft_v12_n12_2019_10
 * @see GraphPart
 *
 * @version 1.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 */
interface GraphEdge extends GraphPart {

    /**
     * Get the source of the graph edge.
     * @return ?GraphNode
     */
    public function getFrom() : ?GraphNode;

    /**
     * Get the target of the graph edge.
     * @return ?GraphNode
     */
    public function getTo() : ?GraphNode;

    /**
     * Get the condition of the graph edge.
     * @return mixed
     */
    public function getCondition() : mixed;

}
?>