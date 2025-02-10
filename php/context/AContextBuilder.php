<?php

/**
 * Class AContextBuilder.
 *
 * @package progression
 * @subpackge php/context
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 */
abstract class AContextBuilder {

    /**
     * Load the context (e.g., the persistence layer, the class context, etc.)
     * @return void
     */
    public static abstract function loadContext() : void;

}
?>