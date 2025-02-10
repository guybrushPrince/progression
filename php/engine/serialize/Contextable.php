<?php

/**
 * Trait Contextable.
 *
 * @package progression
 * @subpackge php/engine/serialize
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 */
trait Contextable {

    /**
     * Get the deserialized context.
     * @return array
     * @throws Exception
     */
    public function getDeserializedContext() : array {
        return ContextSerializer::deserialize($this->context);
    }

    /**
     * Adds something to the context permanently.
     * @param array $addContext The context to add (as key-value pairs).
     * @return bool
     * @throws DatabaseError|UnserializableObjectException|Exception
     */
    public function addToContextPermanently(array $addContext) : bool {
        return $this->setContextPermanently($addContext + $this->getDeserializedContext());
    }

    /**
     * Removes fields from the context permanently.
     * @param array $fields The fields to remove.
     * @return bool
     * @throws DatabaseError|UnserializableObjectException|Exception
     */
    public function removeFromContextPermanently(array $fields) : bool {
        return $this->setContextPermanently(array_diff_key($this->getDeserializedContext(), array_flip($fields)));
    }

    /**
     * Sets the context of the token permanently.
     * @param array $newContext The new context as key-value pairs.
     * @return bool
     * @throws DatabaseError|UnserializableObjectException
     */
    public function setContextPermanently(array $newContext) : bool {
        $this->context = ContextSerializer::serialize($newContext);
        SimplePersistence::instance()->startTransaction();
        $this->updatePermanentObject();
        return SimplePersistence::instance()->endTransaction();
    }

}
?>