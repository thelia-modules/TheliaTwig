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
 * Class IfLoop
 * @package TheliaTwig\Template\Node
 * @author Manuel Raynaud <manu@thelia.net>
 */
class IfLoop extends BaseLoopNode
{

    public static $tmpNumber = 0;

    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $number = ++self::$tmpNumber;

        $compiler->write("ob_start();\n");
        $compiler->subcompile($this->getNode('body'));
        $compiler->write("\$tmp$number = ob_get_clean();\n");

        $compiler->write("if (false === \$this->env->getExtension('loop')->loopHandler->checkEmptyLoop(");
        $compiler->subcompile($this->getNode('parameters'));
        $compiler->raw(")) {\n");
        $compiler->indent();
        $compiler->write("echo \$tmp$number;\n");
        $compiler->outdent();
        $compiler->write("}\n");
    }
}
