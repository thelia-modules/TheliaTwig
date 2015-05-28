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

use Symfony\Component\Translation\TranslatorInterface;
use TheliaTwig\Template\TokenParsers\TranslationDomain;
use TheliaTwig\Template\TokenParsers\TranslationLocale;

/**
 * Class Translation
 * @package TheliaTwig\Template\Extension
 * @author Manuel Raynaud <manu@thelia.net>
 */
class Translation extends \Twig_Extension
{

    protected $translator;
    protected $defaultTranslationDomain = '';
    protected $defaultLocale = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('intl', [$this, 'translate']),
        ];
    }

    public function getTokenParsers()
    {
        return [
            new TranslationDomain(),
            new TranslationLocale()
        ];
    }

    public function translate($id, $parameters = [], $domain = null, $locale = null)
    {
        return $this->translator->trans(
            $id,
            $parameters,
            $domain ?: $this->defaultTranslationDomain,
            $locale ?: $this->defaultLocale
        );
    }

    /**
     * @param string $domain set the default translation domain
     */
    public function setDefaultTranslationDomain($domain)
    {
        $this->defaultTranslationDomain = $domain;
    }

    /**
     * @param $locale set the default translation locale
     */
    public function setDefaultLocale($locale)
    {
        $this->defaultLocale = $locale;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'translation';
    }
}
