<?php

/**
 * Class LocalState.
 *
 * @package progression
 * @subpackge php/engine
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 *
 * @persistent
 */
class LocalState extends APermanentObject {

    use Contextable;
    use LocalStatePersistentTrait;

    /**
     * The id of the instance.
     * @type int
     * @length BIGINT
     * @var int|null
     * @key
     */
    protected int|null $id;

    /**
     * The node it wraps.
     * @crucial
     * @type CPNode
     * @var CPNode|Closure|null
     */
    private CPNode|Closure|null $node;

    /**
     * The context of execution (the parameters, values, etc. it knows). It is serialized as string.
     * @type string
     * @length MEDIUM
     * @var string|null
     * @nullable
     */
    private ?string $context = null;

    /**
     * Whether this local state is in an executable state, or not.
     * @var int
     * @type int
     */
    private int $state = TokenState::CLEAR;

    /**
     * The incoming tokens.
     * @ordered
     * @type [Token
     * @var Token[]|Closure
     */
    private array|Closure $inTokens = [];

    /**
     * The outgoing tokens.
     * @ordered
     * @type [Token
     * @var Token[]|Closure
     */
    private array|Closure $outTokens = [];

    /**
     * Required catching incidents.
     * @type [Incident
     * @var Incident[]|Closure
     */
    private array|Closure $inIncidents = [];

    /**
     * Required throwing incidents.
     * @type [Incident
     * @var Incident[]|Closure
     */
    private array|Closure $outIncidents = [];

    /**
     * The corresponding process instance (for reasons of security).
     * @type ProcessInstance
     * @crucial
     * @var ProcessInstance|Closure|null
     */
    protected ProcessInstance|Closure|null $processInstance;

    /**
     * Set the state of this local state permanently.
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
     * Is this local state executable regarding its incidents?
     * @return bool|null
     * @throws SecurityException
     */
    public function isIncidentReady() : ?bool {
        $oneLive = (count($this->getInIncidents()) === 0);
        Engine::getLogger()->debug('Check event-readiness of', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId());
        foreach ($this->getInIncidents() as $incident) {
            if ($incident->isEmpty()) {
                Engine::getLogger()->debug('Event', $incident->getPermanentId(), 'is empty');
                return null;
            }
            else if ($incident->isLive()) {
                Engine::getLogger()->debug('Event', $incident->getPermanentId(), 'is live');
                $oneLive = true;
            } else if (!$incident->isDead()) { // Previously dead or previously live
                throw new SecurityException('Process node was already executed in this instance. ' . $incident->getState());
            } else {
                Engine::getLogger()->debug('Event', $incident->getPermanentId(), 'is dead');
            }
        }
        Engine::getLogger()->debug('Event-readiness of', $this->getPermanentId(), ': ', $oneLive);
        return $oneLive;
    }

    /**
     * Checks if the given node is skippable in the current local state.
     * @return bool|null
     * @throws SecurityException
     */
    public function isSkippable() : ?bool {
        // We do not need incidents here since if all incoming tokens are dead, there should not be any
        // incident reaching this node. If there is one, it is not important anymore.
        foreach ($this->getInTokens() as $token) {
            if ($token->isEmpty()) return null;
            else if ($token->isLive()) return false;
            else if (!$token->isDead()) { // Previously dead or previously live
                throw new SecurityException('Process node was already executed in this instance.');
            }
        }
        return true;
    }

    /**
     * Checks if the given node is executable in the current local state.
     * @return bool|null
     * @throws SecurityException
     */
    public function isExecutable() : ?bool {
        $oneLive = (count($this->getInTokens()) === 0);
        Engine::getLogger()->debug('Check executability of', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId());
        foreach ($this->getInTokens() as $token) {
            if ($token->isEmpty()) {
                Engine::getLogger()->debug('Token', $token->getPermanentId(), 'is empty');
                return null;
            }
            else if ($token->isLive()) {
                Engine::getLogger()->debug('Token', $token->getPermanentId(), 'is dead');
                $oneLive = true;
            }
            else if (!$token->isDead()) { // Previously dead or previously live
                throw new SecurityException('Process node was already executed in this instance. ' . $token->getState());
            } else {
                Engine::getLogger()->debug('Token', $token->getPermanentId(), 'is dead');
            }
        }
        if ($oneLive) {
            Engine::getLogger()->debug('Token executability of', $this->getPermanentId(), 'is given');
            $oneLive = $this->isIncidentReady();
        }
        return $oneLive;
    }

