<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018/5/2
 * Time: 下午8:36
 */

namespace Toolkit\Error;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Trait DetermineContentTypeTrait
 * @package Toolkit\Error
 * @from Slim 3
 */
trait DetermineContentTypeTrait
{
    /**
     * Known handled content types
     * @var array
     */
    protected $knownContentTypes = [
        'application/json',
        'application/xml',
        'text/xml',
        'text/html',
    ];

    /**
     * Determine which content type we know about is wanted using Accept header
     * @param ServerRequestInterface $request
     * @return string
     */
    protected function determineContentType(ServerRequestInterface $request)
    {
        $acceptHeader = $request->getHeaderLine('Accept');
        $contentTypes = \array_intersect(\explode(',', $acceptHeader), $this->knownContentTypes);

        if (\count($contentTypes)) {
            return \current($contentTypes);
        }

        // handle +json and +xml specially
        if (\preg_match('/\+(json|xml)/', $acceptHeader, $matches)) {
            $mediaType = 'application/' . $matches[1];

            if (\in_array($mediaType, $this->knownContentTypes, true)) {
                return $mediaType;
            }
        }

        return 'text/html';
    }
}
