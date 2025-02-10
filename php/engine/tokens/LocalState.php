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
     * The user who initiated the process instance.
     * @type string
     * @length 255
     * @nullable
     * @var string|null
     */
    private string|null $user = null;

    /**
     * The group, in which the user initiated the process instance.
     * @type string
     * @length 255
     * @nullable
     * @var string|null
     */
    private string|null $group = null;

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
    private ProcessInstance|Closure|null $processInstance;

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
                Engine::getLogger()->debug('Token', $token->getPermanentId(), 'is live');
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
        if ($this->getState() !== TokenState::CLEAR) {
            throw new SecurityException('The system tries to execute the same node twice.');
        }

        $executable = $this->isExecutable();

        Engine::getLogger()->debug('Executability of', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId(), ':', $executable);

        if (is_null($executable)) return; // There is an empty token thus the node is not executable.

        // The node is executable or skippable. We have to join the contexts.
        $nextContext = array_reduce($this->getInTokens(), function (array $context, Token $in) : array {
            if ($in->isLive()) $context = $in->getDeserializedContext() + $context;
            return $context;
        }, []);
        // ... also the contexts of the incoming incidents.
        $nextContext = array_reduce($this->getInIncidents(), function (array $context, Incident $in) : array {
            // Just use the context if the incident really happened.
            if ($in->isLive()) $context = $in->getDeserializedContext() + $context;
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
        $this->setContextPermanently($nextContext);
        $this->setStatePermanently($executable ? TokenState::LIVE : TokenState::PREVIOUSLY_DEAD);

        Engine::getLogger()->debug('Set context of', $this->getPermanentId(), 'to', $nextContext);

        if ($executable) {
            // Execute the node if it represents a task.
            if ($this->getNode() instanceof CPTask) {
                $async = true;
                Engine::getLogger()->debug('Register', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId(), 'to be executed');
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
            $this->setStatePermanently(TokenState::PREVIOUSLY_DEAD);
            foreach ($this->getOutTokens() as $outToken) {
                $outToken->setContextPermanently($nextContext);
                $outToken->setStatePermanently(TokenState::DEAD);
                Engine::getLogger()->debug('Set outgoing token', $outToken->getPermanentId(), 'of', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId(), 'to', $outToken->getState());
            }
            foreach ($this->getOutIncidents() as $outIncident) {
                $outIncident->setContextPermanently([]);
                $outIncident->setStatePermanently(TokenState::DEAD);
                Engine::getLogger()->debug('Set outgoing event', $outIncident->getPermanentId(), 'of', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId(), 'to', $outIncident->getState());
            }
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

        $isXOR = ($this instanceof CPXORGateway);

        // Check the conditional tokens.
        foreach ($nonDefault as $outToken) {
            $outToken->setContextPermanently($context);
            if ((!$isXOR || !$oneLive) && $outToken->isFulfilled($context)) {
                $oneLive = true;
                Engine::getLogger()->debug('Set conditional outgoing token', $outToken->getPermanentId(), 'of', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId(), 'to', TokenState::LIVE);
                $outToken->setStatePermanently(TokenState::LIVE);
            } else {
                Engine::getLogger()->debug('Set conditional outgoing token', $outToken->getPermanentId(), 'of', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId(), 'to', TokenState::DEAD);
                $outToken->setStatePermanently(TokenState::DEAD);
            }
        }
        // Set the unconditional tokens to LIVE if no token was executed yet.
        if (!$oneLive) {
            foreach ($default as $outToken) {
                if ($isXOR && $oneLive) $newState = TokenState::DEAD;
                else $newState = TokenState::LIVE;
                $oneLive = true;
                Engine::getLogger()->debug('Set unconditional outgoing token', $outToken->getPermanentId(), 'of', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId(), 'to', $newState);
                $outToken->setContextPermanently($context);
                $outToken->setStatePermanently($newState);
            }
        } else {
            foreach ($default as $outToken) {
                Engine::getLogger()->debug('Set unconditional outgoing token', $outToken->getPermanentId(), 'of', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId(), 'to', TokenState::DEAD);
                $outToken->setContextPermanently($context);
                $outToken->setStatePermanently(TokenState::DEAD);
            }
        }

        // Throw incidents.
        foreach ($this->getOutIncidents() as $outIncident) {
            Engine::getLogger()->debug('Set outgoing event', $outIncident->getPermanentId(), 'of', $this->getPermanentId(), get_class($this->getNode()), $this->getNode()->getPermanentId(), 'to', TokenState::LIVE);
            $outIncident->setContextPermanently($context);
            $outIncident->setStatePermanently(TokenState::LIVE);

            $receiver = $outIncident->getReceiver();
            Engine::getLogger()->debug('Outgoing event', $outIncident->getPermanentId(), 'with', $receiver ? $receiver->getPermanentId() : null, $receiver ? get_class($receiver) : null);
            if ($receiver) {
                if ($receiver instanceof CPStartEvent) {
                    // Create a new process instance
                    $processModels = CPProcessModel::getPermanentObjectsWhere('elements', $receiver, CPProcessModel::class);
                    foreach ($processModels as $processModel) {
                        // Create a new instance of the corresponding process model.
                        Engine::getLogger()->log('Instantiate', $processModel->getPermanentId(), 'caused by event', $outIncident->getPermanentId(), 'in process instance', $this->getProcessInstance()->getPermanentId());
                        // Create a copy of the incident in the context of the new instance
                        $copyIncident = new Incident();
                        $copyIncident->setState(TokenState::CLEAR);
                        $copyIncident->setType($outIncident->getType());
                        $copyIncident->setContext($outIncident->getContext());
                        $copyIncident->setSender($outIncident->getSender());
                        $copyIncident->setReceiver($outIncident->getReceiver());
                        $copyIncident->setProcessInstance($outIncident->getProcessInstance());
                        SimplePersistence::instance()->startTransaction();
                        $copyIncident->createPermanentObject();
                        SimplePersistence::instance()->endTransaction();
                        Engine::instance()->instantiate($processModel, $copyIncident, $this->getProcessInstance());
                        $outIncident->setStatePermanently(TokenState::PREVIOUSLY_LIVE);
                    }
                } else if ($receiver instanceof CPIntermediateEvent || $receiver instanceof CPEndEvent) {
                    // Is there a callee?
                    $callee = $this->searchCallee($this->getProcessInstance(), $receiver);
                    Engine::getLogger()->log('Inform', array_map(function (ProcessInstance $instance) {
                        return $instance->getPermanentId();
                    }, $callee), 'from', $this->getProcessInstance()->getPermanentId(), 'at', $this->getNode()->getPermanentId());
                    if ($callee) {
                        // Get the corresponding catch-incident
                        $catching = Incident::getPermanentObjectsWhereAll([
                            'processInstance' => array_values($callee),
                            'receiver' => $receiver
                        ], Incident::class);
                        // Set the catching incidents to live
                        foreach ($catching as $catch) {
                            Engine::getLogger()->debug('Set external outgoing event', $catch->getPermanentId(), $callee, 'to', TokenState::LIVE);
                            $catch->setContextPermanently($context);
                            $catch->setStatePermanently(TokenState::LIVE);
                        }
                    }
                }
            }
        }
    }

    /**
     * Search a fitting callee process instance.
     * @param ProcessInstance $instance The current process instance.
     * @param CPIntermediateEvent $recipient The recipient to search.
     * @param ProcessInstance[] $callee (internal) The collected callee.
     * @return ProcessInstance[]
     * @throws NotImplementedException
     */
    private function searchCallee(ProcessInstance $instance, CPIntermediateEvent $recipient,
                                  array &$callee = [], array &$search = []) : array {
        if (in_array($instance->getPermanentId(), $search)) return $callee;
        $search[] = $instance->getPermanentId();

        if ($instance->getProcessModel()->contains($recipient)) {
            $callee[$instance->getPermanentId()] = $instance;
        }
        if ($instance->getCallee()) {
            $this->searchCallee($instance->getCallee(), $recipient, $callee, $search);
        }
        foreach ($instance->getInteractions() as $interaction) {
            $this->searchCallee($interaction, $recipient, $callee, $search);
        }
        return $callee;
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
        $this->setStatePermanently(TokenState::PENDING);

        // Execute it.
        $node = $this->getNode();
        $context = $this->getDeserializedContext();
        $newContext = $node->execute($context);

        if ($newContext instanceof PendingResult) {
            SimplePersistence::instance()->startTransaction();
            $newContext->setId($this->getId());
            $context = $newContext->getDeserializedContext() + $context;
            $newContext->setContext(ContextSerializer::serialize($context));
            $newContext->createPermanentObject();
            SimplePersistence::instance()->endTransaction();
            return;
        } else {
            $this->setStatePermanently(TokenState::PREVIOUSLY_LIVE);
            // Inform the output tokens (and incidents).
            $this->setOutputTokens($newContext);
        }
    }

    /**
     * Checks if the execution of this local state is terminated.
     * @return bool
     * @throws DatabaseError
     * @throws NotImplementedException
     * @throws SecurityException
     * @throws UnserializableObjectException
     */
    public function isTerminated() : bool {
        if ($this->getState() !== TokenState::PENDING) {
            throw new SecurityException('Try to check termination of non-pending task.');
        }

        // Check termination.
        $node = $this->getNode();
        $pendingResult = PendingResult::getPermanentObject($this->getPermanentId(), PendingResult::class);

        if (!$pendingResult) $context = $this->getDeserializedContext();
        else $context = $pendingResult->getDeserializedContext();

        $newContext = $node->isTerminated($context);
        if ($newContext instanceof PendingResult) {
            return false;
        } else {
            $this->setStatePermanently(TokenState::PREVIOUSLY_LIVE);
            // Inform the output tokens (and incidents).
            $this->setOutputTokens($newContext);
            return true;
        }
    }

    /**
     * Cancels the local state.
     * @return bool
     * @throws NotImplementedException
     * @throws Exception
     * @throws UnserializableObjectException
     */
    public function cancel() : bool {
        if ($this->getState() !== TokenState::PENDING) return true;
        // Check termination.
        $node = $this->getNode();
        $pendingResult = PendingResult::getPermanentObject($this->getPermanentId(), PendingResult::class);

        if (!$pendingResult) $context = $this->getDeserializedContext();
        else $context = $pendingResult->getDeserializedContext();

        $node->cancel($context);
        return true;
    }

}
?>