<?php

/**
 * Enumeration TokenState.
 *
 * An enumeration representing the "state" of a token.
 *
 * @author Dr. Dipl.-Inf. Thomas M. Prinz
 * @version 1.0.0
 */
class TokenState {

    const CLEAR           = 1;
    const LIVE            = 2;
    const DEAD            = 3;
    const PREVIOUSLY_LIVE = 4;
    const PREVIOUSLY_DEAD = 5;

}
?>