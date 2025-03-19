<?php

if (!file_exists(__DIR__ . '/../../../cliff/php/loader.php')) {
    include_once __DIR__ . '/../progloader.php';
} else {
    include_once __DIR__ . '/../../../cliff/php/loader.php';
}

include_once __DIR__ . '/../context/SimpleContextBuilder.php';
SimpleContextBuilder::loadContext();

/**
 * Class Engine.
 * The main class of the execution engine.
 *
 * @package progression
 * @subpackge php/engine
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 */
class Engine {

    /**
     * The engines singleton instance.
     * @var Engine|null
     */
    private static ?Engine $instance = null;

    /**
     * A logger for the events.
     * @var CPLogger|null
     */
    private static ?CPLogger $logger = null;

    /**
     * Executable local states.
     * @var LocalState[]
     */
    private array $executable = [];

    /**
     * Constructor.
     * @param CPLogger|null $logger The logger.
     */
    private function __construct(?CPLogger $logger = null) {
        self::$logger = $logger;
    }

    /**
     * Get the singleton instance.
     * @param CPLogger|null $logger The logger (if available).
     * @param int $logLevel The logging level (if available).
     * @return Engine
     */
    public static function instance(?CPLogger $logger = null, int $logLevel = CPLogger::LEVEL_INFO) : Engine {
        if (self::$instance === null) {
            if (!$logger) $logger = EchoLogger::instance('default', $logLevel);
            self::$instance = new Engine($logger);
        }
        return self::$instance;
    }

    /**
     * Performs a single tick of the engine, which executes a single (live and "physical") local state.
     * @return bool
     * @throws NotImplementedException
     */
    public function tick() : bool {
        self::$logger->log('Tick', time());

        $localStates = LocalState::getPermanentObjectsWhere('state', TokenState::PENDING, LocalState::class);
        foreach ($localStates as $localState) {
            $user = $localState->getUser() ?: AAuthentication::SYSTEM_USER;
            $group = $localState->getGroup() ?: AAuthentication::SYSTEM_GROUP;
            if (SimpleAuthentication::login($user, $group)) {
                $localState->isTerminated();
            }
        }

        $localStates = LocalState::getPermanentObjectsWhere('state', TokenState::LIVE, LocalState::class);
        foreach ($localStates as $localState) {
            $user = $localState->getUser() ?: AAuthentication::SYSTEM_USER;
            $group = $localState->getGroup() ?: AAuthentication::SYSTEM_GROUP;
            if (SimpleAuthentication::login($user, $group)) {
                $localState->execute();
            }
        }
        return count($localStates) > 0;
    }

    /**
     * Execute all.
     * @return bool
     * @throws NotImplementedException
     */
    public function executeUntilDone() : bool {
        while ($this->tick());
        return true;
    }

    /**
     * Get all process models (currently, a simple implementation).
     * @return CPProcessModel[]
     * @throws NotImplementedException
     */
    public function getProcessModels() : array {
        return CPProcessModel::getAllPermanentObjects(CPProcessModel::class);
    }

