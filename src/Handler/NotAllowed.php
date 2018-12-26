<?php

namespace Toolkit\Error\Handler;

use PhpComp\Http\Message\Body;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Toolkit\Error\DetermineContentTypeTrait;
use UnexpectedValueException;

/**
 * Class NotAllowed
 * @package Toolkit\Error\Handler
 * @from Slim 3
 */
class NotAllowed
{
    use DetermineContentTypeTrait;

    /**
     * Invoke error handler
     * @param  ServerRequestInterface $request The most recent Request object
     * @param  ResponseInterface $response The most recent Response object
     * @param  string[] $methods Allowed HTTP methods
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $methods)
    {
        if ($request->getMethod() === 'OPTIONS') {
            $status = 200;
            $contentType = 'text/plain';
            $output = $this->renderPlainNotAllowedMessage($methods);
        } else {
            $status = 405;
            $contentType = $this->determineContentType($request);
            switch ($contentType) {
                case 'application/json':
                    $output = $this->renderJsonNotAllowedMessage($methods);
                    break;

                case 'text/xml':
                case 'application/xml':
                    $output = $this->renderXmlNotAllowedMessage($methods);
                    break;

                case 'text/html':
                    $output = $this->renderHtmlNotAllowedMessage($methods);
                    break;
                default:
                    throw new UnexpectedValueException('Cannot render unknown content type ' . $contentType);
            }
        }

        $body = new Body();
        $body->write($output);
        $allow = \implode(', ', $methods);

        return $response
            ->withStatus($status)
            ->withHeader('Content-type', $contentType)
            ->withHeader('Allow', $allow)
            ->withBody($body);
    }

    /**
     * Render PLAIN not allowed message
     * @param  array $methods
     * @return string
     */
    protected function renderPlainNotAllowedMessage($methods)
    {
        $allow = implode(', ', $methods);

        return 'Allowed methods: ' . $allow;
    }

    /**
     * Render JSON not allowed message
     * @param  array $methods
     * @return string
     */
    protected function renderJsonNotAllowedMessage($methods)
    {
        $allow = implode(', ', $methods);

        return '{"message":"Method not allowed. Must be one of: ' . $allow . '"}';
    }

    /**
     * Render XML not allowed message
     * @param  array $methods
     * @return string
     */
    protected function renderXmlNotAllowedMessage($methods)
    {
        $allow = implode(', ', $methods);

        return "<root><message>Method not allowed. Must be one of: $allow</message></root>";
    }

    /**
     * Render HTML not allowed message
     * @param  array $methods
     * @return string
     */
    protected function renderHtmlNotAllowedMessage($methods)
    {
        $allow = implode(', ', $methods);
        $output = <<<END
<html>
    <head>
        <title>Method not allowed</title>
        <style>
            body{
                margin:0;
                padding:30px;
                font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;
            }
            h1{
                margin:0;
                font-size:48px;
                font-weight:normal;
                line-height:48px;
            }
        </style>
    </head>
    <body>
        <h1>Method not allowed</h1>
        <p>Method not allowed. Must be one of: <strong>$allow</strong></p>
    </body>
</html>
END;

        return $output;
    }
}
