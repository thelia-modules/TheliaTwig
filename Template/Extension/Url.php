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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Thelia\Tools\TokenProvider;
use Thelia\Tools\URL as UrlManager;

/**
 * Class Url
 * @package TheliaTwig\Template\Extension
 * @author Manuel Raynaud <manu@thelia.net>
 */
class Url extends \Twig_Extension
{

    protected $request;

    protected $tokenProvider;

    protected $urlManager;

    protected $translator;

    public function __construct(Request $request, TokenProvider $tokenProvider, UrlManager $urlManager, TranslatorInterface $translator)
    {
        $this->request = $request;
        $this->tokenProvider = $tokenProvider;
        $this->urlManager = $urlManager;
        $this->translator = $translator;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction("url", [$this, 'url'])
        ];
    }

    /**
     * generates an absolute URL
     *
     * @param string $path
     * @param array $parameters
     * @param bool $current
     * @param null $file
     * @param bool $noAmp
     * @param null $target
     * @return mixed|string
     */
    public function url($path, $parameters = array(), $current = false, $file = null, $noAmp = false, $target = null)
    {
        if ($current) {
            $path = $this->request->getPathInfo();

            // Then build the query variables
            $parameters = array_merge(
                $this->request->query->all(),
                $parameters
            );
        }

        if ($file !== null) {
            $path = $file;
            $mode = UrlManager::PATH_TO_FILE;
        } elseif ($path !== null) {
            $mode = UrlManager::WITH_INDEX_PAGE;
        } else {
            throw new \InvalidArgumentException($this->translator->trans("Please specify either 'path' or 'file' parameter in {url} function."));
        }

        $url = $this->urlManager->absoluteUrl(
            $path,
            $parameters,
            $mode
        );

        if ($noAmp) {
            $url = str_replace('&', '&amp;', $url);
        }

        if (null !== $target) {
            $url .= "#".$target;
        }

        return $url;
    }


    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'url';
    }
}
