<?php

if (!class_exists('CliffSerializable')) {

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
             */
            public function getPermanentId() : string|int|null {
                try {
                    throw new NotImplementedException('Please provide an implementation of getPermanentId().');
                } catch (NotImplementedException $exception) {
                    Engine::getLogger()->error($exception);
                }
                return '';
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
                try {
                    throw new NotImplementedException('Please provide an implementation of getPermanentObject().');
                } catch (NotImplementedException $exception) {
                    Engine::getLogger()->error($exception);
                }
                return null;
            }

            /**
             * @inheritDoc
             */
            public function createPermanentObject() : bool {
                try {
                    throw new NotImplementedException('Please provide an implementation of createPermanentObject().');
                } catch (NotImplementedException $exception) {
                    Engine::getLogger()->error($exception);
                }
                return false;
            }

            /**
             * @inheritDoc
             */
            public function updatePermanentObject() : bool {
                try {
                    throw new NotImplementedException('Please provide an implementation of updatePermanentObject().');
                } catch (NotImplementedException $exception) {
                    Engine::getLogger()->error($exception);
                }
                return false;
            }

            /**
             * @inheritDoc
             */
            public function deletePermanentObject() : bool {
                try {
                    throw new NotImplementedException('Please provide an implementation of deletePermanentObject().');
                } catch (NotImplementedException $exception) {
                    Engine::getLogger()->error($exception);
                }
                return false;
            }

            /**
             * @inheritDoc
             */
            public static function getAllPermanentObjects(string $class) : array {
                try {
                    throw new NotImplementedException('Please provide an implementation of getAllPermanentObjects().');
                } catch (NotImplementedException $exception) {
                    Engine::getLogger()->error($exception);
                }
                return [];
            }

            /**
             * @inheritDoc
             */
            public static function getPermanentObjectsWhere(string $field, mixed $value, string $class,
                                                            ?int $limit = null) : array {
                try {
                    throw new NotImplementedException('Please provide an implementation of getPermanentObjectsWhere().');
                } catch (NotImplementedException $exception) {
                    Engine::getLogger()->error($exception);
                }
                return [];
            }

            /**
             * @inheritDoc
             */
            public static function getPermanentObjectsWhereAll(array $map, string $class, ?int $limit = null) : array {
                try {
                    throw new NotImplementedException('Please provide an implementation of getPermanentObjectsWhereAll().');
                } catch (NotImplementedException $exception) {
                    Engine::getLogger()->error($exception);
                }
                return [];
            }

        }
    }

} else {

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
}
?>