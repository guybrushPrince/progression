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
     * A list of variables to be exported.
     * @var string[]
     */
    private array $exports = [];

    /**
     * Constructor.
     * @param string|null $id The id (if available).
     * @param string|null $code The code (if available).
     * @param string[]|null $relatedUI A set of related UI elements (if available).
     * @throws UnserializableObjectException
     */
    public function __construct(?string $id = null, ?string $code = null, ?array $relatedUI = null) {
        parent::__construct($id, $relatedUI);
        if (!is_null($code)) $this->code = $code;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $context) : array|PendingResult {
        $context = self::prepareContext($context);
        $code  = '$context = (function($context) {' . PHP_EOL;
        $code .= '$_variables = array_keys($context);' . PHP_EOL;
        $code .= implode(PHP_EOL, array_map(function (string $key) {
            return '$' . str_replace(' ', '_', $key) . ' = $context["' . $key . '"];';
        }, array_keys($context))) . PHP_EOL;
        $code .= $this->code . PHP_EOL;
        $code .= 'foreach ($this->exports as $_export) {' . PHP_EOL;
        $code .= '    if (in_array($_export, $_variables)) $_exportName = str_replace(\' \', \'_\', $_export);' . PHP_EOL;
        $code .= '    else $_exportName = $_export;' . PHP_EOL;
        $code .= '    $context[$_export] = $$_exportName;' . PHP_EOL;
        $code .= '}' . PHP_EOL;
        $code .= 'return $context;' . PHP_EOL;
        $code .= '})($context);';

        try {
            eval($code);
        } catch (Exception $exception) {

        }

        return $context;
    }

    /**
     * Since a PHP script is executed as is, there is no pending result.
     * @inheritDoc
     */
    public function isTerminated(array $context) : array|PendingResult {
        return $context;
    }

    /**
     * @inheritDoc
     */
    public function cancel(array $context) : void { }

    /**
     * Export the given variable name.
     * @param string $variable The variable.
     * @return void
     */
    public function export(string $variable) : void {
        $this->exports[$variable] = $variable;
    }
}
?>