<?php

namespace RuleHook\Core\Api;

if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Custom exception class for RuleHook API errors
 */
class Exception extends \Exception
{
    /**
     * @var array|null The raw API response data if available
     */
    protected $responseData;

    /**
     * @param  string  $message  Error message
     * @param  int  $code  Error code
     * @param  \Throwable|null  $previous  Previous exception
     * @param  array|null  $responseData  Raw API response data
     */
    public function __construct($message, $code = 0, ?\Throwable $previous = null, $responseData = null)
    {
        parent::__construct($message, $code, $previous);
        $this->responseData = $responseData;
    }

    /**
     * Get the raw API response data if available
     *
     * @return array|null
     */
    public function getResponseData()
    {
        return $this->responseData;
    }
}