    /**
     * Change the state of a token.
     * @return void
     * @throws DatabaseError
     * @throws Exception
     */
    public function inform() : void {
        $executable = $this->isExecutable();

        Engine::getLogger()->debug('Executability of', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId(), ':', $executable);

        if (is_null($executable)) return; // There is an empty token thus the node is not executable.

        // The node is executable or skippable. We have to join the contexts.
        $nextContext = array_reduce($this->getInTokens(), function (array $context, Token $in) : array {
            $context += $in->getDeserializedContext();
            return $context;
        }, []);
        // ... also the contexts of the incoming incidents.
        $nextContext = array_reduce($this->getInIncidents(), function (array $context, Incident $in) : array {
            // Just use the context if the incident really happened.
            if ($in->isLive()) $context += $in->getDeserializedContext();
            return $context;
        }, $nextContext);

        // Change the states of the incoming tokens (just to previously live or dead).
        foreach ($this->getInTokens() as $inToken) {
            if ($inToken->isDead()) $inToken->setStatePermanently(TokenState::PREVIOUSLY_DEAD);
            else $inToken->setStatePermanently(TokenState::PREVIOUSLY_LIVE);
            Engine::getLogger()->debug('Set incoming token', $inToken->getPermanentId(), 'of', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId(), 'to', $inToken->getState());
        }
        // ... also for the incoming incidents.
        foreach ($this->getInIncidents() as $inIncident) {
            if ($inIncident->isDead() || $inIncident->isEmpty()) $inIncident->setStatePermanently(TokenState::PREVIOUSLY_DEAD); // There was no incoming incident.
            else $inIncident->setStatePermanently(TokenState::PREVIOUSLY_LIVE);
            Engine::getLogger()->debug('Set incoming event', $inIncident->getPermanentId(), 'of', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId(), 'to', $inIncident->getState());
        }

        // Register the new state of this task.
        $this->setStatePermanently($executable ? TokenState::LIVE : TokenState::PREVIOUSLY_DEAD);
        $this->setContextPermanently($nextContext);

        if ($executable) {
            // Execute the node if it represents a task.
            if ($this->getNode() instanceof CPTask) {
                $async = true;
                Engine::getLogger()->debug('Register', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId(), 'to be executed');
                Engine::instance()->registerExecutable($this);
            } else {
                $async = false;
                Engine::getLogger()->log('Virtually execute ', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId());
                $this->setStatePermanently(TokenState::PREVIOUSLY_LIVE);

                if ($this->getNode() instanceof CPEndEvent) {
                    // Terminate the process instance
                    Engine::instance()->terminate($this->getProcessInstance());
                }
            }

            // If it is just a gateway or event, then execute it.
            if (!$async) $this->setOutputTokens($nextContext); // The context is the same as for the node.

        } else { // The node is skippable (as it is not null and not executable).
            Engine::getLogger()->log('Skip ', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId());
            foreach ($this->getOutTokens() as $outToken) {
                $outToken->setStatePermanently(TokenState::DEAD);
                $outToken->setContextPermanently($nextContext);
                Engine::getLogger()->debug('Set outgoing token', $outToken->getPermanentId(), 'of', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId(), 'to', $outToken->getState());
            }
            foreach ($this->getOutIncidents() as $outIncident) {
                $outIncident->setStatePermanently(TokenState::DEAD);
                $outIncident->setContextPermanently([]);
                Engine::getLogger()->debug('Set outgoing event', $outIncident->getPermanentId(), 'of', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId(), 'to', $outIncident->getState());
            }
            $this->setStatePermanently(TokenState::PREVIOUSLY_DEAD);
        }
    }

    /**
     * Sets the states of the output tokens.
     * @param array $context The context.
     * @return void
     * @throws NotImplementedException|DatabaseError|SecurityException|UnserializableObjectException
     */
    public function setOutputTokens(array $context) : void {
        $nonDefault = array_filter($this->getOutTokens(), function (Token $out) {
            return $out->isConditional();
        });
        $default = array_filter($this->getOutTokens(), function (Token $out) {
            return !$out->isConditional();
        });
        $oneLive = false;

        // Check the conditional tokens.
        foreach ($nonDefault as $outToken) {
            if ($outToken->isFulfilled($context)) {
                $oneLive = true;
                $outToken->setStatePermanently(TokenState::LIVE);
            } else {
                $outToken->setStatePermanently(TokenState::DEAD);
            }
            $outToken->setContextPermanently($context);
            Engine::getLogger()->debug('Set conditional outgoing token', $outToken->getPermanentId(), 'of', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId(), 'to', $outToken->getState());
        }
        // Set the unconditional tokens if no token was executed yet.
        if (!$oneLive) {
            foreach ($default as $outToken) {
                $outToken->setStatePermanently(TokenState::LIVE);
                $outToken->setContextPermanently($context);
                Engine::getLogger()->debug('Set unconditional outgoing token', $outToken->getPermanentId(), 'of', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId(), 'to', $outToken->getState());
            }
        }

        // Throw incidents.
        foreach ($this->getOutIncidents() as $outIncident) {
            $outIncident->setStatePermanently(TokenState::LIVE);
            $outIncident->setContextPermanently($context);
            Engine::getLogger()->debug('Set outgoing event', $outIncident->getPermanentId(), 'of', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId(), 'to', $outIncident->getState());
        }
    }

    /**
     * Execute the local state.
     * @return void
     * @throws DatabaseError
     * @throws NotImplementedException|DatabaseError|UnserializableObjectException|SecurityException
     * @throws Exception
     */
    public function execute() : void {
        Engine::getLogger()->log('Execute', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId());
        // Register the node as previously live to avoid a concurrent execution.
        $this->setStatePermanently(TokenState::PREVIOUSLY_LIVE);

        // Execute it.
        $node = $this->getNode();
        $context = $this->getDeserializedContext();
        $newContext = $node->execute($context);

        // Inform the output tokens (and incidents).
        $this->setOutputTokens($newContext);
    }

}
?>