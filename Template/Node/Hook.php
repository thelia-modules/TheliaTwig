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
 * Class Hook
 * @package TheliaTwig\Template\Node
 * @author Julien Chanséaume <julien@thelia.net>
 */
class Hook extends \Twig_Node
{
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $compiler->write("\$hook = \$this->env->getExtension('hook')->hookHandler->processHookFunction(\$context, ");
        $compiler->subcompile($this->getNode('parameters'));
        $compiler->raw(");\n");
        $compiler->write("echo \$hook;\n");
    }
}
