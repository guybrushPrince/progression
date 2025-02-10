<?php

/**
 * Class KindOfToken.
 *
 * An abstraction of elements (i.e., tokens and incidents) being token-like.
 *
 * @package progression
 * @subpackge php/engines/tokens
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 *
 */
abstract class KindOfToken extends APermanentObject {

    use Contextable;

    /**
     * The id of the instance.
     * @type int
     * @length BIGINT
     * @var int|null
     * @key
     */
    protected int|null $id;

    /**
     * The state of the token.
     * @type int
     * @var int
     */
    protected int $state = TokenState::CLEAR;

    /**
     * The context of execution (the parameters, values, etc. it knows). It is serialized as string.
     * @type string
     * @length MEDIUM
     * @var string
     */
    protected string $context = '[]';

    /**
     * The corresponding process instance (for reasons of security).
     * @type ProcessInstance
     * @crucial
     * @var ProcessInstance|Closure|null
     */
    protected ProcessInstance|Closure|null $processInstance;

    /**
     * Is the token empty?
     * @return bool
     */
    public function isEmpty() : bool {
        return $this->state === TokenState::CLEAR;
    }

    /**
     * Is the token live?
     * @return bool
     */
    public function isLive() : bool {
        return $this->state === TokenState::LIVE;
    }

    /**
     * Is the token dead?
     * @return bool
     */
    public function isDead() : bool {
        return $this->state === TokenState::DEAD;
    }

    /**
     * Was the token previously live?
     * @return bool
     */
    public function wasPreviouslyLive() : bool {
        return $this->state === TokenState::PREVIOUSLY_LIVE;
    }

    /**
     * Was the token previously dead?
     * @return bool
     */
    public function wasPreviouslyDead() : bool {
        return $this->state === TokenState::PREVIOUSLY_DEAD;
    }

    /**
     * Sets the state of a token permanently.
     * @param int $state The new state of the token.
     * @throws DatabaseError|SecurityException
     * @throws NotImplementedException
     */
    public function setStatePermanently(int $state) : void {
        if ($state !== TokenState::CANCELED) {
            if (!$this->isEmpty()) {
                if ($this->isLive() && $state === TokenState::PREVIOUSLY_LIVE) {
                    // Ok
                } else if ($this->isDead() && $state === TokenState::PREVIOUSLY_DEAD) {
                    // Ok
                } else {
                    throw new SecurityException('Try to set a second state of a ' . get_class($this) . '.');
                }
            }
        }
        $this->state = $state;
        SimplePersistence::instance()->startTransaction();
        $this->updatePermanentObject();
        SimplePersistence::instance()->endTransaction();

        // We have to inform local states
        if ($state === TokenState::LIVE || $state === TokenState::DEAD) {
            if ($this instanceof Token) $localStates = LocalState::getPermanentObjectsWhere('inTokens', $this, LocalState::class);
            else $localStates = LocalState::getPermanentObjectsWhere('inIncidents', $this, LocalState::class);;
            foreach ($localStates as $localState) $localState->inform();
        }
    }

}
?>