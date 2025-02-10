<?php

/**
 * Class CPModel.
 *
 * Represents a part of a progression model element.
 *
 * @package progression
 * @subpackge php/model
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 */
abstract class CPModel extends APermanentObject {

    /**
     * The id of the flow.
     * @type string
     * @length 255
     * @var string
     * @key
     */
    protected string $id;

    /**
     * Ids of UI elements being related to this model element.
     * @type string
     * @nullable
     * @length 1020
     * @var string|null
     */
    protected ?string $ui = null;


    /**
     * Constructor.
     * @param string|null $id The id of the model element (if available).
     * @param string[]|null $relatedUI A set of related UI elements (if available).
     * @throws UnserializableObjectException
     */
    public function __construct(?string $id = null, ?array $relatedUI = null) {
        if (!is_null($id)) $this->id = $id;
        if (!is_null($relatedUI) && count($relatedUI) > 0) $this->setRelatedUI($relatedUI);
    }

    /**
     * Get the id.
     * @return string
     */
    public function getId() : string {
        return $this->getKey();
    }

    /**
     * Set related UI elements.
     * @param string[] $ui The ids of the UI elements.
     * @return void
     * @throws UnserializableObjectException
     */
    public function setRelatedUI(array $ui) : void {
        $this->setUI(ContextSerializer::serialize($ui));
    }

    /**
     * Get related UI elements.
     * @return string[]
     * @throws Exception
     */
    public function getRelatedUI() : array {
        if ($this->getUI()) {
            $ui = ContextSerializer::deserialize($this->getUI());
        } else $ui = [];
        if (count($ui) === 0) $ui[] = $this->getId();
        return $ui;
    }
}
?>