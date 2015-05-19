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

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Thelia\Core\Template\ParserInterface;
use Thelia\Core\Template\TemplateDefinition;
use Thelia\Log\Tlog;

/**
 * Class TwigParser
 * @package TheliaTwig\Template
 * @author Manuel Raynaud <manu@thelia.net>
 */
class TwigParser extends \Twig_Environment implements ParserInterface
{

    /** @var \Twig_Loader_Filesystem */
    protected $fileSystemLoader;
    protected $status = 200;
    protected $context = array();

    protected $backOfficeTemplateDirectories = array();
    protected $frontOfficeTemplateDirectories = array();

    protected $templateDirectories = array();

    /**
     * @var TemplateDefinition
     */
    protected $templateDefinition = "";

    public function __construct(\Twig_LoaderInterface $loader = null, $options = array())
    {
        $this->fileSystemLoader = $loader;
        parent::__construct(null, $options);

        if ($this->isDebug()) {
            $this->addExtension(new \Twig_Extension_Debug());
        }
    }

/*    public function loadTemplate($name, $index = null)
    {
        $template = parent::loadTemplate($name, $index);
        if ($template instanceof ContainerAwareInterface) {
            $template->setContainer($this->container);
        }

        return $template;
    }*/


    public function render($realTemplateName, array $parameters = array(), $compressOutput = true)
    {
        if (substr($realTemplateName, strlen($realTemplateName)-4) != "twig") {
            $realTemplateName .= ".twig";
        }
        $parameters = array_merge($parameters, $this->context);
        $this->setLoader($this->fileSystemLoader);
        return parent::render($realTemplateName, $parameters);
    }

    public function renderString($templateText, array $parameters = array(), $compressOutput = true)
    {
        $parameters = array_merge($parameters, $this->context);
        $loader = new \Twig_Loader_Array(
            ['index.html' => $templateText]
        );
        $this->setLoader($loader);
        return parent::render('index.html', $parameters);
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string the template path
     */
    public function getTemplatePath()
    {
        return $this->templateDefinition->getPath();
    }

    /**
     * @param TemplateDefinition $templateDefinition
     */
    public function setTemplateDefinition(TemplateDefinition $templateDefinition, $useFallback = false)
    {
        $this->templateDefinition = $templateDefinition;

        /* init template directories */
        $this->fileSystemLoader->setPaths(array());



        /* add modules template directories */
        $this->addTemplateDirectory(
            $templateDefinition->getType(),
            $templateDefinition->getName(),
            THELIA_TEMPLATE_DIR . $this->getTemplatePath(),
            self::TEMPLATE_ASSETS_KEY,
            true
        );

        $type = $templateDefinition->getType();
        $name = $templateDefinition->getName();

        /* do not pass array directly to addTemplateDir since we cant control on keys */
        if (isset($this->templateDirectories[$type][$name])) {
            foreach ($this->templateDirectories[$type][$name] as $key => $directory) {
                $this->fileSystemLoader->addPath($directory);
            }
        }

        // fallback on default template
        if ($useFallback && 'default' !== $name) {
            if (isset($this->templateDirectories[$type]['default'])) {
                foreach ($this->templateDirectories[$type]['default'] as $key => $directory) {
                    if (false === $this->fileSystemLoader->exists($directory)) {
                        $this->fileSystemLoader->addPath($directory);
                    }
                }
            }
        }
    }

    /**
     * Get template definition
     *
     * @param bool $webAssetTemplate Allow to load asset from another template
     *                               If the name of the template if provided
     *
     * @return TemplateDefinition
     */
    public function getTemplateDefinition($webAssetTemplate = false)
    {
        $ret = clone $this->templateDefinition;

        if (false !== $webAssetTemplate) {
            $customPath = str_replace($ret->getName(), $webAssetTemplate, $ret->getPath());
            $ret->setName($webAssetTemplate);
            $ret->setPath($customPath);
        }

        return $ret;
    }

    /**
     * Add a template directory to the current template list
     *
     * @param int     $templateType      the template type (a TemplateDefinition type constant)
     * @param string  $templateName      the template name
     * @param string  $templateDirectory path to the template directory
     * @param string  $key               ???
     * @param boolean $addAtBeginning    if true, the template definition should be added at the beginning of the template directory list
     */
    public function addTemplateDirectory($templateType, $templateName, $templateDirectory, $key, $addAtBeginning = false)
    {
        Tlog::getInstance()->addDebug("Adding template directory $templateDirectory, type:$templateType name:$templateName, key: $key");

        if (true === $addAtBeginning && isset($this->templateDirectories[$templateType][$templateName])) {
            // When using array_merge, the key was set to 0. Use + instead.
            $this->templateDirectories[$templateType][$templateName] =
                [ $key => $templateDirectory ] + $this->templateDirectories[$templateType][$templateName]
            ;
        } else {
            $this->templateDirectories[$templateType][$templateName][$key] = $templateDirectory;
        }
    }

    /**
     * Return the registered template directories for a given template type
     *
     * @param  int                      $templateType
     * @throws \InvalidArgumentException
     * @return mixed:
     */
    public function getTemplateDirectories($templateType)
    {
        if (! isset($this->templateDirectories[$templateType])) {
            throw new \InvalidArgumentException("Failed to get template type %", $templateType);
        }

        return $this->templateDirectories[$templateType];
    }

    /**
     * Create a variable that will be available in the templates
     *
     * @param string $variable the variable name
     * @param mixed $value the value of the variable
     */
    public function assign($variable, $value)
    {
        $this->context[$variable] = $value;
    }
}
