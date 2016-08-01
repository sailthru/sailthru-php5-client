<?php

/**
 * Handles Sailthru Client Exceptions
 */
class Sailthru_Client_Exception extends Exception {
    /**
     * Standardized exception codes.
     */
    const CODE_GENERAL = 1000;
    const CODE_BAD_API_RESPONSE = 1001;
    const CODE_INVALID_JSON = 1002;
}
