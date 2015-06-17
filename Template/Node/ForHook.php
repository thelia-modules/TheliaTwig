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
 * Class ForHook
 * @package TheliaTwig\Template\Node
 * @author Julien Chanséaume <julien@thelia.net>
 */
class ForHook extends \Twig_Node
{
    public function __construct(\Twig_Node $body, \Twig_Node $parameters, $lineno, $tag)
    {
        parent::__construct(['body' => $body, 'parameters' => $parameters], [], $lineno, $tag);
    }

    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $compiler->write("\$repeat = true;\n");
        $compiler->write("\$this->env->getExtension('hook')->hookHandler->processForHookBlock(\$context, ");
        $compiler->subcompile($this->getNode('parameters'));
        $compiler->raw(", \$repeat, true, \$context);\n");
        $compiler->write("while (\$repeat) {\n");
        $compiler->indent();
        $compiler->subcompile($this->getNode('body'));
        $compiler->write("\$repeat = false;\n");
        $compiler->write("\$this->env->getExtension('hook')->hookHandler->processForHookBlock(\$context, ");
        $compiler->subcompile($this->getNode('parameters'));
        $compiler->raw(", \$repeat, false);\n");
        $compiler->outdent();
        $compiler->write("}\n");
    }
}
