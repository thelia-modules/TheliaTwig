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

use Twig_Compiler;

/**
 * Class PageLoop
 * @package TheliaTwig\Template\Node
 * @author Manuel Raynaud <manu@thelia.net>
 */
class PageLoop extends BaseLoopNode
{
    public function compile(Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $compiler->write("\$repeat = true;\n");
        $compiler->write("\$this->env->getExtension('loop')->loopHandler->pageLoop(\$context, ");
        $compiler->subcompile($this->getNode('parameters'));
        $compiler->raw(", \$repeat, true, \$context);\n");
        $compiler->write("while (\$repeat) {\n");
        $compiler->indent();
        $compiler->subcompile($this->getNode('body'));
        $compiler->write("\$repeat = false;\n");
        $compiler->write("\$this->env->getExtension('loop')->loopHandler->pageLoop(\$context, ");
        $compiler->subcompile($this->getNode('parameters'));
        $compiler->raw(", \$repeat, false);\n");
        $compiler->outdent();
        $compiler->write("}");
    }
}
