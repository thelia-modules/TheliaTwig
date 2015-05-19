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

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Loop
 * @package TheliaTwig\Template\Node
 * @author Manuel Raynaud <manu@thelia.net>
 */
class Loop extends \Twig_Node
{

    protected $parameters;

    public function __construct(\Twig_Node $body, \Twig_Node $parameters, $lineno, $tag)
    {
        parent::__construct(['body' => $body, 'parameters' => $parameters], [], $lineno, $tag);
    }

    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $compiler->write("\$repeat = true;\n");
        $compiler->write("\$this->env->getExtension('loop')->loopHandler->loop(\$context, ");
        $compiler->subcompile($this->getNode('parameters'));
        $compiler->raw(", \$repeat, true, \$context);\n");
        $compiler->write("while (\$repeat) {\n");
        $compiler->indent();
            $compiler->subcompile($this->getNode('body'));
            $compiler->write("\$repeat = false;\n");
            $compiler->write("\$this->env->getExtension('loop')->loopHandler->loop(\$context, ");
            $compiler->subcompile($this->getNode('parameters'));
            $compiler->raw(", \$repeat, false, \$context);\n");
        $compiler->outdent();
        $compiler->write("}\n");
    }
}
