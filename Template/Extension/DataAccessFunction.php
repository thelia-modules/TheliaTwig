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

use Propel\Runtime\ActiveQuery\ModelCriteria;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Security\SecurityContext;
use Thelia\Core\Template\ParserContext;
use Thelia\Model\BrandQuery;
use Thelia\Model\CategoryQuery;
use Thelia\Model\ConfigQuery;
use Thelia\Model\ContentQuery;
use Thelia\Model\CountryQuery;
use Thelia\Model\CurrencyQuery;
use Thelia\Model\FolderQuery;
use Thelia\Model\MetaDataQuery;
use Thelia\Model\OrderQuery;
use Thelia\Model\ProductQuery;
use Thelia\Model\Tools\ModelCriteriaTools;
use Thelia\TaxEngine\TaxEngine;
use Thelia\Tools\DateTimeFormat;

/**
 * Class DataAccessFunction
 * @package TheliaTwig\Template\Extension
 * @author Manuel Raynaud <manu@thelia.net>
 */
class DataAccessFunction extends \Twig_Extension
{

    protected $securityContext;
    protected $parserContext;
    protected $request;
    protected $dispatcher;
    protected $taxEngine;

    private static $dataAccessCache = array();

    public function __construct(
        Request $request,
        SecurityContext $securityContext,
        TaxEngine $taxEngine,
        ParserContext $parserContext,
        ContainerAwareEventDispatcher $dispatcher
    ) {
        $this->securityContext = $securityContext;
        $this->parserContext = $parserContext;
        $this->request = $request;
        $this->dispatcher = $dispatcher;
        $this->taxEngine = $taxEngine;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('admin', [$this, 'adminDataAccess']),
            new \Twig_SimpleFunction('brand', [$this, 'brandDataAccess']),
            new \Twig_SimpleFunction('cart', [$this, 'cartDataAccess']),
            new \Twig_SimpleFunction('category', [$this, 'categoryDataAccess']),
            new \Twig_SimpleFunction('config', [$this, 'configDataAccess']),
            new \Twig_SimpleFunction('content', [$this, 'contentDataAccess']),
            new \Twig_SimpleFunction('country', [$this, 'countryDataAccess']),
            new \Twig_SimpleFunction('currency', [$this, 'currencyDataAccess']),
            new \Twig_SimpleFunction('customer', [$this, 'customerDataAccess']),
            new \Twig_SimpleFunction('folder', [$this, 'folderDataAccess']),
            new \Twig_SimpleFunction('lang', [$this, 'langDataAccess']),
            new \Twig_SimpleFunction('meta', [$this, 'metaDataAccess']),
            new \Twig_SimpleFunction('order', [$this, 'orderDataAccess']),
            new \Twig_SimpleFunction('product', [$this, 'productDataAccess']),
            new \Twig_SimpleFunction('stats', [$this, 'statDataAccess']),
        ];
    }

    /**
     * Provides access to the current logged administrator attributes using the accessors.
     *
     * @param  string $attribute the attribute to return
     * @param  array $options any needed options
     * @return string
     */
    public function adminDataAccess($attribute, $options = array())
    {
        return $this->dataAccess("Admin User", $attribute, $this->securityContext->getAdminUser(), $options);
    }

    /**
     * Provides access to an attribute of the current brand
     *
     * @param  string $attribute the attribute to return
     * @param  array $options any needed options
     * @param string $lang force the lang parameter
     * @return null|string the value of the requested attribute
     */
    public function brandDataAccess($attribute, $options = array(), $lang = null)
    {
        $brandId = $this->request->get('brand_id');

        if ($brandId === null) {
            $productId = $this->request->get('product_id');

            if ($productId !== null) {
                if (null !== $product = ProductQuery::create()->findPk($productId)) {
                    $brandId = $product->getBrandId();
                }
            }
        }

        if ($brandId !== null) {
            return $this->dataAccessWithI18n(
                "Brand",
                $attribute,
                BrandQuery::create()->filterByPrimaryKey($brandId),
                $lang,
                $options
            );
        }

        return null;
    }

    /**
     * Retrieve meta data associated to an element
     *
     * params should contain at least key an id attributes. Thus it will return
     * an array of associated data.
     *
     * If meta argument is specified then it will return an unique value.
     *
     * @param string $key
     * @param string $id
     * @param $meta
     *
     * @throws \InvalidArgumentException
     *
     * @return string|array|null
     */
    public function metaDataAccess($key, $id, $meta = null)
    {
        $cacheKey = sprintf('meta_%s_%s_%s', $meta, $key, $id);

        $out = null;

        if (array_key_exists($cacheKey, self::$dataAccessCache)) {
            return self::$dataAccessCache[$cacheKey];
        }

        if ($key !== null && $id !== null) {
            if ($meta === null) {
                $out = MetaDataQuery::getAllVal($key, (int) $id);
            } else {
                $out = MetaDataQuery::getVal($meta, $key, (int) $id);
            }
        } else {
            throw new \InvalidArgumentException("key and id attributes are required in meta access function");
        }

        self::$dataAccessCache[$cacheKey] = $out;

        return $out;
    }

    /**
     * @param string $key with stat you want : sales or orders
     * @param string $startDate stat beginning date
     * @param string $endDate stat ending date
     * @param bool $includeShipping
     * @return int
     */
    public function statDataAccess($key, $startDate, $endDate, $includeShipping = false)
    {
        if ($startDate == 'today') {
            $start = new \DateTime();
            $start->setTime(0, 0, 0);
        } elseif ($startDate == 'yesterday') {
            $start = new \DateTime();
            $start->setTime(0, 0, 0);
            $start->modify('-1 day');
        } elseif ($startDate == 'this_month') {
            $start = new \DateTime();
            $start->modify('first day of this month');
            $start->setTime(0, 0, 0);
        } elseif ($startDate == 'last_month') {
            $start = new \DateTime();
            $start->modify('first day of last month');
            $start->setTime(0, 0, 0);
        } elseif ($startDate == 'this_year') {
            $start = new \DateTime();
            $start->modify('first day of January this year');
            $start->setTime(0, 0, 0);
        } elseif ($startDate == 'last_year') {
            $start = new \DateTime();
            $start->modify('first day of December last year');
            $start->setTime(0, 0, 0);
        } else {
            try {
                $start = new \DateTime($startDate);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException(
                    sprintf("invalid startDate attribute '%s' in stats access function", $startDate)
                );
            }
        }

        if ($endDate == 'today') {
            $end = new \DateTime();
            $end->setTime(0, 0, 0);
        } elseif ($endDate == 'yesterday') {
            $end = new \DateTime();
            $end->setTime(0, 0, 0);
            $end->modify('-1 day');
        } elseif ($endDate == 'this_month') {
            $end = new \DateTime();
            $end->modify('last day of this month');
            $end->setTime(0, 0, 0);
        } elseif ($endDate == 'last_month') {
            $end = new \DateTime();
            $end->modify('last day of last month');
            $end->setTime(0, 0, 0);
        } elseif ($endDate == 'this_year') {
            $end = new \DateTime();
            $end->modify('last day of December this year');
            $end->setTime(0, 0, 0);
        } elseif ($endDate == 'last_year') {
            $end = new \DateTime();
            $end->modify('last day of January last year');
            $end->setTime(0, 0, 0);
        } else {
            try {
                $end = new \DateTime($endDate);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException(
                    sprintf("invalid endDate attribute '%s' in stats access function", $endDate)
                );
            }
        }

        switch ($key) {
            case 'sales':
                return OrderQuery::getSaleStats($start, $end, $includeShipping);
                break;
            case 'orders':
                return OrderQuery::getOrderStats($start, $end, array(1, 2, 3, 4));
                break;
        }

        throw new \InvalidArgumentException(
            sprintf("invalid key attribute '%s' in stats access function", $key)
        );
    }

    /**
     * @param string $key the config key
     * @param null $default default value if there is no value for the given key
     * @return mixed
     */
    public function configDataAccess($key, $default = null)
    {
        return ConfigQuery::read($key, $default);
    }

    public function orderDataAccess($attribute)
    {
        $order = $this->request->getSession()->getOrder();
        switch ($attribute) {
            case 'untaxed_postage':
                return $order->getUntaxedPostage();
            case 'postage':
                return $order->getPostage();
            case 'postage_tax':
                return $order->getPostageTax();
            case 'discount':
                return $order->getDiscount();
            case 'delivery_address':
                return $order->getChoosenDeliveryAddress();
            case 'invoice_address':
                return $order->getChoosenInvoiceAddress();
            case 'delivery_module':
                return $order->getDeliveryModuleId();
            case 'payment_module':
                return $order->getPaymentModuleId();
            case 'has_virtual_product':
                return $order->hasVirtualProduct();

        }

        throw new \InvalidArgumentException(sprintf("%s has no '%s' attribute", 'Order', $attribute));
    }

    public function cartDataAccess($attribute)
    {
        if (array_key_exists('currentCountry', self::$dataAccessCache)) {
            $taxCountry = self::$dataAccessCache['currentCountry'];
        } else {
            $taxCountry = $this->taxEngine->getDeliveryCountry();
            self::$dataAccessCache['currentCountry'] = $taxCountry;
        }

        $cart = $this->request->getSession()->getSessionCart($this->dispatcher);

        $result = "";
        switch ($attribute) {
            case "count_product":
                $result = $cart->getCartItems()->count();
                break;
            case "count_item":
                $count_allitem = 0;
                foreach ($cart->getCartItems() as $cartItem) {
                    $count_allitem += $cartItem->getQuantity();
                }
                $result = $count_allitem;
                break;
            case "total_price":
                $result = $cart->getTotalAmount();
                break;
            case "total_taxed_price":
                $result = $cart->getTaxedAmount($taxCountry);
                break;
            case "total_taxed_price_without_discount":
                $result = $cart->getTaxedAmount($taxCountry, false);
                break;
            case "is_virtual":
                $result = $cart->isVirtual();
                break;
            case "total_vat":
                $result = $cart->getTotalVAT($taxCountry);
                break;
        }

        return $result;
    }

    /**
     * Provides access to an attribute of the current lang
     *
     * @param  string $attribute the attribute to return
     * @param  array $options any needed options
     * @return string
     */
    public function langDataAccess($attribute, $options = array())
    {
        return $this->dataAccess("Lang", $attribute, $this->request->getSession()->getLang(), $options);
    }

    /**
     * Provides access to an attribute for the default country
     *
     * @param  string $attribute the attribute to return
     * @param  array $options any needed options
     * @param string $lang force the lang parameter
     * @return null|string the value of the requested attribute
     */
    public function countryDataAccess($attribute, $options = array(), $lang = null)
    {
        return $this->dataAccessWithI18n(
            "defaultCountry",
            $attribute,
            CountryQuery::create()->filterByByDefault(1)->limit(1),
            $lang,
            $options
        );
    }

    /**
     * Provides access to an attribute of the current currency
     *
     * @param  string $attribute the attribute to return
     * @param  array $options any needed options
     * @param string $lang force the lang parameter
     * @return null|string the value of the requested attribute
     */
    public function currencyDataAccess($attribute, $options = array(), $lang = null)
    {
        $currency = $this->request->getSession()->getCurrency();

        if ($currency) {
            return $this->dataAccessWithI18n(
                "Currency",
                $attribute,
                CurrencyQuery::create()->filterByPrimaryKey($currency->getId()),
                $lang,
                $options,
                ["NAME"]
            );
        }

        return '';
    }

    /**
     * Provides access to an attribute of the current customer
     *
     * @param  string $attribute the attribute to return
     * @param  array $options any needed options
     * @return string
     */
    public function customerDataAccess($attribute, $options = array())
    {
        return $this->dataAccess("Customer User", $attribute, $this->securityContext->getCustomerUser(), $options);
    }

    /**
     * Provides access to an attribute of the current folder
     *
     * @param  string $attribute the attribute to return
     * @param  array $options any needed options
     * @param string $lang force the lang parameter
     * @return null|string the value of the requested attribute
     */
    public function folderDataAccess($attribute, $options = array(), $lang = null)
    {
        $folderId = $this->request->get('folder_id');

        if ($folderId === null) {
            $contentId = $this->request->get('content_id');

            if ($contentId !== null) {
                if (null !== $content = ContentQuery::create()->findPk($contentId)) {
                    $folderId = $content->getDefaultFolderId();
                }
            }
        }

        if ($folderId !== null) {
            return $this->dataAccessWithI18n(
                "Folder",
                $attribute,
                FolderQuery::create()->filterByPrimaryKey($folderId),
                $lang,
                $options
            );
        }

        return null;
    }

    /**
     * Provides access to an attribute of the current content
     *
     * @param  string $attribute the attribute to return
     * @param  array $options any needed options
     * @param string $lang force the lang parameter
     * @return string the value of the requested attribute
     */
    public function contentDataAccess($attribute, $options = array(), $lang = null)
    {
        $contentId = $this->request->get('content_id');

        if ($contentId !== null) {
            return $this->dataAccessWithI18n(
                "Content",
                $attribute,
                ContentQuery::create()->filterByPrimaryKey($contentId),
                $lang,
                $options
            );
        }

        return null;
    }

    /**
     * Provides access to an attribute of the current category
     *
     * @param  string $attribute the attribute to return
     * @param  array $options any needed options
     * @param string $lang force the lang parameter
     * @return string the value of the requested attribute
     */
    public function categoryDataAccess($attribute, $options = array(), $lang = null)
    {
        $categoryId = $this->request->get('category_id');

        if ($categoryId === null) {
            $productId = $this->request->get('product_id');

            if ($productId !== null) {
                if (null !== $product = ProductQuery::create()->findPk($productId)) {
                    $categoryId = $product->getDefaultCategoryId();
                }
            }
        }

        if ($categoryId !== null) {
            return $this->dataAccessWithI18n(
                "Category",
                $attribute,
                CategoryQuery::create()->filterByPrimaryKey($categoryId),
                $lang,
                $options
            );
        }

        return '';
    }

    /**
     * Provides access to an attribute of the current product
     *
     * @param  string $attribute the attribute to return
     * @param  array $options any needed options
     * @param string $lang force the lang parameter
     * @return string the value of the requested attribute
     */
    public function productDataAccess($attribute, $options = array(), $lang = null)
    {
        $productId = $this->request->get('product_id');

        if ($productId !== null) {
            return $this->dataAccessWithI18n(
                "Product",
                $attribute,
                ProductQuery::create()->filterByPrimaryKey($productId),
                $lang,
                $options
            );
        }

        return null;
    }

    protected function dataAccessWithI18n(
        $objectLabel,
        $attribute,
        ModelCriteria $search,
        $lang = null,
        $options = array(),
        $columns = array('TITLE', 'CHAPO', 'DESCRIPTION', 'POSTSCRIPTUM'),
        $foreignTable = null,
        $foreignKey = 'ID'
    ) {
        if (array_key_exists('data_' . $objectLabel, self::$dataAccessCache)) {
            $data = self::$dataAccessCache['data_' . $objectLabel];
        } else {
            if ($lang === null) {
                $lang = $this->request->getSession()->getLang()->getId();
            }

            ModelCriteriaTools::getI18n(
                false,
                $lang,
                $search,
                $this->request->getSession()->getLang()->getLocale(),
                $columns,
                $foreignTable,
                $foreignKey,
                true
            );

            $data = $search->findOne();

            self::$dataAccessCache['data_' . $objectLabel] = $data;
        }

        if ($data !== null) {
            $noGetterData = array();

            foreach ($columns as $column) {
                $noGetterData[$column] = $data->getVirtualColumn('i18n_' . $column);
            }

            return $this->dataAccess($objectLabel, $attribute, $data, $options, $noGetterData);
        } else {
            throw new NotFoundHttpException();
        }
    }

    protected function dataAccess($objectLabel, $attribute, $data, $options = array(), $noGetterData = array())
    {
        if (!empty($attribute)) {
            if (null != $data) {
                $keyAttribute = strtoupper($attribute);
                if (array_key_exists($keyAttribute, $noGetterData)) {
                    return $noGetterData[$keyAttribute];
                }

                $getter = sprintf("get%s", $this->underscoreToCamelcase($attribute));
                if (method_exists($data, $getter)) {
                    $return = $data->$getter();

                    if ($return instanceof \DateTime) {
                        if (array_key_exists("format", $options)) {
                            $format = $options["format"];
                        } else {
                            $format = DateTimeFormat::getInstance($this->request)->getFormat(
                                array_key_exists("output", $options) ? $options["output"] : null
                            );
                        }

                        $return = $return->format($format);
                    }

                    return $return;
                }

                throw new \InvalidArgumentException(sprintf("%s has no '%s' attribute", $objectLabel, $attribute));
            }
        }

        return '';
    }

    /**
     * Transcode an underscored string into a camel-cased string, eg. default_folder into DefaultFolder
     *
     * @param string $str the string to convert from underscore to camel-case
     *
     * @return string the camel cased string.
     */
    private function underscoreToCamelcase($str)
    {
        // Split string in words.
        $words = explode('_', strtolower($str));

        $return = '';

        foreach ($words as $word) {
            $return .= ucfirst(trim($word));
        }

        return $return;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'data_access_function';
    }
}
