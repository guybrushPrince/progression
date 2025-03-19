<?php
/**
 * Interface Graph.
 * <p>The 'graphs' model is an abstraction layer for a graph-like structure. It allows
 * the usage of different graph-specific algorithms.</p>
 * <p>A <i>Graph</i> consists of <i>nodes</i> ({@link Graph::getNodes()}) and <i>edges</i> ({@link Graph::getEdges()}).
 * Graphs can have <i>start nodes</i> ({@link Graph::determineStartNodes()}) and <i>end nodes</i>
 * ({@link Graph::determineEndNodes()}).</p>
 * <p>It is possible to get a DOT representation of the graph with {@link Graph::asDotGraph()}</p>
 *
 * @package rope
 * @subpackage model
 * @link https://www.thinkmind.org/download.php?articleid=soft_v12_n12_2019_10
 *
 * @version 1.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 */
interface Graph {

    /**
     * Get the nodes of the graph.
     * @return GraphNode[]
     */
    public function getNodes() : array;

    /**
     * Get the edges of the graph.
     * @return GraphEdge[]
     */
    public function getEdges() : array;

    /**
     * Get the start nodes of the graph.
     * @return GraphNode[]
     */
    public function determineStartNodes() : array;

    /**
     * Get the end nodes of the graph.
     * @return GraphNode[]
     */
    public function determineEndNodes() : array;

    /**
     * Returns a dot representation of the graph.
     * @param array $properties Some additional properties to configure the output generation.
     * @return string
     */
    public function asDotGraph(array $properties = []) : string;

}
?>