<?php

/**
 * Class CPContextTask.
 *
 * A task that adds or replaces context variables.
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
class CPContextTask extends CPTask {

    use CPContextTaskPersistentTrait;
    use Contextable;

    /**
     * The context of execution (the parameters, values, etc. it knows). It is serialized as string.
     * @type string
     * @length MEDIUM
     * @var string|null
     * @nullable
     */
    private ?string $context = null;

    /**
     * The scheme of the allowed context.
     * @type string
     * @length 1000
     * @var string|null
     * @nullable
     */
    private ?string $scheme = null;

    /**
     * Constructor.
     * @param string|null $id The id.
     * @param array|null $context The context as key-value-pairs.
     * @param array|null $scheme The scheme of the context.
     * @param string[]|null $relatedUI A set of related UI elements (if available).
     * @throws UnserializableObjectException
     */
    public function __construct(?string $id = null, ?array $context = null, ?array $scheme = null,
                                ?array $relatedUI = null) {
        parent::__construct($id, $relatedUI);
        if (!is_null($context)) $this->context = ContextSerializer::serialize($context);
        if (!is_null($scheme)) $this->scheme = ContextSerializer::serialize($scheme);
    }

    /**
     * @inheritDoc
     */
    public function execute(array $context) : array|PendingResult {
        $context = self::prepareContext($context);
        if ($this->getContext()) $context = $this->getDeserializedContext() + $context;
        return $context;
    }

    /**
     * @inheritDoc
     */
    public function isTerminated(array $context) : array|PendingResult {
        return $context;
    }

    /**
     * @inheritDoc
     */
    public function cancel(array $context) : void { }
}
?>