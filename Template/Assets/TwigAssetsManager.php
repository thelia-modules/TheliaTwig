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

namespace TheliaTwig\Template\Assets;

use Thelia\Core\Template\Assets\AssetManagerInterface;
use Thelia\Core\Template\Assets\AssetResolverInterface;
use Thelia\Core\Template\ParserInterface;
use Thelia\Exception\TheliaProcessException;
use Thelia\Core\Template\TemplateDefinition;
use Thelia\Log\Tlog;

class TwigAssetsManager
{
    const ASSET_TYPE_AUTO = '';

    private $assetsManager;

    /** @var AssetResolverInterface */
    private $assetsResolver;

    private $web_root;
    private $path_relative_to_web_root;

    private static $assetsDirectory = null;

    /**
     * Creates a new TwigAssetsManager instance
     *
     * @param AssetManagerInterface  $assetsManager   an asset manager instance
     * @param AssetResolverInterface $assetsResolver  an asset resolver instance
     * @param string $web_root the disk path to the web root (with final /)
     * @param string $path_relative_to_web_root the path (relative to web root) where the assets will be generated
     */
    public function __construct(
        AssetManagerInterface $assetsManager,
        AssetResolverInterface $assetsResolver,
        $web_root,
        $path_relative_to_web_root
    ) {
        $this->web_root = $web_root;
        $this->path_relative_to_web_root = $path_relative_to_web_root;

        $this->assetsManager = $assetsManager;
        $this->assetsResolver = $assetsResolver;
    }


    /**
     * Prepare current template assets
     *
     * @param string $assets_directory the assets directory in the template
     * @param ParserInterface $parser the smarty parser
     */
    public function prepareAssets($assets_directory, ParserInterface $parser)
    {
        // Be sure to use the proper path separator
        if (DS != '/') {
            $assets_directory = str_replace('/', DS, $assets_directory);
        }

        // Set the current template assets directory
        self::$assetsDirectory = $assets_directory;

        $this->prepareTemplateAssets($parser->getTemplateDefinition(), $assets_directory, $parser);
    }

    /**
     * Prepare template assets
     *
     * @param TemplateDefinition $templateDefinition the template to process
     * @param string $assets_directory the assets directory in the template
     * @param ParserInterface $parser the current parser.
     */
    protected function prepareTemplateAssets(
        TemplateDefinition $templateDefinition,
        $assets_directory,
        ParserInterface $parser
    ) {
        // Get the registered template directories for the current template path
        $templateDirectories = $parser->getTemplateDirectories($templateDefinition->getType());

        if (isset($templateDirectories[$templateDefinition->getName()])) {
            /* create assets foreach registered directory : main @ modules */
            foreach ($templateDirectories[$templateDefinition->getName()] as $key => $directory) {
                // This is the assets directory in the template's tree
                $tpl_path = $directory . DS . $assets_directory;

                $asset_dir_absolute_path = realpath($tpl_path);

                if (false !== $asset_dir_absolute_path) {
                    // If we're processing template assets (not module assets),
                    // we will use the $assets_directory as the assets parent dir.
                    if (ParserInterface::TEMPLATE_ASSETS_KEY == $key && ! null !== $assets_directory) {
                        $assetsWebDir = ParserInterface::TEMPLATE_ASSETS_KEY . DS . $assets_directory;
                    } else {
                        $assetsWebDir = $key;
                    }

                    Tlog::getInstance()->addDebug(
                        "Preparing assets: source assets directory $asset_dir_absolute_path, "
                        . "web assets dir base: " . $this->web_root . $this->path_relative_to_web_root . ", "
                        . "template: ".$templateDefinition->getPath().", "
                        . "web asset key: $assetsWebDir (key=$key)"
                    );

                    $this->assetsManager->prepareAssets(
                        $asset_dir_absolute_path,
                        $this->web_root . $this->path_relative_to_web_root,
                        $templateDefinition->getPath(),
                        $key . DS . $assets_directory
                    );
                }
            }
        }
    }

