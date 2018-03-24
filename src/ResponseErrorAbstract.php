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
     * Parameter just for reference
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \Throwable $e
     *
     * @return Run
     */
    protected function getOutputHandler(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \Throwable $e
    ) : Run {
        $whoops = new Run();
        // disable write to output
        $whoops->writeToOutput(false);
        // push handler by Type
        switch ($this->determineOutputType()) {
            case self::TYPE_JSON:
                $whoops->pushHandler([$this, 'renderJson']);
                break;
            case self::TYPE_XML:
                $whoops->pushHandler([$this, 'renderXML']);
                break;
            case self::TYPE_PLAIN:
                $whoops->pushHandler([$this, 'renderPlainText']);
                break;
            default:
                $whoops->pushHandler([$this, 'renderHtml']);
        }

        return $whoops;
    }

    /**
     * Render HTML Output
     *
     * @param \Throwable $e
     * @param Inspector $inspector
     * @param RunInterface $run
     *
     * @return int
     * @throws \Throwable
     */
    public function renderHTML(\Throwable $e, Inspector $inspector, RunInterface $run) : int
    {
        if ($this->isDisplayError()) {
            // if is diplay error, whoops does not allow to render pretty page handler
            // use plain text if it is on cli
            if (PHP_SAPI === 'cli') {
                return $this->renderPlainText($e, $inspector, $run);
            }
            $response = new PrettyPageHandler();
            $response->setRun($run);
            $response->setInspector($inspector);
            $response->setException($e);
            return $response->handle();
        }

        echo <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>500 Internal Server Error</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style type="text/css">
    body {
        background: #f1f1f1;
        color: #444;
        font-family: 'Helvetica', arial,sans-serif;
        line-height: normal;
        vertical-align: baseline;
        font-size: 14px;
    }
    .wrapper {
        text-align:center;
    }
    .wrapper .error-title {
        font-size: 15em;
        margin: 20vh 0 0;
        line-height: 1em;
    }
    .wrapper .error-sub-title {
        font-size: 2em;
        letter-spacing: 1px;
        margin: .1em 0 .2em;
    }
    .wrapper .error-description {
        margin: 1em 0 1.2em;
        font-size: 14px;
    }
  </style>
</head>
<body class="error-500">
  <div class="wrapper">
    <h1 class="error-title">500</h1>
    <h3 class="error-sub-title">Internal Server Error</h3>
    <p class="error-description">There was an error. We are sorry for inconvenience.</p>
  </div>
</body>
</html>
HTML;

        return Handler::DONE;
    }

    /**
     * Render JSON Output
     *
     * @param \Throwable $e
     * @param Inspector $inspector
     * @param RunInterface $run
     *
     * @return int
     */
    public function renderJSON(\Throwable $e, Inspector $inspector, RunInterface $run) : int
    {
        if ($this->isDisplayError()) {
            $response = new WhoopsJsonResponseHandler();
            // do not use JSON API
            $response->setJsonApi(false);
            $response->setRun($run);
            $response->setInspector($inspector);
            $response->addTraceToOutput(true);
            $response->setException($e);
            return $response->handle();
        }

        echo json_encode([
            'error' => [
                'message' => 'There was an error'
            ]
        ], JSON_PRETTY_PRINT);

        return Handler::DONE;
    }

    /**
     * Render XML Output
     *
     * @param \Throwable $e
     * @param Inspector $inspector
     * @param RunInterface $run
     *
     * @return int
     */
    public function renderXML(\Throwable $e, Inspector $inspector, RunInterface $run) : int
    {
        if ($this->isDisplayError()) {
            $response = new XmlResponseHandler();
            $response->setRun($run);
            $response->setInspector($inspector);
            $response->addTraceToOutput(true);
            $response->setException($e);
            return $response->handle();
        }

        echo <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root>
<error>
    <message>There was an error</message>
</error>
</root>
XML;
        return Handler::DONE;
    }

    /**
     * @param \Throwable $e
     * @param Inspector $inspector
     * @param RunInterface $run
     *
     * @return int
     */
    public function renderPlainText(\Throwable $e, Inspector $inspector, RunInterface $run) : int
    {
        if ($this->isDisplayError()) {
            $response = new PlainTextHandler();
            $response->setRun($run);
            $response->setInspector($inspector);
            $response->addTraceToOutput(true);
            $response->setException($e);
            return $response->handle();
        }

        echo "There was an error";
        return Handler::DONE;
    }

    /**
     * Generate Output Handler For Response
     *
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
        // if content type has not been set
        // get it from Request / Response
        if (!$contentType) {
            $contentType = $response->getHeaderLine('Content-Type')?: (
                $request->getHeaderLine('Content-Type')?: static::DEFAULT_CONTENT_TYPE
            );
            $this->setContentType($contentType);
        }

        $this->cleanOutputBuffer();
        $body = new RequestBody();

        $handler = $this->getOutputHandler($request, $response, $e);
        // disable quit
        $handler->allowQuit(false);
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
