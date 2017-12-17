<?php
/**
 * MIT License
 *
 * Copyright (c) 2017 Pentagonal Development
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Apatis\Handler\Response;

use Apatis\Http\Message\RequestBody;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Whoops\Exception\Inspector;
use Whoops\Handler\Handler;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\XmlResponseHandler;
use Whoops\Run;
use Whoops\RunInterface;

/**
 * Class ResponseErrorAbstract
 * @package Apatis\Handler\Response
 */
abstract class ResponseErrorAbstract extends ResponseHandlerAbstract
{
    const TYPE_JSON   = 'json';
    const TYPE_PLAIN  = 'plain';
    const TYPE_XML    = 'xml';
    const TYPE_HTML   = 'html';

    /**
     * ResponseErrorAbstract constructor.
     *
     * @param bool $displayError
     */
    public function __construct(bool $displayError = false)
    {
        $this->setDisplayError($displayError);
    }

    /**
     * Clean All Output buffering
     */
    protected function cleanOutputBuffer()
    {
        $level = ob_get_level();
        while ($level > 0) {
            $level--;
            ob_end_clean();
        }
    }

    /**
     * Determine Output Type
     *
     * @return string
     */
    protected function determineOutputType() : string
    {
        $contentType = $this->getContentType();
        $type = self::TYPE_HTML;
        if ($contentType !== '') {
            preg_match(
                '`
                    (?P<json>\/ja?son|js)
                    | (?P<plain>plain)
                    | (?P<xml>application\/xml)
                    | (?P<html>\/html)
                  `xi',
                $contentType,
                $match
            );
            foreach ($match as $key => $value) {
                if (is_string($key)) {
                    return $key;
                }
            }
        }

        return $type;
    }

    /**
     * Get Output
     *
     * @return Run
     */
    protected function getOutputHandler() : Run
    {
        $whoops = new Run();
        $type = $this->determineOutputType();
        if ($this->isDisplayError()) {
            switch ($type) {
                case self::TYPE_JSON:
                    $responseHandler = new JsonResponseHandler();
                    // $responseHandler->addTraceToOutput(true);
                    break;
                case self::TYPE_XML:
                    $responseHandler = new XmlResponseHandler();
                    // $responseHandler->addTraceToOutput(true);
                    break;
                case self::TYPE_PLAIN:
                    $responseHandler = new PlainTextHandler();
                    // $responseHandler->addTraceToOutput(true);
                    break;
                default:
                    $responseHandler = new PrettyPageHandler();
                    break;
            }
        } else {
            $responseHandler = function (\Throwable $e, Inspector $inspector, RunInterface $run) use ($type) {
                switch ($type) {
                    case self::TYPE_JSON:
                        return $this->renderJsonError($e, $inspector, $run);
                    case self::TYPE_XML:
                        return $this->renderXMLError($e, $inspector, $run);
                    case self::TYPE_PLAIN:
                        return $this->renderTextError($e, $inspector, $run);
                    default:
                        return $this->renderHtmlError($e, $inspector, $run);
                }
            };
        }

        $whoops->pushHandler($responseHandler);
        $whoops->allowQuit(false);
        return $whoops;
    }

    /**
     * @param \Throwable $e
     * @param Inspector $inspector
     * @param RunInterface $run
     *
     * @return int
     */
    public function renderHtmlError(\Throwable $e, Inspector $inspector, RunInterface $run) : int
    {
        return Handler::QUIT;
    }

    /**
     * @param \Throwable $e
     * @param Inspector $inspector
     * @param RunInterface $run
     *
     * @return int
     */
    public function renderJsonError(\Throwable $e, Inspector $inspector, RunInterface $run) : int
    {
        echo json_encode([
            'error' => 'There was an error'
        ]);

        return Handler::QUIT;
    }

    /**
     * @param \Throwable $e
     * @param Inspector $inspector
     * @param RunInterface $run
     *
     * @return int
     */
    public function renderXMLError(\Throwable $e, Inspector $inspector, RunInterface $run) : int
    {
        echo '<?xml version="1.0" encoding="utf-8"?>';
        echo "<root><error>There was an error</error></root>";
        return Handler::QUIT;
    }

    /**
     * @param \Throwable $e
     * @param Inspector $inspector
     * @param RunInterface $run
     *
     * @return int
     */
    public function renderTextError(\Throwable $e, Inspector $inspector, RunInterface $run) : int
    {
        echo "There was an error";
        return Handler::QUIT;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \Throwable $e
     *
     * @return ResponseInterface
     */
    protected function generateOutputHandler(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \Throwable $e
    ) : ResponseInterface {

        $contentType = $this->getContentType();
        if (!$contentType) {
            $contentType = $response->getHeaderLine('Content-Type')?: static::DEFAULT_CONTENT_TYPE;
            $this->setContentType($contentType);
        }

        $this->cleanOutputBuffer();
        $body = new RequestBody();

        $handler = $this->getOutputHandler();
        // log
        $this->logThrowable($e);
        // write handler
        $body->write($handler->handleException($e));

        return $response->withBody($body)->withHeader('Content-Type', $contentType);
    }

    /**
     * @param \Throwable $e
     *
     * @return mixed
     */
    abstract protected function logThrowable(\Throwable $e);
}
