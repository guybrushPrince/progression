<?php

/**
 * Class PendingResult.
 *
 * @package progression
 * @subpackge php/engine/serialize
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 *
 * @persistent
 * @keyGiven
 */
class PendingResult extends APermanentObject {

    use Contextable;
    use PendingResultPersistentTrait;

    /**
     * The id of the instance.
     * @type int
     * @length BIGINT
     * @var int|null
     * @key
     */
    protected int|null $id;

    /**
     * The context of execution (the parameters, values, etc. it knows). It is serialized as string.
     * @type string
     * @length MEDIUM
     * @var string|null
     * @nullable
     */
    private ?string $context = null;

    /**
     * Constructor.
     * @param int|null $id The id.
     * @param array|null $context The context.
     * @throws UnserializableObjectException
     */
    public function __construct(?int $id = null, ?array $context = null) {
        if (!is_null($id)) $this->id = $id;
        if (!is_null($context)) $this->context = ContextSerializer::serialize($context);
    }

}
?>