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

namespace TheliaTwig\Template\Elements;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Thelia\Core\Template\Element\Exception\ElementNotFoundException;
use Thelia\Core\Template\Element\Exception\InvalidElementException;
use Thelia\Core\Template\Element\LoopResult;
use TheliaTwig\TheliaTwig;

/**
 * Class Loop
 * @package TheliaTwig\Template\Elements
 * @author Manuel Raynaud <manu@thelia.net>
 */
class LoopHandler
{
    /**
     * @var ContainerInterface
     */
    protected $container;


    protected $loopDefinition;

    /**
     * @var \Thelia\Core\Translation\Translator
     */
    protected $translator;

    /** @var LoopResult[]  */
    protected $loopStack = [];

    protected $varStack = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->loopDefinition = $container->getParameter('thelia.parser.loops');
        $this->translator = $container->get("thelia.translator");
    }


    /**
     * Check if a loop has returned results. The loop shoud have been executed before, or an
     * InvalidArgumentException is thrown
     *
     * @param array $parameters
     *
     * @return boolean                   true if the loop is empty
     * @throws \InvalidArgumentException
     */
    public function checkEmptyLoop($parameters)
    {
        $loopName = $this->getParam($parameters, 'rel');

        if (null == $loopName) {
            throw new \InvalidArgumentException(
                $this->translator->trans("Missing 'rel' parameter in ifloop/elseloop arguments")
            );
        }

        if (! isset($this->loopStack[$loopName])) {
            throw new \InvalidArgumentException(
                $this->translator->trans("Related loop name '%name'' is not defined.", ['%name' => $loopName])
            );
        }

        return $this->loopStack[$loopName]->isEmpty();
    }

    public function loop(&$context, $parameters, &$repeat, $first = false, $_context = null)
    {
        $name = $this->getParam($parameters, 'name');
        if (null == $name) {
            throw new \InvalidArgumentException(
                $this->translator->trans("Missing 'name' parameter in loop arguments", [], TheliaTwig::DOMAIN)
            );
        }

        $type = $this->getParam($parameters, 'type');

        if (null == $type) {
            throw new \InvalidArgumentException(
                $this->translator->trans("Missing 'type' parameter in loop arguments", [], TheliaTwig::DOMAIN)
            );
        }

        if ($first) {
            $loop = $this->createLoopInstance($parameters);
            $pagination = 0;
            $loopResults = $loop->exec($pagination);
            $loopResults->rewind();

            $this->loopStack[$name] = $loopResults;

            // No results ? The loop is terminated, do not evaluate loop text.
            if ($loopResults->isEmpty()) {
                $repeat = false;
            } else {
                $this->varStack[$name] = $_context;
                $this->assignContext($context, $loopResults);
                $loopResults->next();
            }
        } else {
            $loopResults = $this->loopStack[$name];

            if ($loopResults->valid()) {
                $this->assignContext($context, $loopResults);

                $repeat = true;
                $loopResults->next();
            }
        }

        if (false === $repeat && isset($this->varStack[$name])) {
            $context = $this->varStack[$name];
            unset($this->varStack[$name]);
        }
    }

    protected function assignContext(&$context, LoopResult $loopResults)
    {
        $loopResultRow = $loopResults->current();

        foreach ($loopResultRow->getVarVal() as $var => $val) {
            $context[$var]= $val;
        }
    }

    /**
     * @param $parameters array of parameters
     *
     * @return \Thelia\Core\Template\Element\BaseLoop
     * @throws \Thelia\Core\Template\Element\Exception\InvalidElementException
     * @throws \Thelia\Core\Template\Element\Exception\ElementNotFoundException
     */
    protected function createLoopInstance($parameters)
    {
        $type = strtolower($parameters['type']);

        if (! isset($this->loopDefinition[$type])) {
            throw new ElementNotFoundException(
                $this->translator->trans("Loop type '%type' is not defined.", ['%type' => $type], TheliaTwig::DOMAIN)
            );
        }

        $class = new \ReflectionClass($this->loopDefinition[$type]);

        if ($class->isSubclassOf("Thelia\\Core\\Template\\Element\\BaseLoop") === false) {
            throw new InvalidElementException(
                $this->translator->trans("'%type' loop class should extends Thelia\Core\Template\Element\BaseLoop", ['%type' => $type], TheliaTwig::DOMAIN)
            );
        }

        $loop = $class->newInstance(
            $this->container
        );

        $loop->initializeArgs($parameters);

        return $loop;
    }

    /**
     * Get a function or block parameter value
     *
     * @param  array $params  the parameters array
     * @param  mixed $name    as single parameter name, or an array of names. In this case, the first defined parameter is returned. Use this for aliases (context, ctx, c)
     * @param  mixed $default the defaut value if parameter is missing (default to null)
     * @return mixed the parameter value, or the default value if it is not found.
     */
    public function getParam($params, $name, $default = null)
    {
        if (is_array($name)) {
            foreach ($name as $test) {
                if (isset($params[$test])) {
                    return $params[$test];
                }
            }
        } elseif (isset($params[$name])) {
            return $params[$name];
        }

        return $default;
    }
}