    /**
     * Create a new instance of the given process model.
     * @param CPProcessModel $processModel The process model.
     * @param Incident|null $incident (Optional) incident if given.
     * @param ProcessInstance|null $callee The callee process instance.
     * @param string|null $user The user, who executes the process instance.
     * @param string|null $group The group the user belongs to.
     * @return ProcessInstance
     * @throws DatabaseError
     * @throws NotImplementedException
     * @throws SecurityException
     */
    public function instantiate(CPProcessModel $processModel, ?Incident $incident = null,
                                ?ProcessInstance $callee = null, ?string $user = null,
                                ?string $group = null) : ProcessInstance {

        self::getLogger()->log('Instantiate', $processModel->getPermanentId(), 'with', $incident ? [$incident->getPermanentId(), $incident->getDeserializedContext()] : null, 'by', $user, 'in', $group);

        if (!$user) $user = AAuthentication::SYSTEM_USER;
        if (!$group) $group = AAuthentication::SYSTEM_GROUP;

        // Generate a new process instance.
        $instance = new ProcessInstance();
        $instance->setProcessModel($processModel);
        if ($callee) $instance->setCallee($callee);
        $instance->setUser($user);
        $instance->setGroup($group);
        SimplePersistence::instance()->startTransaction();
        $instance->createPermanentObject();
        SimplePersistence::instance()->endTransaction();

        // Set this instance as interaction of the callee
        if ($callee) {
            $callee->addInteraction($instance);
            SimplePersistence::instance()->startTransaction();
            $callee->updatePermanentObject();
            SimplePersistence::instance()->endTransaction();
        }

        $flowTokens = [];
        $eventIncidents = [];
        $startEvents = [];
        if ($incident) {
            if ($incident->getReceiver()) {
                $receiver = $incident->getReceiver();
                $eventIncidents[$receiver->getPermanentId()] = [ $incident ];
                self::getLogger()->log('Registered', $incident->getPermanentId(), 'with', $receiver->getPermanentId(), 'and', $incident->getDeserializedContext());
            } else {
                $sender = $incident->getSender();
                array_filter($sender->getEventRecipients(), function (CPEvent $model) use ($processModel, &$eventIncidents, $incident) {
                    $within = $processModel->contains($model);
                    if ($within) {
                        $incident->setReceiver($model);
                        SimplePersistence::instance()->startTransaction();
                        $incident->updatePermanentObject();
                        SimplePersistence::instance()->endTransaction();
                        $eventIncidents[$model->getPermanentId()] = [ $incident ];
                        self::getLogger()->log('Registered', $incident->getPermanentId(), 'with', $model->getPermanentId(), 'and', $incident->getDeserializedContext());
                    }
                    return $within;
                });
            }
        }

        // Create the tokens, incidents, and the local states.
        SimplePersistence::instance()->startTransaction();
        array_map(function (CPNode $node) use (&$flowTokens, $instance, &$startEvents, &$eventIncidents,
            $user, $group) : LocalState {
            // Create incoming tokens
            $incomingTokens = array_map(function(CPFlow $flow) use (&$flowTokens, $instance) : Token {
                return $this->getOrCreateToken($flow, $flowTokens, $instance);
            }, $node->getIncoming());
            // Create outgoing tokens
            $outgoingTokens = array_map(function(CPFlow $flow) use (&$flowTokens, $instance) : Token {
                return $this->getOrCreateToken($flow, $flowTokens, $instance);
            }, $node->getOutgoing());

            // Create a new local state.
            $localState = new LocalState();
            $localState->setUser($user);
            $localState->setGroup($group);
            $localState->setInTokens($incomingTokens);
            $localState->setOutTokens($outgoingTokens);
            $localState->setNode($node);
            $localState->setProcessInstance($instance);
            self::getLogger()->debug('Created local state for', get_class($node), $node->getPermanentId(), ' with ', count($incomingTokens), count($outgoingTokens));
            if ($node instanceof CPStartEvent) $startEvents[$node->getPermanentId()] = $localState;

            // Create incoming or outgoing events / incidents.
            if ($node instanceof CPEvent) {
                $incidents = $this->getOrCreateIncident($node, $eventIncidents, $instance);
                if ($node->getDirection() === CPEventDirection::CATCHING) {
                    $localState->setInIncidents($incidents);
                } else {
                    $localState->setOutIncidents($incidents);
                }
            }

            $localState->createPermanentObject();

            self::getLogger()->debug('Instantiated tokens, events, and state for ', get_class($node), $node->getPermanentId());

            return $localState;
        }, $processModel->getNodes());
        SimplePersistence::instance()->endTransaction();

        $allStartEvents = $startEvents;

        // Start the process instance.
        if (!$incident && count($startEvents) >= 2) {
            $nonSpecificStarts = array_filter($startEvents, function (LocalState $startEvent) {
                $node = $startEvent->getNode();
                return $node instanceof CPStartEvent && $node->getType() === CPEventType::NONE;
            });
            if (count($nonSpecificStarts) >= 2) {
                $instance->setStatePermanently(ProcessState::ERROR);
                throw new SecurityException('Process model with multiple start events was instantiated without event.');
            } else $startEvents = $nonSpecificStarts;
        }

        if ($incident) {
            if ($incident->getReceiver()) {
                $posStartEvents = [ $startEvents[$incident->getReceiver()->getPermanentId()] ];
            } else {
                $posStartEvents = array_filter($startEvents, function (LocalState $startEventState) use ($incident) {
                    return $startEventState->getNode()->getType() === $incident->getType();
                });
            }
        } else $posStartEvents = $startEvents;

        if (count($posStartEvents) === 0) {
            $instance->setStatePermanently(ProcessState::ERROR);
            if ($incident)
                throw new SecurityException('Process model has no fitting start event for the given event.');
            else
                throw new SecurityException('Process model has no start event.');
        }

        $startEventState = array_shift($posStartEvents);

        // Set process instance running
        $instance->setStatePermanently(ProcessState::RUNNING);

        if ($startEventState instanceof LocalState) {
            foreach ($startEventState->getInIncidents() as $inIncident) {
                self::getLogger()->log('Set', get_class($inIncident), $inIncident->getPermanentId(), 'of state of', get_class($startEventState->getNode()), $startEventState->getNode()->getPermanentId(), 'to', TokenState::LIVE);
                $inIncident->setStatePermanently(TokenState::LIVE);
            }
        }
        foreach ($allStartEvents as $startEvent) {
            if ($startEvent instanceof LocalState && $startEvent->getPermanentId() !== $startEventState->getPermanentId()) {
                foreach ($startEvent->getInIncidents() as $inIncident) {
                    self::getLogger()->log('Set', get_class($inIncident), $inIncident->getPermanentId(), 'of state of', get_class($startEventState->getNode()), $startEventState->getNode()->getPermanentId(), 'to', TokenState::DEAD);
                    $inIncident->setStatePermanently(TokenState::DEAD);
                }
            }
        }

        return $instance;
    }

