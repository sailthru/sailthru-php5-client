<?php

/**
 * Handles Sailthru Client Exceptions
 */
class Sailthru_Client_Exception extends Exception {
    /**
     * Standardized exception codes.
     */
    const CODE_GENERAL = 1000;
    const CODE_RESPONSE_EMPTY = 1002;
    const CODE_RESPONSE_INVALID = 1003;
}
