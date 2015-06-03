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
            new \Twig_SimpleFunction("url", [$this, 'url']),
            new \Twig_SimpleFunction("url_token", [$this, 'tokenUrl']),
            new \Twig_SimpleFunction("current_url", [$this, 'currentUrl']),
            new \Twig_SimpleFunction("previous_url", [$this, 'currentUrl']),
            new \Twig_SimpleFunction("index_url", [$this, 'currentUrl']),
        ];
    }

    /**
     * @return string return the current url
     */
    public function currentUrl()
    {
        return $this->request->getUri();
    }

    /**
     * @return string return the previous url
     */
    protected function previousUrl()
    {
        return $this->urlManager->absoluteUrl($this->request->getSession()->getReturnToUrl());
    }

    /**
     * @return string return the home url
     */
    protected function indexUrl()
    {
        return $this->urlManager->getIndexPage();
    }

    /**
     * generates an absolute URL
     *
     * @param string $path The value of the path parameter is the route path you want to get as an URL
     * @param array $parameters paremeters added to the query string
     * @param bool $current generate absolute URL grom the current URL
     * @param null $file The value of the file parameter is the absolute path (from /web) of a real file, that will be served by your web server, and not processed by Thelia
     * @param bool $noAmp escape all & as &amp; that may be present in the generated URL.
     * @param null $target Add an anchor to the URL
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
     * generates an absolute URL
     *
     * @param string $path The value of the path parameter is the route path you want to get as an URL
     * @param array $parameters paremeters added to the query string
     * @param bool $current generate absolute URL grom the current URL
     * @param null $file The value of the file parameter is the absolute path (from /web) of a real file, that will be served by your web server, and not processed by Thelia
     * @param bool $noAmp escape all & as &amp; that may be present in the generated URL.
     * @param null $target Add an anchor to the URL
     * @return mixed|string
     */
    public function tokenUrl($path, $parameters = array(), $current = false, $file = null, $noAmp = false, $target = null)
    {
        $url = $this->url($path, $parameters, $current, $file, $noAmp, $target);
        $token = $this->tokenProvider->assignToken();

        $urlTokenParam = isset($parameters['url_param']) ? $parameters['url_param'] : '_token';

        return $this->urlManager->absoluteUrl(
            $url,
            [
                $urlTokenParam => $token
            ]
        );
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
