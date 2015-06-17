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


namespace TheliaTwig\Template;

use Twig_Error_Loader;
use Twig_ExistsLoaderInterface;
use Twig_LoaderInterface;

/**
 * Class TwigTheliaLoader
 * @package TheliaTwig\Template
 * @author Julien Chanséaume <julien@thelia.net>
 */
class TwigTheliaLoader implements Twig_LoaderInterface, Twig_ExistsLoaderInterface
{
    protected $paths = array();
    protected $cache = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getSource($name)
    {
        return file_get_contents($this->findTemplate($name));
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKey($name)
    {
        return $this->findTemplate($name);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($name)
    {
        $name = $this->normalizeName($name);

        if (isset($this->cache[$name])) {
            return true;
        }

        try {
            $this->findTemplate($name);

            return true;
        } catch (Twig_Error_Loader $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($name, $time)
    {
        return filemtime($this->findTemplate($name)) <= $time;
    }

    protected function findTemplate($name)
    {
        $normalizeName = $this->normalizeName($name);

        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $this->validateName($normalizeName);

        if (is_file($normalizeName)) {
            if (false !== $realpath = realpath($normalizeName)) {
                return $this->cache[$name] = $realpath;
            }

            return $this->cache[$name] = $normalizeName;
        }

        throw new Twig_Error_Loader(sprintf('Unable to find template "%s".', $normalizeName));
    }

    protected function normalizeName($name)
    {
        $name =  preg_replace('#/{2,}#', '/', strtr((string) $name, '\\', '/'));
        if (substr($name, strlen($name)-4) != "twig") {
            $name .= ".twig";
        }

        return $name;
    }

    protected function validateName($name)
    {
        if (false !== strpos($name, "\0")) {
            throw new Twig_Error_Loader('A template name cannot contain NUL bytes.');
        }

        $name = ltrim($name, '/');
        $parts = explode('/', $name);
        $level = 0;
        foreach ($parts as $part) {
            if ('..' === $part) {
                --$level;
            } elseif ('.' !== $part) {
                ++$level;
            }

            if ($level < 0) {
                throw new Twig_Error_Loader(sprintf('Looks like you try to load a template outside configured directories (%s).', $name));
            }
        }
    }
}

