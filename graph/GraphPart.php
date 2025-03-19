<?php
/**
 * Interface GraphPart.
 * <p>The 'graphs' model is an abstraction layer for graph-like structure. It allows
 * the usage of different graph-specific algorithms.</p>
 * <p>A <i>GraphPart</i> is an abstract part of a graph, which has an ide ({@link GraphPart::getId()}).</p>
 *
 * @package rope
 * @subpackage model
 *
 * @version 1.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 */
interface GraphPart {

    /**
     * Get the id of the node.
     * @return int|string|float
     */
    public function getId() : int|string|float;

}
?>