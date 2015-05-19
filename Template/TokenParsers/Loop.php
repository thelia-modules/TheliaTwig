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

use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig_NodeInterface;
use Twig_Token;
use Twig_Error_Syntax;
use TheliaTwig\Template\Node\Loop as NodeLoop;

/**
 * Class Loop
 * @package TheliaTwig\Template\TokenParsers
 * @author Manuel Raynaud <manu@thelia.net>
 */
class Loop extends \Twig_TokenParser
{

    /**
     * Parses a token and returns a node.
     *
     * @param Twig_Token $token A Twig_Token instance
     *
     * @return Twig_NodeInterface A Twig_NodeInterface instance
     *
     * @throws Twig_Error_Syntax
     */
    public function parse(Twig_Token $token)
    {
        $lineno = $token->getLine();
        $parser = $this->parser;
        $stream = $parser->getStream();
        $parameters = [];
        while (false === $stream->test(Twig_Token::BLOCK_END_TYPE)) {
            $name = $stream->next();
            if ($token->test(Twig_Token::NAME_TYPE)) {
                $stream->expect(Twig_Token::OPERATOR_TYPE, '=');
                $value = $parser->getExpressionParser()->parseExpression();
                $parameters[$name->getValue()] = $value->getAttribute('value');
            }
        }

        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        $body = $parser->subparse(array($this, 'decideEndLoop'), true);
        $stream->expect(Twig_Token::BLOCK_END_TYPE);

        return new NodeLoop($body, $parameters, $lineno, $this->getTag());
    }

    public function decideEndLoop(Twig_Token $token)
    {
        return $token->test(['endloop']);
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'loop';
    }
}
