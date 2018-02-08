<?php
/**
 * Slim Framework (https://slimframework.com)
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace MyLib\Error\Handler;

use Psr\Log\LoggerInterface;

/**
 * Abstract application error handler
 */
abstract class AbstractError extends AbstractHandler
{
    /** @var LoggerInterface */
    private $logger;

    /**
     * @var bool
     */
    protected $displayErrorDetails;

    /**
     * Constructor
     * @param bool $displayErrorDetails Set to true to display full details
     * @param LoggerInterface|null $logger
     */
    public function __construct($displayErrorDetails = false, LoggerInterface $logger = null)
    {
        $this->displayErrorDetails = (bool)$displayErrorDetails;
        $this->logger = $logger;
    }

    /**
     * Write to the error log if displayErrorDetails is false
     * @param \Exception|\Throwable $throwable
     * @return void
     */
    protected function writeToErrorLog($throwable)
    {
//        if ($this->displayErrorDetails) {
//            return;
//        }

        $message = 'Light Cms Error:' . PHP_EOL;
        $message .= $this->renderThrowableAsText($throwable);
        while ($throwable = $throwable->getPrevious()) {
            $message .= PHP_EOL . 'Previous error:' . PHP_EOL;
            $message .= $this->renderThrowableAsText($throwable);
        }

        $message .= PHP_EOL . 'View in rendered output by enabling the "displayErrorDetails" setting.' . PHP_EOL;

        $this->logError($message);
    }

    /**
     * Render error as Text.
     * @param \Exception|\Throwable $throwable
     * @return string
     */
    protected function renderThrowableAsText($throwable)
    {
        $text = sprintf('Type: %s' . PHP_EOL, \get_class($throwable));

        if ($code = $throwable->getCode()) {
            $text .= sprintf('Code: %s' . PHP_EOL, $code);
        }

        if ($message = $throwable->getMessage()) {
            $text .= sprintf('Message: %s' . PHP_EOL, htmlentities($message));
        }

        if ($file = $throwable->getFile()) {
            $text .= sprintf('File: %s' . PHP_EOL, $file);
        }

        if ($line = $throwable->getLine()) {
            $text .= sprintf('Line: %s' . PHP_EOL, $line);
        }

        if ($trace = $throwable->getTraceAsString()) {
            $text .= sprintf('Trace: %s', $trace);
        }

        return $text;
    }

    /**
     * Wraps the error_log function so that this can be easily tested
     * @param $message
     */
    protected function logError($message)
    {
        if ($this->logger) {
            $this->logger->error($message);
        } else {
            error_log($message);
        }
    }
}
