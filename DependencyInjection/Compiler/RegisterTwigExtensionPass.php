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

namespace TheliaTwig\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;


/**
 * Class RegisterTwigExtensionPass
 * @package TheliaTwig\DependencyInjection\Compiler
 * @author Manuel Raynaud <manu@thelia.net>
 */
class RegisterTwigExtensionPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition("thelia.parser")) {
            return;
        }

        $twig = $container->getDefinition("thelia.parser");

        $extensions = [];

        foreach ($container->findTaggedServiceIds("thelia.parser.add_extension") as $id => $parameters) {

            $priority = isset($parameters['priority']) ? $parameters['priority'] : 128;

            $extensions[$priority][] = $id;
        }

        foreach ($extensions as $extension) {
            array_walk($extension, function($id) use ($twig){
                $twig->addMethodCall("addExtension", array(new Reference($id)));
            });

        }

    }
}