    /**
     * Get all process instances.
     * @param int[]|null $states An optional array of states to filter on {@link ProcessState}.
     * @return ProcessInstance[]
     * @throws NotImplementedException
     */
    public function getProcessInstances(?array $states = null) : array {
        if (is_array($states) && count($states) >= 1) {
            return ProcessInstance::getPermanentObjectsWhere('state', $states, ProcessInstance::class);
        } else return ProcessInstance::getAllPermanentObjects(ProcessInstance::class);
    }

    /**
     * Terminate a process instance.
     * @param ProcessInstance $processInstance The process instance to terminate.
     * @return void
     * @throws NotImplementedException
     */
    public function terminate(ProcessInstance $processInstance) : void {
        // Set the instance to finished
        $processInstance->setStatePermanently(ProcessState::FINISHED);
    }

    /**
     * Cancel a process instance.
     * @param ProcessInstance $processInstance The process instance to cancel.
     * @return void
     * @throws DatabaseError
     * @throws NotImplementedException
     * @throws SecurityException
     * @throws UnserializableObjectException
     */
    public function cancel(ProcessInstance $processInstance) : void {
        $states = $processInstance->getInstanceStates();
        $processInstance->setStatePermanently(ProcessState::CANCELED);
        foreach ($states as $state) {
            if ($state->getState() === TokenState::PENDING) {
                // Inform the nodes
                $state->cancel();
            }
            $state->setStatePermanently(TokenState::CANCELED);
        }
    }

    /**
     * Delete a process instance. A running instance will be canceled first.
     * @param ProcessInstance $processInstance The process instance to delete.
     * @return void
     * @throws DatabaseError
     * @throws NotImplementedException
     * @throws SecurityException
     * @throws UnserializableObjectException
     */
    public function delete(ProcessInstance $processInstance) : void {
        if ($processInstance->getState() === ProcessState::RUNNING) {
            $this->cancel($processInstance);
        }
        $states = $processInstance->getInstanceStates();
        foreach ($states as $state) {
            if ($state instanceof LocalState) {
                $pending = PendingResult::getPermanentObject($state->getPermanentId(), PendingResult::class);
                if ($pending) $pending->deletePermanentObject();
            }
            $state->deletePermanentObject();
        }
        $processInstance->deletePermanentObject();
    }

