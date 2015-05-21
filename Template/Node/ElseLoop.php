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
 * Class ElseLoop
 * @package TheliaTwig\Template\Node
 * @author Manuel Raynaud <manu@thelia.net>
 */
class ElseLoop extends BaseLoopNode
{
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $compiler->write("if (true === \$this->env->getExtension('loop')->loopHandler->checkEmptyLoop(");
        $compiler->subcompile($this->getNode('parameters'));
        $compiler->raw(")) {\n");
        $compiler->indent();
        $compiler->subcompile($this->getNode('body'));
        $compiler->outdent();
        $compiler->write("}");
    }
}
