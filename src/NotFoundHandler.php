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

/**
 * Class NotFoundHandler
 * @package Apatis\Handler\Response
 */
class NotFoundHandler extends ResponseHandlerAbstract implements NotFoundHandlerInterface
{
    /**
     * Render Plain Text Output
     *
     * Arguments just for reference
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return void
     */
    protected function renderPlainText(ServerRequestInterface $request, ResponseInterface $response)
    {
        echo "404 Not Found";
    }

    /**
     * Render XML Output
     *
     * Arguments just for reference
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return void
     */
    protected function renderXML(ServerRequestInterface $request, ResponseInterface $response)
    {
        echo <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root>
<error>
    <code>404</code>
    <message>Not Found</message>
</error>
</root>
XML;
    }

    /**
     * Render JSON Output
     *
     * Arguments just for reference
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return void
     */
    protected function renderJSON(ServerRequestInterface $request, ResponseInterface $response)
    {
        echo json_encode([
            'error' => [
                'message' => 'Not Found'
            ],
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Render HTML Output
     *
     * Arguments just for reference
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return void
     */
    protected function renderHTML(ServerRequestInterface $request, ResponseInterface $response)
    {
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>404 Page Not Found</title>
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
<body class="error-404">
  <div class="wrapper">
    <h1 class="error-title">404</h1>
    <h3 class="error-sub-title">Page Not Found</h3>
    <p class="error-description">The page you have requested was not found.</p>
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
     *
     * @return ResponseInterface
     */
    protected function getOutputResponse(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) : ResponseInterface {
        ob_start();
        switch ($this->determineOutputType()) {
            case self::TYPE_JSON:
                $this->renderJSON($request, $response);
                break;
            case self::TYPE_XML:
                $this->renderXML($request, $response);
                break;
            case self::TYPE_PLAIN:
                $this->renderPlainText($request, $response);
                break;
            default:
                $this->renderHTML($request, $response);
                break;
        }

        $output = ob_get_clean();
        $body = new RequestBody();
        // write handler
        $body->write($output);
        return $response->withStatus(404)->withBody($body);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
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
            ->getOutputResponse($request, $response)
            ->withHeader('Content-Type', $contentType);
    }
}
