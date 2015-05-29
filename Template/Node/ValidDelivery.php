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
 * Class ValidDelivery
 * @package TheliaTwig\Template\Node
 * @author Manuel Raynaud <manu@thelia.net>
 */
class ValidDelivery extends \Twig_Node
{
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->write("\$this->env->getExtension('security')->checkValidDelivery();\n");
    }
}
