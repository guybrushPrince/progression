<?php

include_once __DIR__ . '/../engine/exceptions/NotImplementedException.php';
include_once __DIR__ . '/APersistence.php';

if (!class_exists('Persistence')) {
    if (!class_exists('SimplePersistence')) {
        /**
         * Class SimplePersistence.
         *
         * A default error-throwing implementation. TODO: You have to add an ordinary implementation.
         *
         * @package progression
         * @subpackge php/permanent
         *
         * @version 1.0.0
         * @author Dr. Dipl.-Inf. Thomas M. Prinz
         */
        class SimplePersistence extends APersistence {

            /**
             * @inheritDoc
             * @throws NotImplementedException
             */
            public static function instance() : APersistence {
                CPProcessModel::init();
                return new SimplePersistence();
            }

            /**
             * @inheritDoc
             * @throws NotImplementedException
             */
            public function startTransaction() : bool {
                return true;
            }

            /**
             * @inheritDoc
             * @throws NotImplementedException
             */
            public function endTransaction() : bool {
                return true;
            }
        }
    }
} else {

    /**
     * Class SimplePersistence.
     *
     * A standard Coast-environment based implementation.
     *
     * @package progression
     * @subpackge php/permanent
     *
     * @version 1.0.0
     * @author Dr. Dipl.-Inf. Thomas M. Prinz
     */
    class SimplePersistence extends APersistence {

        /**
         * The persistence layer from the Coast environment.
         * @var Persistence|null
         */
        private ?Persistence $persistence = null;

        /**
         * The singleton instance.
         * @var SimplePersistence|null
         */
        private static ?SimplePersistence $singleton = null;

        /**
         * @inheritDoc
         */
        public static function instance() : APersistence {
            if (!self::$singleton) {
                self::$singleton = new SimplePersistence();
                self::$singleton->persistence = Persistence::instance();
            }
            return self::$singleton;
        }

        /**
         * @inheritDoc
         */
        public function startTransaction() : bool {
            $this->persistence->startTransaction();
            return true;
        }

        /**
         * @inheritDoc
         * @throws DatabaseError
         */
        public function endTransaction() : bool {
            return $this->persistence->endTransaction();
        }
    }
}
?>