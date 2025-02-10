<?php

/**
 * Class EventLog.
 *
 * An event log inspired by the XES standard.
 * https://www.tf-pm.org/resources/xes-standard
 *
 * @package progression
 * @subpackge php/tools
 *
 * @persistent
 * @keyGiven
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 */
class EventLog extends APermanentObject {

    use Contextable;
    use EventLogPersistentTrait;

    /**
     * The event log entry has a unique id.
     * @type int
     * @auto
     * @key
     * @var int
     */
    protected ?int $id;

    /**
     * The process instance but just its id.
     * @type int
     * @length BIGINT
     * @var int
     * @unsigned
     * @crucial
     * @unique log
     */
    protected int $case;

    /**
     * The sender's process model node.
     * @type string
     * @length 255
     * @var string
     * @nullable
     */
    protected string $senderNode;

    /**
     * The sender's model instance.
     * @type int
     * @length BIGINT
     * @var int
     * @unsigned
     * @nullable
     */
    protected int $senderInstance;

    /**
     * The sender's process model instance.
     * @type int
     * @length BIGINT
     * @var int
     * @unsigned
     * @nullable
     */
    protected int $senderProcessInstance;

    /**
     * The incoming state of the sender.
     * @type int
     * @var int
     * @unsigned
     * @nullable
     */
    protected int $incomingState;

    /**
     * The receiving activity.
     * @type string
     * @length 255
     * @var string
     * @crucial
     * @unique log
     */
    protected string $activityNode;

    /**
     * The receiving activity instance.
     * @type int
     * @length BIGINT
     * @var int
     * @unsigned
     * @crucial
     */
    protected int $activityInstance;

    /**
     * The current state of the activity.
     * @type int
     * @length BIGINT
     * @var int
     * @unsigned
     * @crucial
     * @unique log
     */
    protected int $state;

    /**
     * The timestamp of the event log.
     * @type int
     * @length BIGINT
     * @var int
     * @unsigned
     * @crucial
     */
    protected int $concreteTimestamp;

    /**
     * The execution context.
     * @type string
     * @var string
     * @length MEDIUM
     * @nullable
     */
    protected string $context = '[]';

    /**
     * Constructor.
     * @param int|null $case The case.
     * @param string|null $activityNode The activity node.
     * @param int|null $state The state.
     * @param string|array|null $context The context.
     * @param int|null $incomingState The incoming state.
     * @param string|null $senderNode The sender node.
     * @param int|null $activityInstance The activity instance.
     * @param int|null $senderInstance The sender instance.
     * @param int|null $senderProcessInstance The sender's process instance.
     * @throws UnserializableObjectException
     */
    public function __construct(?int $case = null, ?string $activityNode = null, ?int $state = null,
                                string|array|null $context = null, ?int $incomingState = null,
                                ?string $senderNode = null,
                                ?int $activityInstance = null, ?int $senderInstance = null,
                                ?int $senderProcessInstance = null) {
        if (!is_null($case)) $this->case = $case;
        if (!is_null($activityNode)) $this->activityNode = $activityNode;
        if (!is_null($state)) $this->state = $state;
        if (!is_null($context)) {
            if (is_array($context)) $context = ContextSerializer::serialize($context);
            $this->context = $context;
        }
        if (!is_null($incomingState)) $this->incomingState = $incomingState;
        if (!is_null($senderNode)) $this->senderNode = $senderNode;
        if (!is_null($activityInstance)) $this->activityInstance = $activityInstance;
        if (!is_null($senderInstance)) $this->senderInstance = $senderInstance;
        if (!is_null($senderProcessInstance)) $this->senderProcessInstance = $senderProcessInstance;
        $this->concreteTimestamp = intval(microtime(true));
    }

    /**
     * Get the case as process instance.
     * @return ProcessInstance|null
     * @throws Exception
     */
    public function getProcessInstanceObject() : ?ProcessInstance {
        return ProcessInstance::getPermanentObject($this->getCase(), ProcessInstance::class);
    }

    /**
     * Get the sender as object.
     * @return CPNode|null
     * @throws Exception
     */
    public function getSenderObject() : ?CPNode {
        $id = $this->getSenderNode();
        if ($id !== null) return CPNode::getPermanentObject($id, CPNode::class);
        else return null;
    }

    /**
     * Get the sender instance as object.
     * @return LocalState|null
     * @throws Exception
     */
    public function getSenderInstanceObject() : ?LocalState {
        $id = $this->getSenderInstance();
        if ($id !== null) return LocalState::getPermanentObject($id, LocalState::class);
        else return null;
    }

    /**
     * Get the activity as object.
     * @return CPNode|null
     * @throws Exception
     */
    public function getActivityObject() : ?CPNode {
        return CPNode::getPermanentObject($this->getActivityNode(), CPNode::class);
    }

    /**
     * Get the activity instance as object.
     * @return LocalState|null
     * @throws Exception
     */
    public function getActivityInstanceObject() : ?LocalState {
        return LocalState::getPermanentObject($this->getActivityInstance(), LocalState::class);
    }

}
?>