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
 * Class Auth
 * @package TheliaTwig\Template\Node
 * @author Manuel Raynaud <manu@thelia.net>
 */
class Auth extends \Twig_Node
{
    public function compile(Twig_Compiler $compiler)
    {
        $compiler->write("\$this->env->getExtension('security')->checkAuth(");
        $compiler->subcompile($this->getNode('auth'));
        $compiler->raw(");\n");
    }
}
