<?php

/**
 * Interface IProgressionSerializable.
 *
 * @package progression
 * @subpackge php/engine/serialize
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 */
interface IProgressionSerializable {

    /**
     * Returns the permanent id of an object.
     * @return string|int
     */
    public function getPermanentId() : string|int;

    /**
     * Returns the permanent class of an object.
     * @return string
     */
    public function getPermanentClass() : string;

    /**
     * Retain a permanent object.
     * @param string|int $id The id of the object.
     * @param string $class The class of the object.
     * @return IProgressionSerializable|null
     * @throws Exception
     */
    public static function getPermanentObject(string|int $id, string $class) : ?IProgressionSerializable;

    /**
     * Create the object permanently.
     * @return bool
     */
    public function createPermanentObject() : bool;

    /**
     * Update the object permanently.
     * @return bool
     */
    public function updatePermanentObject() : bool;

    /**
     * Delete the object permanently.
     * @return bool
     */
    public function deletePermanentObject() : bool;

    /**
     * Get all objects of the type.
     * @param string $class The class of the object.
     * @return IProgressionSerializable[]
     */
    public static function getAllPermanentObjects(string $class) : array;

    /**
     * A standard interface to get all objects with a given property.
     * @param string $field The property.
     * @param mixed $value The value.
     * @param string $class The class of the object.
     * @param int|null $limit The maximal number of results.
     * @return IProgressionSerializable[]
     */
    public static function getPermanentObjectsWhere(string $field, mixed $value, string $class,
                                                    ?int $limit = null) : array;

    /**
     * A standard interface to get all objects with the given properties.
     * @param array $map The key-value mapping.
     * @param string $class The class of the object.
     * @param int|null $limit The maximal number of results.
     * @return IProgressionSerializable[]
     */
    public static function getPermanentObjectsWhereAll(array $map, string $class, ?int $limit = null) : array;
}
?>