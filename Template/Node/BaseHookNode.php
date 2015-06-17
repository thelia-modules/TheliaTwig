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

namespace TheliaTwig\Template\Node;

/**
 * Class BaseHookNode
 * @package TheliaTwig\Template\Node
 * @author Julien Chanséaume <julien@thelia.net>
 */
class BaseHookNode extends \Twig_Node
{
    public function __construct(\Twig_Node $body, \Twig_Node $parameters, $lineno, $tag)
    {
        parent::__construct(['body' => $body, 'parameters' => $parameters], [], $lineno, $tag);
    }
}