    /**
     * Get an already created token or create a new one.
     * @param CPFlow $flow The flow.
     * @param Token[] $flowTokens The tokens (indexed by the flow id).
     * @param ProcessInstance $instance The process instance.
     * @return Token
     * @throws NotImplementedException
     */
    private function getOrCreateToken(CPFlow $flow, array &$flowTokens, ProcessInstance $instance) : Token {
        if (!array_key_exists($flow->getId(), $flowTokens)) {
            $token = new Token();
            $token->setState(TokenState::CLEAR);
            $token->setProcessInstance($instance);
            $token->setFlow($flow);
            if ($flow->getCondition()) $token->setCondition($flow->getCondition());

            $flowTokens[$flow->getId()] = $token;
            $token->createPermanentObject();
        }
        return $flowTokens[$flow->getId()];
    }

    /**
     * Get an already created incident or create a new one.
     * @param CPEvent $event The event.
     * @param Incident[][] $eventIncidents The created incidents (indexed by the event id).
     * @param ProcessInstance $instance The process instance.
     * @return Incident[]
     * @throws NotImplementedException
     */
    private function getOrCreateIncident(CPEvent $event, array &$eventIncidents, ProcessInstance $instance) : array {
        if (!array_key_exists($event->getPermanentId(), $eventIncidents)) {
            $eventIncidents[$event->getPermanentId()] = [];
            foreach ($event->getEventRecipients() as $recipient) {
                if (!array_key_exists($recipient->getPermanentId(), $eventIncidents[$event->getPermanentId()])) {
                    if ($event->getDirection() !== CPEventDirection::CATCHING) {
                        $incident = $this->createIncident($instance, $event->getType(), $event, $recipient);
                    } else {
                        $incident = $this->createIncident($instance, $event->getType(), $recipient, $event);
                    }
                    $eventIncidents[$event->getPermanentId()][$recipient->getPermanentId()] = $incident;
                }
            }
            foreach ($event->getProcessRecipients() as $recipient) {
                $starts = $recipient->determineStartNodes();
                $starts = array_filter($starts, function (CPStartEvent $start) use ($event) {
                    return $start->getType() === $event->getType();
                });
                foreach ($starts as $start) {
                    if (!array_key_exists($start->getPermanentId(), $eventIncidents[$event->getPermanentId()])) {
                        if ($event->getDirection() !== CPEventDirection::CATCHING) {
                            $incident = $this->createIncident($instance, $event->getType(), $event, $start);
                        } else {
                            $incident = $this->createIncident($instance, $event->getType(), $start, $event);
                        }
                        $eventIncidents[$event->getPermanentId()][$start->getPermanentId()] = $incident;
                    }
                }
            }
            if (count($eventIncidents[$event->getPermanentId()]) === 0) {
                // There are no corresponding recipients. Create a stub incident.
                if ($event->getDirection() !== CPEventDirection::CATCHING) {
                    $incident = $this->createIncident($instance, $event->getType(), $event, null);
                } else {
                    $incident = $this->createIncident($instance, $event->getType(), null, $event);
                }
                $eventIncidents[$event->getPermanentId()][] = $incident;
            }
        }
        return $eventIncidents[$event->getPermanentId()];
    }

    /**
     * Create an incident.
     * @param ProcessInstance $instance The instance.
     * @param int $type The event type.
     * @param CPEvent|null $sender The sender.
     * @param CPEvent|null $receiver The receiver.
     * @return Incident
     * @throws NotImplementedException
     */
    private function createIncident(ProcessInstance $instance, int $type, ?CPEvent $sender,
                                    ?CPEvent $receiver) : Incident {
        $incident = new Incident();
        $incident->setState(TokenState::CLEAR);
        $incident->setProcessInstance($instance);
        $incident->setType($type);

        if ($sender) $incident->setSender($sender);
        if ($receiver) $incident->setReceiver($receiver);
        $incident->createPermanentObject();
        return $incident;
    }


    /**
     * Get the logger registered to the engine.
     * @return CPLogger
     */
    public static function getLogger() : CPLogger {
        return self::$logger;
    }
}
?>