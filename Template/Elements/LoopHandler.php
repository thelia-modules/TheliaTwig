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

use Propel\Runtime\Util\PropelModelPager;
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

    protected $pageVarStack = [];

    /** @var PropelModelPager[] */
    protected static $pagination = null;

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
            // Check if a loop with the same name exists in the current scope, and abort if it's the case.
            if (array_key_exists($name, $this->varStack)) {
                throw new \InvalidArgumentException(
                    $this->translator->trans("A loop named '%name' already exists in the current scope.", ['%name' => $name])
                );
            }

            $loop = $this->createLoopInstance($parameters);
            self::$pagination[$name] = null;
            $loopResults = $loop->exec(self::$pagination[$name]);
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


    public function pageLoop(&$context, $parameters, &$repeat, $first = false, $_context = null)
    {
        $loopName = $this->getParam($parameters, 'rel');

        if (null == $loopName) {
            throw new \InvalidArgumentException($this->translator->trans("Missing 'rel' parameter in page loop", [], TheliaTwig::DOMAIN));
        }

        $pagination = $this->getPagination($loopName);

        if ($pagination === null || $pagination->getNbResults() == 0) {
            $repeat = false;
            return;
        }

        $startPage          = intval($this->getParam($parameters, 'start-page', 1));
        $displayedPageCount = intval($this->getParam($parameters, 'limit', 10));

        if (intval($displayedPageCount) == 0) {
            $displayedPageCount = PHP_INT_MAX;
        }

        $totalPageCount = $pagination->getLastPage();

        if ($first) {
            $this->pageVarStack[$loopName] = $_context;
            // The current page
            $currentPage = $pagination->getPage();

            // Get the start page.
            if ($totalPageCount > $displayedPageCount) {
                $startPage = $currentPage - round($displayedPageCount / 2);

                if ($startPage < 0) {
                    $startPage = 1;
                }
            }

            // This is the iterative page number, the one we're going to increment in this loop
            $iterationPage = $startPage;

            // The last displayed page number
            $endPage = $startPage + $displayedPageCount - 1;

            if ($endPage > $totalPageCount) {
                $endPage = $totalPageCount;
            }

            // The first displayed page number
            $context['START'] = $startPage;
            // The previous page number
            $context['PREV'] = $currentPage > 1 ? $currentPage-1 : $currentPage;
            // The next page number
            $context['NEXT'] = $currentPage < $totalPageCount ? $currentPage+1 : $totalPageCount;
            // The last displayed page number
            $context['END'] = $endPage;
            // The overall last page
            $context['LAST'] = $totalPageCount;
        } else {
            $iterationPage = $context['PAGE'];
            $iterationPage++;
        }

        if ($iterationPage <= $context['END']) {
            // The iterative page number
            $context['PAGE'] = $iterationPage;

            // The overall current page number
            $context['CURRENT'] = $pagination->getPage();

            $repeat = true;
        }

        if ($repeat === false && isset($this->pageVarStack[$loopName])) {
            $context = $this->pageVarStack[$loopName];
            unset($this->pageVarStack[$loopName]);
        }
    }

    /**
     * @param  string                    $loopName
     * @return PropelModelPager
     * @throws \InvalidArgumentException if no pagination was found for loop
     */
    public function getPagination($loopName)
    {
        if (array_key_exists($loopName, self::$pagination)) {
            return self::$pagination[$loopName];
        } else {
            throw new \InvalidArgumentException(
                $this->translator->trans("No pagination currently defined for loop name '%name'", ['%name' => $loopName ], TheliaTwig::DOMAIN)
            );
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
