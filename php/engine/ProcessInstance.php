<?php

/**
 * Class ProcessInstance.
 *
 * @package progression
 * @subpackge php/engine
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 *
 * @persistent
 */
class ProcessInstance extends APermanentObject {

    use ProcessInstancePersistentTrait;

    /**
     * The id of the instance.
     * @type int
     * @length BIGINT
     * @var int|null
     * @key
     */
    protected int|null $id = null;

    /**
     * The user who initiated the process instance.
     * @type string
     * @length 255
     * @nullable
     * @var string|null
     */
    protected string|null $user = null;

    /**
     * The group, in which the user initiated the process instance.
     * @type string
     * @length 255
     * @nullable
     * @var string|null
     */
    protected string|null $group = null;

    /**
     * The state of the process instance.
     * @type int
     * @var int
     */
    protected int $state = ProcessState::INITIALIZED;

    /**
     * The process model being executed.
     * @type CPProcessModel
     * @crucial
     * @var CPProcessModel|Closure|null
     */
    protected CPProcessModel|Closure|null $processModel;

    /**
     * A process instance that called (instantiated) this process instance.
     * @type ProcessInstance
     * @nullable
     * @var ProcessInstance|Closure|null
     */
    protected ProcessInstance|Closure|null $callee = null;

    /**
     * Process instances this process instance interacts with (e.g., has instantiated).
     * @type [ProcessInstance
     * @var ProcessInstance[]|Closure
     */
    protected array|Closure $interactions = [];

    /**
     * Sets the state of the process instance permanently.
     * @param int $state The new state.
     * @return void
     * @throws NotImplementedException
     */
    public function setStatePermanently(int $state) : void {
        SimplePersistence::instance()->startTransaction();
        $this->setState($state);
        $this->updatePermanentObject();
        SimplePersistence::instance()->endTransaction();
    }

    /**
     * Get all related state describing the instance.
     * @return (LocalState|Token|Incident)[]
     * @throws NotImplementedException
     */
    public function getInstanceStates() : array {
        return array_merge(
            LocalState::getPermanentObjectsWhere('processInstance', $this, LocalState::class),
            Token::getPermanentObjectsWhere('processInstance', $this, Token::class),
            Incident::getPermanentObjectsWhere('processInstance', $this, Incident::class)
        );
    }

}
?>