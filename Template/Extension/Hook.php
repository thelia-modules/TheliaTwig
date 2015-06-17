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

use TheliaTwig\Template\Elements\HookHandler;
use TheliaTwig\Template\TokenParsers\IfHook as IfHookTokenParsers;
use TheliaTwig\Template\TokenParsers\ElseHook as ElseHookTokenParsers;
use TheliaTwig\Template\TokenParsers\HookBlock as HookBlockTokenParsers;
use TheliaTwig\Template\TokenParsers\ForHook as ForHookTokenParsers;
use TheliaTwig\Template\TokenParsers\Hook as HookTokenParsers;

/**
 * Class Hook
 * @package TheliaTwig\Template\Extension
 * @author Julien Chanséaume <julien@thelia.net>
 */
class Hook extends \Twig_Extension
{
    public $hookHandler;

    public function __construct(HookHandler $hookHandler)
    {
        $this->hookHandler = $hookHandler;
    }

    public function getTokenParsers()
    {
        return [
            new HookTokenParsers(),
            new IfHookTokenParsers(),
            new ElseHookTokenParsers(),
            new HookBlockTokenParsers(),
            new ForHookTokenParsers()
        ];
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'hook';
    }
}
