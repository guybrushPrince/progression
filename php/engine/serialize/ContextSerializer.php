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
    private const SERIALIZED_COMPRESSED = '__compressed';

    /**
     * Serializes a given context.
     * @param array $context The context to serialize.
     * @param bool $compressed Whether an object id shall be compressed to a string, or not.
     * @param bool $stringStyle Whether an object shall be stored in a single string, or not.
     * @return string
     * @throws UnserializableObjectException
     */
    public static function serialize(array $context, bool $compressed = false, bool $stringStyle = false) : string {
        $context = self::serializeObjects($context, $compressed, $stringStyle);
        return CPTools::jsonEncode($context);
    }

    /**
     * Serializes the objects of a given context.
     * @param array $context The context to serialize all objects within.
     * @param bool $compressed Whether an object id shall be compressed to a string, or not.
     * @param bool $stringStyle Whether an object shall be stored in a single string, or not.
     * @return array
     * @throws UnserializableObjectException
     */
    private static function serializeObjects(array $context, bool $compressed = false, bool $stringStyle = false) : array {
        foreach ($context as $field => $value) {
            //if (is_int($field)) $field = 'i' . $field;
            if ($value instanceof IProgressionSerializable) {
                $context[$field] = self::serializeObject($value, $compressed, $stringStyle);
            } else if ($value instanceof ContextVariable) {
                $context[$field] = '{{' . $value->getName() . '}}';
            } else if (is_object($value)) {
                throw new UnserializableObjectException('Object of class ' . get_class($value) . ' cannot be serialized.');
            } else if (is_array($value)) {
                $context[$field] = self::serializeObjects($value, $compressed, $stringStyle);
            }
        }
        return $context;
    }

    /**
     * Serializes a given object.
     * @param IProgressionSerializable $object The object to serialize.
     * @param bool $compressed Whether an object id shall be compressed to a string, or not.
     * @param bool $stringStyle Whether an object shall be stored in a single string, or not.
     * @return array|string
     */
    private static function serializeObject(IProgressionSerializable $object, bool $compressed = false,
                                            bool $stringStyle = false) : array|string {
        $obj = [
            self::SERIALIZED_ID => $object->getPermanentId(),
            self::SERIALIZED_CLASS => $object->getPermanentClass()
        ];
        if ($compressed && is_int($object->getPermanentId())) {
            $obj[self::SERIALIZED_COMPRESSED] = true;
            $obj[self::SERIALIZED_ID] = base_convert($obj[self::SERIALIZED_ID], 10, 36);
        }
        if ($stringStyle) $obj = $obj[self::SERIALIZED_CLASS] . '<::>' . $obj[self::SERIALIZED_ID];
        return $obj;
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
            $context = CPTools::jsonDecode($context);
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
                    $id = $value[self::SERIALIZED_ID];
                    if (array_key_exists(self::SERIALIZED_COMPRESSED, $value) &&
                        $value[self::SERIALIZED_COMPRESSED]) {
                        $id = base_convert($id, 36, 10);
                    }
                    $context[$field] = self::deserializeObject($id, $value[self::SERIALIZED_CLASS]);
                } else {
                    $context[$field] = self::deserializeObjects($value);
                }
            } else if (is_string($value) && strpos($value, '<::>') > 0) {
                $id = substr($value, 0, strpos($value, '<::>'));
                $clazz = substr($value, strpos($value, '<::>') + 4);
                $id = base_convert($id, 36, 10);
                $context[$field] = self::deserializeObject($id, $clazz);
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