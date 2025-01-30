<?php

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
     * The tokens in the current context.
     * @var Token[]
     */
    private array $tokens = [];

    /**
     * The incidents in the current context.
     * @var Incident[]
     */
    private array $incidents = [];

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
     * Start the engine with tokens in a live or dead state.
     * @return void
     * @throws NotImplementedException
     */
    public function start() : void {
        $this->refresh();
        self::getLogger()->debug($this->tokens);
        self::getLogger()->debug($this->incidents);
    }

    /**
     * Refreshs the entire engine state.
     * @return void
     * @throws NotImplementedException
     */
    private function refresh() : void {
        $this->tokens = Token::getPermanentObjectsWhere('state', [ TokenState::DEAD, TokenState::LIVE ], Token::class);
        $this->incidents = Incident::getPermanentObjectsWhere('state', [ TokenState::DEAD, TokenState::LIVE ], Incident::class);
        $this->executable = LocalState::getPermanentObjectsWhere('state', TokenState::LIVE, LocalState::class);
    }

    /**
     * Registers a token at the engine / bus.
     * @param Token $token The token to register.
     * @return void
     * @throws NotImplementedException
     */
    public function registerToken(Token $token) : void {
        $this->tokens[$token->getPermanentId()] = $token;
        Engine::getLogger()->debug(get_class($this), 'Registered token', $token->getPermanentId(), $token->getState());
    }

    /**
     * Registers an incident at the engine / bus.
     * @param Incident $incident The incident to register.
     * @return void
     * @throws NotImplementedException
     */
    public function registerIncident(Incident $incident) : void {
        $this->incidents[$incident->getPermanentId()] = $incident;
        Engine::getLogger()->debug(get_class($this), 'Registered event', $incident->getPermanentId(), $incident->getState());
    }

    /**
     * Inform the local states about the token change.
     * @return void
     * @throws NotImplementedException
     */
    public function informLocalStates() : void {
        Engine::getLogger()->debug(get_class($this), 'Inform local states');
        $this->tokens = $this->filterDeadTokens($this->tokens);
        Engine::getLogger()->debug(get_class($this), 'Available tokens', count($this->tokens));
        $copy = $this->tokens + [];
        $this->tokens = [];
        while (count($copy) >= 1) {
            $token = array_shift($copy);
            $localStates = LocalState::getPermanentObjectsWhere('inTokens', $token, LocalState::class);
            Engine::getLogger()->debug(get_class($this), 'Inform', $token->getKey(), $token->getState(), count($localStates));
            foreach ($localStates as $localState) $localState->inform();
            $copy = $this->filterDeadTokens($copy);
        }

        $this->incidents = $this->filterDeadIncidents($this->incidents);
        Engine::getLogger()->debug(get_class($this), 'Available events', count($this->incidents));
        $copy = $this->incidents + [];
        $this->incidents = [];
        while (count($copy) >= 1) {
            $incident = array_shift($copy);
            $localStates = LocalState::getPermanentObjectsWhere('inIncidents', $incident, LocalState::class);
            foreach ($localStates as $localState) $localState->inform();
            $copy = $this->filterDeadIncidents($copy);
        }
    }

    /**
     * Filters all "dead" tokens.
     * @param Token[] $tokens A set of tokens to filter.
     * @return Token[]
     */
    private function filterDeadTokens(array $tokens) : array {
        return array_filter($tokens, function (Token $token) {
            return $token->getState() === TokenState::LIVE || $token->getState() === TokenState::DEAD;
        });
    }

    /**
     * Filters all "dead" incidents.
     * @param Incident[] $incidents A set of incidents to filter.
     * @return Incident[]
     */
    private function filterDeadIncidents(array $incidents) : array {
        return array_filter($incidents, function (Incident $incident) {
            return $incident->getState() === TokenState::LIVE || $incident->getState() === TokenState::DEAD;
        });
    }

    /**
     * Register executable states.
     * @param LocalState $localState The executable state.
     * @return void
     * @throws NotImplementedException
     */
    public function registerExecutable(LocalState $localState) : void {
        $this->executable[$localState->getPermanentId()] = $localState;
        Engine::getLogger()->debug(get_class($this), 'Registered executable', $localState->getPermanentId(), get_class($localState->getNode()), $localState->getNode()->getPermanentId());
    }

    /**
     * Execute a local state (if available).
     * @return bool
     * @throws Exception
     */
    public function executeOne() : bool {
        Engine::getLogger()->debug(get_class($this), 'Execute next');
        if (count($this->executable) === 0 && (count($this->tokens) >= 1 || count($this->incidents) >= 1)) {
            $this->informLocalStates();
        }
        if (count($this->executable) >= 1) {
            $localState = array_shift($this->executable);
            try {
                $localState->execute();
            } catch (DatabaseError $e) {
            } catch (NotImplementedException $e) {
            } catch (SecurityException $e) {
            } catch (UnserializableObjectException $e) {
            }
            return true;
        }
        return false;
    }

    /**
     * Execute all.
     * @return bool
     * @throws DatabaseError
     */
    public function executeAll() : bool {
        while ($this->executeOne()) {

        }
        return true;
    }

    /**
     * Create a new instance of the given process model.
     * @param CPProcessModel $processModel The process model.
     * @param Incident|null $incident (Optional) incident if given.
     * @return void
     * @throws NotImplementedException
     * @throws SecurityException
     * @throws DatabaseError
     */
    public function instantiate(CPProcessModel $processModel, ?Incident $incident = null) : void {

        self::getLogger()->log('Instantiate', $processModel->getKey(), 'with', $incident);

        // Generate a new process instance.
        $instance = new ProcessInstance();
        $instance->setProcessModel($processModel);
        SimplePersistence::instance()->startTransaction();
        $instance->createPermanentObject();
        SimplePersistence::instance()->endTransaction();

        $flowTokens = [];
        $eventIncidents = [];
        $startEvents = [];
        if ($incident) {
            $sender = $incident->getSender();
            array_filter($sender->getEventRecipients(), function (CPEvent $model) use ($processModel, &$eventIncidents, $incident) {
                $within = $processModel->contains($model);
                if ($within) {
                    $eventIncidents[$model->getPermanentId()] = $incident;
                    self::getLogger()->debug('Registered', $incident, 'with', $model);
                }
                return $within;
            });
        }

        // Create the tokens, incidents, and the local states.
        SimplePersistence::instance()->startTransaction();
        array_map(function (CPNode $node) use (&$flowTokens, $instance, &$startEvents, &$eventIncidents) : LocalState {
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
            $localState->setInTokens($incomingTokens);
            $localState->setOutTokens($outgoingTokens);
            $localState->setNode($node);
            $localState->setProcessInstance($instance);
            self::getLogger()->debug('Created local state for', get_class($node), $node->getKey(), ' with ', count($incomingTokens), count($outgoingTokens));
            if ($node instanceof CPStartEvent) $startEvents[$node->getId()] = $localState;

            // Create incoming or outgoing events / incidents.
            if ($node instanceof CPEvent) {
                $incident = $this->getOrCreateIncident($node, $eventIncidents, $instance);
                if ($node->getDirection() === CPEventDirection::CATCHING) {
                    $localState->setInIncidents([ $incident ]);
                } else {
                    $localState->setOutIncidents([ $incident ]);
                }
            }

            $localState->createPermanentObject();

            self::getLogger()->debug('Instantiated tokens, events, and state for ', get_class($node), $node->getKey());

            return $localState;
        }, $processModel->getNodes());
        SimplePersistence::instance()->endTransaction();

        // Start the process instance.
        if (!$incident && count($startEvents) >= 2) {
            $instance->setStatePermanently(ProcessState::ERROR);
            throw new SecurityException('Process model with multiple start events was instantiated without event.');
        }

        if ($incident) {
            $posStartEvents = array_filter($startEvents, function (LocalState $startEventState) use ($incident) {
                return $startEventState->getNode()->getType() === $incident->getType();
            });
        } else $posStartEvents = $startEvents;

        if (count($posStartEvents) === 0) {
            $instance->setStatePermanently(ProcessState::ERROR);
            if ($incident)
                throw new SecurityException('Process model has no fitting start event for the given event.');
            else
                throw new SecurityException('Process model has no start event.');
        }

        $startEventState = array_shift($posStartEvents);

        if ($startEventState instanceof LocalState) {
            foreach ($startEventState->getInIncidents() as $inIncident) {
                $inIncident->setStatePermanently(TokenState::LIVE);
                self::getLogger()->log('Set', get_class($inIncident), $inIncident->getPermanentId(), 'of state of', get_class($startEventState->getNode()), $startEventState->getNode()->getPermanentId(), 'to', TokenState::LIVE);
            }
        }

        // Set process instance running
        $instance->setStatePermanently(ProcessState::RUNNING);

        $startEventState->inform();
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
            if ($flow->getCondition()) $token->setCondition($flow->getCondition());

            $flowTokens[$flow->getId()] = $token;
            $token->createPermanentObject();
        }
        return $flowTokens[$flow->getId()];
    }

    /**
     * Get an already created incident or create a new one.
     * @param CPEvent $event The event.
     * @param Incident[] $eventIncidents The created incidents (indexed by the event id).
     * @param ProcessInstance $instance The process instance.
     * @return Incident
     * @throws NotImplementedException
     */
    private function getOrCreateIncident(CPEvent $event, array &$eventIncidents, ProcessInstance $instance) : Incident {
        if (!array_key_exists($event->getId(), $eventIncidents)) {
            $incident = new Incident();
            $incident->setState(TokenState::CLEAR);
            $incident->setProcessInstance($instance);
            $incident->setType($event->getType());

            $eventIncidents[$event->getId()] = $incident;
            $incident->createPermanentObject();
        }
        return $eventIncidents[$event->getId()];
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