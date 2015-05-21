<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace TheliaTwig\Template\TokenParsers;

use TheliaTwig\Template\Node\IfLoop as NodeIfLoop;

/**
 * Class IfLoop
 * @package TheliaTwig\Template\TokenParsers
 * @author Manuel Raynaud <manu@thelia.net>
 */
class IfLoop extends \Twig_TokenParser
{

    /**
     * Parses a token and returns a node.
     *
     * @param \Twig_Token $token A \Twig_Token instance
     *
     * @return \Twig_NodeInterface A \Twig_NodeInterface instance
     *
     * @throws \Twig_Error_Syntax
     */
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();
        $parser = $this->parser;
        $stream = $parser->getStream();

        $parameters = $parser->getExpressionParser()->parseHashExpression();

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);
        $body = $parser->subparse(array($this, 'decideEndLoop'), true);
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new NodeIfLoop($body, $parameters, $lineno, $this->getTag());
    }

    public function decideEndLoop(\Twig_Token $token)
    {
        return $token->test(['endifloop']);
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return "ifloop";
    }
}
