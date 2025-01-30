<?php

if (class_exists('CliffSerializable')) {

    /**
     * Class APermanentObject.
     *
     * The Coast implementation of the abstract permanent object class.
     *
     * @package progression
     * @subpackge php/permanent
     *
     * @version 1.0.0
     * @author Dr. Dipl.-Inf. Thomas M. Prinz
     */
    abstract class APermanentObject extends CliffSerializable implements IProgressionSerializable {

        use CoastSerializableStandard;

    }
} else {

    if (!class_exists('APermanentObject')) {
        /**
         * Class APermanentObject.
         *
         * An implementation that throws exceptions.
         *
         * @package progression
         * @subpackge php/permanent
         *
         * @version 1.0.0
         * @author Dr. Dipl.-Inf. Thomas M. Prinz
         */
        abstract class APermanentObject implements IProgressionSerializable {

            /**
             * @inheritDoc
             * @throws NotImplementedException
             */
            public function getPermanentId() : string|int {
                throw new NotImplementedException('Please provide an implementation of getPermanentId().');
            }

            /**
             * @inheritDoc
             */
            public function getPermanentClass() : string {
                return get_class($this);
            }

            /**
             * @inheritDoc
             */
            public static function getPermanentObject(int|string $id, string $class): ?IProgressionSerializable {
                throw new NotImplementedException('Please provide an implementation of getPermanentObject().');
            }

            /**
             * @inheritDoc
             * @throws NotImplementedException
             */
            public function createPermanentObject() : bool {
                throw new NotImplementedException('Please provide an implementation of createPermanentObject().');
            }

            /**
             * @inheritDoc
             * @throws NotImplementedException
             */
            public function updatePermanentObject() : bool {
                throw new NotImplementedException('Please provide an implementation of updatePermanentObject().');
            }

            /**
             * @inheritDoc
             * @throws NotImplementedException
             */
            public static function getPermanentObjectsWhere(string $field, mixed $value, string $class) : array {
                throw new NotImplementedException('Please provide an implementation of getPermanentObjectsWhere().');
            }

        }
    }
}
?>