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

namespace TheliaTwig\Template\Extension;


/**
 * Class BaseExtension
 * @package TheliaTwig\Template\Extension
 * @author Manuel Raynaud <manu@thelia.net>
 */
abstract class BaseExtension extends \Twig_Extension
{

    /**
     * Explode a comma separated list in a array, trimming all array elements
     *
     * @param  mixed  $commaSeparatedValues
     * @return mixed:
     */
    protected function explode($commaSeparatedValues)
    {
        if (null === $commaSeparatedValues) {
            return [];
        }

        $array = explode(',', $commaSeparatedValues);

        if (array_walk($array, function (&$item) {$item = strtoupper(trim($item));})) {
            return $array;
        }

        return [];
    }

    /**
     * Get a function or block parameter value
     *
     * @param  array $parameters  the parameters array
     * @param  mixed $name    as single parameter name, or an array of names. In this case, the first defined parameter is returned. Use this for aliases (context, ctx, c)
     * @param  mixed $default the defaut value if parameter is missing (default to null)
     * @return mixed the parameter value, or the default value if it is not found.
     */
    public function getParam($parameters, $name, $default = null)
    {
        if (is_array($name)) {
            foreach ($name as $test) {
                if (isset($parameters[$test])) {
                    return $parameters[$test];
                }
            }
        } elseif (isset($parameters[$name])) {
            return $parameters[$name];
        }

        return $default;
    }

}
