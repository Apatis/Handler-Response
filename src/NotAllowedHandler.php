<?php
/**
 * BSD 3-Clause License
 *
 * Copyright (c) 2018, Pentagonal Development
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace Apatis\Handler\Response;

use Apatis\Http\Message\RequestBody;
use Apatis\Http\Message\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class NotAllowedHandler
 * @package Apatis\Handler\Response
 */
class NotAllowedHandler extends ResponseHandlerAbstract implements NotAllowedHandlerInterface
{
    /**
     * Render Plain Text Output
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response just for reference
     * @param array $allowedMethods
     *
     * @return void
     */
    protected function renderPlainText(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $allowedMethods
    ) {
        printf("Method %s is not allowed\r\n", $request->getMethod());
        if ($this->isDisplayError()) {
            printf("Request method must be one of: (%s).\r\n", implode(', ', $allowedMethods));
        }
    }

    /**
     * Render XML Output
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response just for reference
     * @param array $allowedMethods
     *
     * @return void
     */
    protected function renderXML(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $allowedMethods
    ) {
        $message = sprintf('Method %s is not allowed', $request->getMethod());
        $baseSep = str_repeat(' ', 4);
        $sep = str_repeat($baseSep, 2);
        $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
            . "<root>\n"
            . "{$baseSep}<error>\n"
            . "{$sep}<message>{$message}</message>\n";

        if ($this->isDisplayError()) {
            $method = htmlentities($request->getMethod());
            $allowedMethodXML = '';
            foreach ($allowedMethods as $value) {
                $value = htmlentities($value);
                $allowedMethodXML .= "{$baseSep}<method>{$value}</method>\n{$sep}";
            }

            $xml .= "{$sep}<request_method>{$method}</request_method>\n";
            $xml .= "{$sep}<allowed_methods>\n{$sep}{$allowedMethodXML}</allowed_methods>\n";
        }

        $xml .= "{$baseSep}</error>\n</root>";
        echo $xml;
    }

    /**
     * Render JSON Output
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response just for reference
     * @param array $allowedMethods
     *
     * @return void
     */
    protected function renderJSON(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $allowedMethods
    ) {
        $error = [
            'error' => [
                'message' => sprintf('Method %s is not allowed', $request->getMethod()),
            ],
        ];
        if ($this->isDisplayError()) {
            $error['message']['request_method']  = $request->getMethod();
            $error['message']['allowed_methods'] = array_values($allowedMethods);
        }

        echo json_encode($error, JSON_PRETTY_PRINT);
    }

    /**
     * Render HTML Output
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response just for reference
     * @param array $allowedMethods
     * @return void
     */
    protected function renderHTML(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $allowedMethods
    ) {
        $addition = '';
        if ($this->isDisplayError()) {
            $method = $request->getMethod();
            $allowed = implode(', ', $allowedMethods);
            $addition = <<<TAG

    <p class="error-description">Method `{$method}` is not allowed.</p>
    <p class="error-description">Request method must be one of: ({$allowed}).</p>
TAG;
        }
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>405 Method Not Allowed</title>
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
<body class="error-405">
  <div class="wrapper">
    <h1 class="error-title">405</h1>
    <h3 class="error-sub-title">Method Not Allowed</h3>{$addition}
  </div>
</body>
</html>
HTML;
    }

    /**
     * Getting Output For Response
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $allowedMethods
     *
     * @return ResponseInterface
     */
    protected function getOutputResponse(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $allowedMethods
    ) : ResponseInterface {
        ob_start();
        switch ($this->determineOutputType()) {
            case self::TYPE_JSON:
                $this->renderJSON($request, $response, $allowedMethods);
                break;
            case self::TYPE_XML:
                $this->renderXML($request, $response, $allowedMethods);
                break;
            case self::TYPE_PLAIN:
                $this->renderPlainText($request, $response, $allowedMethods);
                break;
            default:
                $this->renderHTML($request, $response, $allowedMethods);
                break;
        }

        $output = ob_get_clean();
        $body = new Stream(fopen('php://temp', 'r+'));
        // write handler
        $body->write($output);
        return $response->withStatus(405)->withBody($body);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $allowedMethods
    ): ResponseInterface {
        $contentType = $this->getContentType();
        // if content type has not been set
        // get it from Request
        if (!$contentType) {
            $contentType = $request->getHeaderLine('Content-Type')?: static::DEFAULT_CONTENT_TYPE;
            $this->setContentType($contentType);
        }

        // clean output buffers
        $this->cleanOutputBuffer();
        return $this
            ->getOutputResponse($request, $response, $allowedMethods)
            ->withHeader('Content-Type', $contentType);
    }
}
