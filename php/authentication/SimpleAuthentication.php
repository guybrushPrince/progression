<?php

if (class_exists('Authentication')) {

    /**
     * Class SimpleAuthentication.
     *
     * Coast-related implementation of {@link AAuthentication}.
     *
     * @package progression
     * @subpackge php/authentication
     *
     * @version 1.0.0
     * @author Dr. Dipl.-Inf. Thomas M. Prinz
     */
    class SimpleAuthentication extends AAuthentication {

        /**
         * @inheritDoc
         */
        public static function login(string $user, ?string $group = null) : bool {
            if ($user === self::SYSTEM_USER) {
                Persistence::instance()->initiateDefault();
                return true;
            }

            $authentication = new Authentication();
            if (!$authentication->isLoggedIn($user)) {
                $userObj = User::get($user);
                $res = $authentication->login($user, $userObj->getPassword(), $group);

                echo "Login " . $user . ' ' . $res . PHP_EOL;

                if (is_string($res)) {
                    return $authentication->isLoggedIn($user);
                }
                else return false;
            } else return true;
        }

        /**
         * @inheritDoc
         */
        public static function logout(string $user) : bool {
            $authentication = new Authentication();
            if (!$authentication->isLoggedIn($user)) return true;
            else return $authentication->logout($user);
        }
    }
} else {
    if (!class_exists('SimpleAuthentication')) {

        /**
         * Class SimpleAuthentication.
         *
         * General dump implementation of {@link AAuthentication}.
         *
         * @package progression
         * @subpackge php/authentication
         *
         * @version 1.0.0
         * @author Dr. Dipl.-Inf. Thomas M. Prinz
         */
        class SimpleAuthentication extends AAuthentication {

            /**
             * @inheritDoc
             */
            public static function login(string $user, ?string $group = null) : bool {
                return true;
            }

            /**
             * @inheritDoc
             */
            public static function logout(string $user) : bool {
                return true;
            }
        }
    }
}
?>