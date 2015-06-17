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

use Thelia\Core\HttpFoundation\Request;
use TheliaTwig\Template\Elements\LoopHandler;
use TheliaTwig\Template\TokenParsers\IfLoop as IfLoopTokenParsers;
use TheliaTwig\Template\TokenParsers\Loop as LoopTokenParsers;
use TheliaTwig\Template\TokenParsers\ElseLoop as ElseLoopTokenParsers;
use TheliaTwig\Template\TokenParsers\PageLoop as PageLoopTokenParsers;

/**
 * Class Loop
 * @package TheliaTwig\Template\Extension
 * @author Manuel Raynaud <manu@thelia.net>
 */
class FlashMessage extends \Twig_Extension
{
    public $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('has_flash', [$this, 'hasFlashMessage']),
            new \Twig_SimpleFunction('flash', [$this, 'getFlashMessage']),
        ];
    }

    /**
     * Test if a flash message exits in the session
     *
     * @param string $type
     *
     * @return boolean true if
     * @throws \InvalidArgumentException if a parameter is missing
     *
     */
    public function hasFlashMessage($type)
    {
        return $this->request->getSession()->getFlashBag()->has($type);
    }

    /**
     * Get flash message and clean session from this key.
     * If `type` is not provided all flash message in session are returned
     *
     * @param string|null $type the type (identifier) of the flash message we want.
     *
     * @return array
     */
    public function getFlashMessage($type = null)
    {
        $results = [];

        if (null === $type) {
            $results = $this->request->getSession()->getFlashBag()->all();
        } else {
            $results = $this->request->getSession()->getFlashBag()->get($type, []);
        }

        return $results;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'flash';
    }
}