    /**
     * Retrieve asset URL
     *
     * @param string $assetType js|css|image
     * @param array $params Parameters
     *                                             - file File path in the default template
     *                                             - source module asset
     *                                             - filters filter to apply
     *                                             - debug
     *                                             - template if you want to load asset from another template
     * @param ParserInterface $parser the current parser
     *
     * @param bool $allowFilters if false, the 'filters' parameter is ignored
     * @return string
     */
    public function computeAssetUrl($assetType, $params, ParserInterface $parser, $allowFilters = true)
    {
        $assetUrl = "";

        $file = $params['file'];

        // The 'file' parameter is mandatory
        if (empty($file)) {
            throw new \InvalidArgumentException(
                "The 'file' parameter is missing in an asset directive (type is '$assetType')"
            );
        }

        $assetOrigin  = isset($params['source']) ? $params['source'] : ParserInterface::TEMPLATE_ASSETS_KEY;
        $filters      = $allowFilters && isset($params['filters']) ? $params['filters'] : '';
        $debug        = isset($params['debug']) ? trim(strtolower($params['debug'])) == 'true' : false;
        $templateName = isset($params['template']) ? $params['template'] : false;
        $failsafe     = isset($params['failsafe']) ? $params['failsafe'] : false;

        Tlog::getInstance()->debug("Searching asset $file in source $assetOrigin, with template $templateName");

        if (false !==  $templateName) {
            // We have to be sure that this external template assets have been properly prepared.
            // We will assume the following:
            //   1) this template have the same type as the current template,
            //   2) this template assets have the same structure as the current template
            //     (which is in self::$assetsDirectory)
            $currentTemplate = $parser->getTemplateDefinition();

            $templateDefinition = new TemplateDefinition(
                $templateName,
                $currentTemplate->getType()
            );

            /* Add this templates directory to the current list */
            $parser->addTemplateDirectory(
                $templateDefinition->getType(),
                $templateDefinition->getName(),
                THELIA_TEMPLATE_DIR . $templateDefinition->getPath(),
                ParserInterface::TEMPLATE_ASSETS_KEY
            );

            $this->prepareTemplateAssets($templateDefinition, self::$assetsDirectory, $parser);
        }

        $assetSource = $this->assetsResolver->resolveAssetSourcePath($assetOrigin, $templateName, $file, $parser);

        if (null !== $assetSource) {
            $assetUrl = $this->assetsResolver->resolveAssetURL(
                $assetOrigin,
                $file,
                $assetType,
                $parser,
                $filters,
                $debug,
                self::$assetsDirectory,
                $templateName
            );
        } else {
            // Log the problem
            if ($failsafe) {
                // The asset URL will be ''
                Tlog::getInstance()->addWarning("Failed to find asset source file " . $params['file']);
            } else {
                throw new TheliaProcessException("Failed to find asset source file " . $params['file']);
            }
        }

        return $assetUrl;
    }

    public function processSmartyPluginCall(
        $assetType,
        $params,
        $content,
        ParserInterface $parser,
        &$repeat
    ) {
        // Opening tag (first call only)
        if ($repeat) {
            $isfailsafe = false;

            $url = '';
            try {
                // Check if we're in failsafe mode
                if (isset($params['failsafe'])) {
                    $isfailsafe = $params['failsafe'];
                }

                $url = $this->computeAssetUrl($assetType, $params, $parser);

                if (empty($url)) {
                    $message = sprintf("Failed to get real path of asset %s without exception", $params['file']);

                    Tlog::getInstance()->addWarning($message);

                    // In debug mode, throw exception
                    if ($this->assetsManager->isDebugMode() && ! $isfailsafe) {
                        throw new TheliaProcessException($message);
                    }
                }
            } catch (\Exception $ex) {
                Tlog::getInstance()->addWarning(
                    sprintf(
                        "Failed to get real path of asset %s with exception: %s",
                        $params['file'],
                        $ex->getMessage()
                    )
                );

                // If we're in development mode, just retrow the exception, so that it will be displayed
                if ($this->assetsManager->isDebugMode() && ! $isfailsafe) {
                    throw $ex;
                }
            }
            $parser->assign('asset_url', $url);
        } elseif (isset($content)) {
            return $content;
        }

        return null;
    }
}
