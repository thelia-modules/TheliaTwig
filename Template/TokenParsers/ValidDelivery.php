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

use TheliaTwig\Template\Node\ValidDelivery as ValidDeliveryNode;

/**
 * Class ValidDelivery
 * @package TheliaTwig\Template\TokenParsers
 * @author Manuel Raynaud <manu@thelia.net>
 */
class ValidDelivery extends \Twig_TokenParser
{

    /**
     * Parses a token and returns a node.
     *
     * @param \Twig_Token $token A Twig_Token instance
     *
     * @return \Twig_NodeInterface A Twig_NodeInterface instance
     *
     * @throws \Twig_Error_Syntax
     */
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();

        $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

        return new ValidDeliveryNode([], [], $lineno, $this->getTag());
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'check_valid_delivery';
    }
}
