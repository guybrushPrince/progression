<?php

/**
 * Class AAuthentication.
 *
 * @package progression
 * @subpackge php/authentication
 *
 * @version 1.0.0
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 */
abstract class AAuthentication {

    public const SYSTEM_USER = '__system_user';

    public const SYSTEM_GROUP = '__system_group';

    /**
     * Login a user.
     * @param string $user The username to login.
     * @param string|null $group An optional group.
     * @return bool
     */
    public abstract static function login(string $user, ?string $group = null) : bool;

    /**
     * Logout a user.
     * @param string $user The username to logout.
     * @return bool
     */
    public abstract static function logout(string $user) : bool;

}
?>