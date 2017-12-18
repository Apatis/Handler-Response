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

/**
 * Class ResponseHandlerAbstract
 * @package Apatis\Handler\Response
 */
abstract class ResponseHandlerAbstract extends SetContentTypeHandler implements ResponseHandlerInterface
{
    const TYPE_JSON   = 'json';
    const TYPE_PLAIN  = 'plain';
    const TYPE_XML    = 'xml';
    const TYPE_HTML   = 'html';

    /**
     * @var bool
     */
    protected $displayError = false;

    /**
     * {@inheritdoc}
     */
    public function setDisplayError(bool $displayError)
    {
        $this->displayError = $displayError;
    }

    /**
     * {@inheritdoc}
     */
    public function isDisplayError(): bool
    {
        return $this->displayError;
    }

    /**
     * Clean All Output buffers
     *
     * @return void
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
    public function determineOutputType() : string
    {
        $contentType = $this->getContentType();
        // set default
        $type = self::TYPE_HTML;
        if (is_string($contentType) && trim($contentType) !== ''
            // use regex match parameter
            // sort by priority type
            && preg_match(
                '`
                    (?P<'.self::TYPE_JSON.'>\/ja?son|js)
                    | (?P<'.self::TYPE_PLAIN.'>plain)
                    | (?P<'.self::TYPE_XML.'>application\/xml)
                    | (?P<'.self::TYPE_HTML.'>\/html)
                  `xi',
                $contentType,
                $match
            ) && !empty($match)
        ) {
            foreach ($match as $key => $value) {
                // if key is string that must be output
                if (is_string($key)) {
                    return $key;
                }
            }
        } // else using default self::TYPE_HTML

        return $type;
    }
}
