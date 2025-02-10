<?php

/**
 * Class ContextSerializer.
 *
 * (De-)serializes a given context.
 *
 * @package progression
 * @subpackge php/engine/serialize
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 */
class ContextSerializer {

    private const SERIALIZED_ID = '__id';

    private const SERIALIZED_CLASS = '__class';

    /**
     * Serializes a given context.
     * @param array $context The context to serialize.
     * @return string
     * @throws UnserializableObjectException
     */
    public static function serialize(array $context) : string {
        $context = self::serializeObjects($context);
        return Tools::jsonEncode($context);
    }

    /**
     * Serializes the objects of a given context.
     * @param array $context The context to serialize all objects within.
     * @return array
     * @throws UnserializableObjectException
     */
    private static function serializeObjects(array $context) : array {
        foreach ($context as $field => $value) {
            if ($value instanceof IProgressionSerializable) {
                $context[$field] = self::serializeObject($value);
            } else if ($value instanceof ContextVariable) {
                $context[$field] = '{{' . $value->getName() . '}}';
            } else if (is_object($value)) {
                throw new UnserializableObjectException('Object of class ' . get_class($value) . ' cannot be serialized.');
            } else if (is_array($value)) {
                $context[$field] = self::serializeObjects($value);
            }
        }
        return $context;
    }

    /**
     * Serializes a given object.
     * @param IProgressionSerializable $object The object to serialize.
     * @return array
     */
    private static function serializeObject(IProgressionSerializable $object) : array {
        return [
            self::SERIALIZED_ID => $object->getPermanentId(),
            self::SERIALIZED_CLASS => $object->getPermanentClass()
        ];
    }

    /**
     * Deserializes a given context.
     * @param string|null $context The context to deserialize.
     * @return array
     * @throws Exception
     */
    public static function deserialize(string|null $context) : array {
        if ($context === null) $context = [];
        else {
            $context = Tools::jsonDecode($context);
            $context = self::deserializeObjects($context);
        }
        return $context;
    }

    /**
     * Deserializes all objects in the context.
     * @param array $context The context.
     * @return array
     * @throws Exception
     */
    private static function deserializeObjects(array $context) : array {
        foreach ($context as $field => $value) {
            if (is_array($value)) {
                if (array_key_exists(self::SERIALIZED_ID, $value) &&
                    array_key_exists(self::SERIALIZED_CLASS, $value)) {
                    $context[$field] = self::deserializeObject($value[self::SERIALIZED_ID], $value[self::SERIALIZED_CLASS]);
                } else {
                    $context[$field] = self::deserializeObjects($value);
                }
            } else if (is_string($value) && preg_match('/\{\{.*}}/m', $value)) {
                $context[$field] = new ContextVariable(substr($value, 2, -2));
            }
        }
        return $context;
    }

    /**
     * Deserialize an object.
     * @param string|int $id The id.
     * @param string $clazz The class.
     * @return IProgressionSerializable|null
     * @throws Exception
     */
    private static function deserializeObject(string|int $id, string $clazz) : ?IProgressionSerializable {
        return $clazz::getPermanentObject($id, $clazz);
    }
}
?>