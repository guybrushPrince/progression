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
    protected int|null $id;

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

}
?>