<?php

/**
 * Class CPNode.
 *
 * @package progression
 * @subpackge php/model
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 *
 * @persistent
 */
abstract class CPNode extends CPModel implements GraphNode {

    use CPNodePersistentTrait;

    /**
     * The incoming flows into this node.
     * @temporary
     * @var CPFlow[]|null
     */
    private ?array $incoming = null;

    /**
     * The outgoing flow out of this node.
     * @temporary
     * @var CPFlow[]|null
     */
    private ?array $outgoing = null;

    /**
     * The preset nodes of this node.
     * @temporary
     * @var CPNode[]|null
     */
    private ?array $preset = null;

    /**
     * The postset nodes of this node.
     * @temporary
     * @var CPNode[]|null
     */
    private ?array $postset = null;

    /**
     * Get the incoming flows.
     * @return CPFlow[]
     * @throws NotImplementedException
     */
    public function getIncoming() : array {
        if ($this->incoming === null) {
            $this->incoming = CPFlow::getPermanentObjectsWhere('target', $this, CPFlow::class);
        }
        return $this->incoming;
    }

    /**
     * Set the incoming flows.
     * @param CPFlow[] $incoming The incoming flows.
     * @return void
     */
    public function setIncoming(array $incoming) : void {
        $this->incoming = $incoming;
    }

    /**
     * Get the outgoing flows.
     * @return CPFlow[]
     * @throws NotImplementedException
     */
    public function getOutgoing() : array {
        if ($this->outgoing === null) {
            $this->outgoing = CPFlow::getPermanentObjectsWhere('source', $this, CPFlow::class);
        }
        return $this->outgoing;
    }

    /**
     * Set the outgoing flows.
     * @param CPFlow[] $outgoing The outgoing flows.
     * @return void
     */
    public function setOutgoing(array $outgoing) : void {
        $this->outgoing = $outgoing;
    }

    /**
     * Get the preset of this node.
     * @return CPNode[]
     * @throws NotImplementedException
     */
    public function getPreset() : array {
        if ($this->preset === null) {
            $this->preset = array_map(function (CPFlow $in) {
                return $in->getSource();
            }, $this->getIncoming());
        }
        return $this->preset;
    }

    /**
     * Set the preset nodes.
     * @param CPNode[] $preset The preset.
     * @return void
     */
    public function setPreset(array $preset) : void {
        $this->preset = $preset;
    }

    /**
     * Get the postset of this node.
     * @return CPNode[]
     * @throws NotImplementedException
     */
    public function getPostset() : array {
        if ($this->postset === null) {
            $this->postset = array_map(function (CPFlow $out) {
                return $out->getTarget();
            }, $this->getOutgoing());
        }
        return $this->postset;
    }

    /**
     * Set the postset nodes.
     * @param CPNode[] $postset The postset.
     * @return void
     */
    public function setPostset(array $postset) : void {
        $this->postset = $postset;
    }

    /**
     * Prepare the context by e.g. replacing context variables.
     * @param array $context The deserialized context.
     * @param array|null $overallContext (Internal) The original context.
     * @return array
     */
    public static function prepareContext(array $context, ?array $overallContext = null) : array {
        if (!$overallContext) $overallContext = $context;
        foreach ($context as $field => $value) {
            if (is_array($value)) {
                $context[$field] = self::prepareContext($value, $overallContext);
            } else if ($value instanceof ContextVariable) {
                if (array_key_exists($value->getName(), $overallContext)) {
                    $context[$field] = $overallContext[$value->getName()];
                }
            }
        }
        return $context;
    }

}
?>