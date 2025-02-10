<?php

/**
 * Class CPExecuteTask.
 *
 * A task that executes some program code in a specific language.
 *
 * @package progression
 * @subpackge php/model
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 *
 * @persistent
 * @inParent
 */
abstract class CPExecuteTask extends CPTask {

    /**
     * The code to execute.
     * @type string
     * @length MEDIUM
     * @var string
     * @crucial
     */
    protected string $code;

}
?>