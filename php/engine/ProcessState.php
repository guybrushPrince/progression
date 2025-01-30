<?php

/**
 * Enumeration ProcessState.
 *
 * An enumeration describing different state of a process instance.
 *
 * @package progression
 * @subpackge php/engine
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 */
class ProcessState {

    const INITIALIZED = 1;
    const RUNNING     = 2;
    const FINISHED    = 3;
    const ERROR       = 4;

}
?>