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
class Loop extends \Twig_Extension
{
    public $loopHandler;

    public function __construct(LoopHandler $loopHandler)
    {
        $this->loopHandler = $loopHandler;
    }

    public function getTokenParsers()
    {
        return [
            new LoopTokenParsers(),
            new IfLoopTokenParsers(),
            new ElseLoopTokenParsers(),
            new PageLoopTokenParsers()
        ];
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'loop';
    }
}
