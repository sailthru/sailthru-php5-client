<?php

/**
 * Handles Sailthru Client Exceptions
 */
class Sailthru_Client_Exception extends Exception {
    /**
     * Standardized exception codes.
     */
    const CODE_GENERAL = 1000;
    const CODE_RESPONSE_EMPTY = 1001;
    const CODE_RESPONSE_INVALID = 1002;
}
