<?php

namespace Toolkit\Error\Handler;

use Inhere\Http\Body;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UnexpectedValueException;

/**
 * Class ErrorRenderer
 * @package Toolkit\Error\Handler
 * @from Slim 3
 */
class ErrorRenderer extends AbstractError
{
    /**
     * Invoke error handler
     * @param ServerRequestInterface $request The most recent Request object
     * @param ResponseInterface $response The most recent Response object
     * @param \Throwable $e The caught Exception object
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     * @throws UnexpectedValueException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, \Throwable $e)
    {
        $contentType = $this->determineContentType($request);
        switch ($contentType) {
            case 'application/json':
                $output = $this->renderJsonErrorMessage($e);
                break;

            case 'text/xml':
            case 'application/xml':
                $output = $this->renderXmlErrorMessage($e);
                break;

            case 'text/html':
                $output = $this->renderHtmlErrorMessage($e);
                break;

            default:
                throw new UnexpectedValueException('Cannot render unknown content type ' . $contentType);
        }

        $this->writeToErrorLog($e);

        $body = new Body();
        $body->write($output);

        return $response
            ->withStatus(500)
            ->withHeader('Content-type', $contentType)
            ->withBody($body);
    }

    /**
     * Render HTML error page
     * @param \Throwable $e
     * @return string
     */
    protected function renderHtmlErrorMessage(\Throwable $e)
    {
        $type = $e instanceof \Error ? 'Error' : 'Exception';
        $class = \get_class($e);
        $title = 'Application Runtime Error';

        if ($this->displayErrorDetails) {
            $html = '<p>The application could not run because of the following error:</p>';
            $html .= '<h2>Details</h2>';
            $html .= $this->renderHtmlException($e);

            while ($e = $e->getPrevious()) {
                $html .= '<h2>Previous exception</h2>';
                $html .= $this->renderHtmlExceptionOrError($e);
            }
        } else {
            $html = '<p>A website error has occurred. Sorry for the temporary inconvenience.</p>';
        }

        $output = sprintf(<<<EOF
<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<title>%s</title><style>body{margin:0;padding:70px 50px;font: 14px/1.5 Menlo, Monaco, Consolas, 'Courier New', monospace;}
h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}
strong{display:inline-block;width:78px;}
pre{
font: 13px/1.5 Menlo, Monaco, Consolas, 'Courier New', monospace;background-color: #f6f8fa;
border-radius: 3px;padding: 16px;border: 1px solid #dedede;overflow-x: auto;
}
</style></head><body><h1>%s</h1>%s</body></html>
EOF
            ,
            $title,
            $title,
            $html
        );

        return $output;
    }

    /**
     * Render exception as HTML.
     * Provided for backwards compatibility; use renderHtmlExceptionOrError().
     * @param \Throwable $e
     * @return string
     */
    protected function renderHtmlException(\Throwable $e)
    {
        return $this->renderHtmlExceptionOrError($e);
    }

    /**
     * Render exception or error as HTML.
     * @param \Error|\Throwable $e
     * @return string
     */
    protected function renderHtmlExceptionOrError(\Throwable $e)
    {
        $html = sprintf('<div><strong>Exception:</strong> %s</div>', \get_class($e));

        if ($code = $e->getCode()) {
            $html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);
        }

        $html .= sprintf('<div><strong>Message:</strong> %s</div>', \htmlentities($e->getMessage()));

        if ($file = $e->getFile()) {
            $html .= sprintf(
                '<div><strong>Position:</strong> %s line <b>%s</b></div>',
                $file,
                $e->getLine()
            );
        }

        if ($trace = $e->getTraceAsString()) {
            $html .= '<h2>Trace</h2>';
            $html .= sprintf('<pre>%s</pre>', \htmlentities($trace));
        }

        if ($this->options['hideRootPath'] && $this->options['rootPath']) {
            $html = \str_replace($this->options['rootPath'], $this->options['rootPlaceholder'], $html);
        }

        return $html;
    }

    /**
     * Render JSON error
     * @param \Throwable $e
     * @return string
     */
    protected function renderJsonErrorMessage(\Throwable $e)
    {
        $type = $e instanceof \Error ? 'Error' : 'Exception';
        $json = [
            'message' => "Application Runtime Error(from $type)",
        ];

        if ($this->displayErrorDetails) {
            $json['exception'] = [];

            do {
                $json['exception'][] = [
                    'type' => \get_class($e),
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString()),
                ];
            } while ($e = $e->getPrevious());
        }

        return \json_encode($json, JSON_PRETTY_PRINT);
    }

    /**
     * Render XML error
     * @param \Throwable $e
     * @return string
     */
    protected function renderXmlErrorMessage(\Throwable $e)
    {
        $xml = "<error>\n  <message>Application Runtime Error</message>\n";
        if ($this->displayErrorDetails) {
            do {
                $xml .= "  <error>\n";
                $xml .= '    <type>' . \get_class($e) . "</type>\n";
                $xml .= '    <code>' . $e->getCode() . "</code>\n";
                $xml .= '    <message>' . $this->createCdataSection($e->getMessage()) . "</message>\n";
                $xml .= '    <file>' . $e->getFile() . "</file>\n";
                $xml .= '    <line>' . $e->getLine() . "</line>\n";
                $xml .= '    <trace>' . $this->createCdataSection($e->getTraceAsString()) . "</trace>\n";
                $xml .= "  </error>\n";
            } while ($e = $e->getPrevious());
        }
        $xml .= '</error>';

        return $xml;
    }

    /**
     * Returns a CDATA section with the given content.
     * @param  string $content
     * @return string
     */
    private function createCdataSection($content)
    {
        return sprintf('<![CDATA[%s]]>', str_replace(']]>', ']]]]><![CDATA[>', $content));
    }
}
