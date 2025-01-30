<?php

/**
 * Class CPPHPExecuteTask.
 *
 * A task that executes PHP code.
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
class CPPHPExecuteTask extends CPExecuteTask {

    use CPPHPExecuteTaskPersistentTrait;

    /**
     * Constructor.
     * @param string|null $id The id (if available).
     * @param string|null $code The code (if available).
     */
    public function __construct(?string $id = null, ?string $code = null) {
        if ($id !== null) $this->id = $id;
        if ($code !== null) $this->code = $code;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $context) : array {
        $code = implode(PHP_EOL, array_map(function (string $key) {
            return '$' . str_replace(' ', '_', $key) . ' = $context["' . $key . '"];';
        }, array_keys($context))) . PHP_EOL;
        $code .= $this->code . PHP_EOL;
        $code .= implode(PHP_EOL, array_map(function (string $key) {
            return '$context["' . $key . '"] = $' . str_replace(' ', '_', $key) . ';';
        }, array_keys($context)));

        eval($code);

        return $context;
    }
}
?>