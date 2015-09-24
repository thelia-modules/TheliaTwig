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
 * Class BaseElseNode
 * @package TheliaTwig\Template\Node
 * @author Manuel Raynaud <manu@thelia.net>
 * @author Julien Chanséaume <julien@thelia.net>
 */
class BaseElseNode extends \Twig_Node
{
    protected $extensionName = '';

    protected $testFunction = '';

    public function __construct(\Twig_Node $body, \Twig_Node $parameters, $lineno, $tag)
    {
        parent::__construct(['body' => $body, 'parameters' => $parameters], [], $lineno, $tag);
    }

    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        if (empty($this->extensionName) || empty($this->testFunction)) {
            throw new \Exception("Missing extension name or test function");
        }

        $compiler->write(
            sprintf(
                "if (true === \$this->env->getExtension('%s')->%s(",
                $this->extensionName,
                $this->testFunction
            )
        );
        $compiler->subcompile($this->getNode('parameters'));
        $compiler->raw(")) {\n");
        $compiler->indent();
        $compiler->subcompile($this->getNode('body'));
        $compiler->outdent();
        $compiler->write("}");
    }
}
