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
 * Class BaseIfNode
 * @package TheliaTwig\Template\Node
 * @author Julien Chanséaume <julien@thelia.net>
 */
abstract class BaseIfNode extends \Twig_Node
{

    protected $extensionName = '';

    protected $testFunction = '';

    public static $tmpNumber = 0;

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

        $testVar = sprintf('tmp%s%s', $this->extensionName, ++self::$tmpNumber);

        $compiler->write("ob_start();\n");
        $compiler->subcompile($this->getNode('body'));
        $compiler->write("\$$testVar = ob_get_clean();\n");

        $compiler->write(
            sprintf(
                "if (false === \$this->env->getExtension('%s')->%s(",
                $this->extensionName,
                $this->testFunction
            )
        );
        $compiler->subcompile($this->getNode('parameters'));
        $compiler->raw(")) {\n");
        $compiler->indent();
        $compiler->write("echo \$$testVar;\n");
        $compiler->outdent();
        $compiler->write("}\n");
    }
}
