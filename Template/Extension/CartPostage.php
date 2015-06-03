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

use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Thelia\Model\AddressQuery;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Country;
use Thelia\Model\CountryQuery;
use Thelia\Model\Customer;
use Thelia\Model\ModuleQuery;
use Thelia\Module\BaseModule;
use Thelia\Module\Exception\DeliveryException;
use TheliaTwig\Template\TokenParsers\CartPostage as CartPostageTokenParser;

/**
 * Class CartPostage
 * @package TheliaTwig\Template\Extension
 * @author Manuel Raynaud <manu@thelia.net>
 */
class CartPostage extends \Twig_Extension
{

    /** @var \Thelia\Core\HttpFoundation\Request The Request */
    protected $request;

    /** @var ContainerInterface Service Container */
    protected $container = null;

    /** @var integer $countryId the id of country */
    protected $countryId = null;

    /** @var integer $deliveryId the id of the cheapest delivery  */
    protected $deliveryId = null;

    /** @var float $postage the postage amount */
    protected $postage = null;

    /** @var boolean $isCustomizable indicate if customer can change the country */
    protected $isCustomizable = true;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->request = $container->get('request');
    }

    public function getTokenParsers()
    {
        return [
            new CartPostageTokenParser()
        ];
    }

    public function postage(&$context)
    {
        $customer = $this->request->getSession()->getCustomerUser();
        $country = $this->getDeliveryCountry($customer);

        if (null !== $country) {
            $this->countryId = $country->getId();
            // try to get the cheapest delivery for this country
            $this->getCheapestDelivery($country);
        }

        $context['country_id'] = $this->countryId;
        $context['delivery_id'] = $this->deliveryId;
        $context['postage'] = $this->postage ?: 0.0;
        $context['is_customizable'] = $this->isCustomizable;
    }

    /**
     * Retrieve the delivery country for a customer
     *
     * The rules :
     *  - the country of the delivery address of the customer related to the
     *      cart if it exists
     *  - the country saved in cookie if customer have changed
     *      the default country
     *  - the default country for the shop if it exists
     *
     *
     * @param  \Thelia\Model\Customer $customer
     * @return \Thelia\Model\Country
     */
    protected function getDeliveryCountry(Customer $customer = null)
    {
        // get country from customer addresses
        if (null !== $customer) {
            $address = AddressQuery::create()
                ->filterByCustomerId($customer->getId())
                ->filterByIsDefault(1)
                ->findOne()
            ;

            if (null !== $address) {
                $this->isCustomizable = false;

                return $address->getCountry();
            }
        }

        // get country from cookie
        $cookieName = ConfigQuery::read('front_cart_country_cookie_name', 'fcccn');
        if ($this->request->cookies->has($cookieName)) {
            $cookieVal = $this->request->cookies->getInt($cookieName, 0);
            if (0 !== $cookieVal) {
                $country = CountryQuery::create()->findPk($cookieVal);
                if (null !== $country) {
                    return $country;
                }
            }
        }

        // get default country for store.
        try {
            $country = Country::getDefaultCountry();

            return $country;
        } catch (\LogicException $e) {
            ;
        }

        return null;
    }

    /**
     * Retrieve the cheapest delivery for country
     *
     * @param  \Thelia\Model\Country   $country
     * @return DeliveryModuleInterface
     */
    protected function getCheapestDelivery(Country $country)
    {
        $deliveryModules = ModuleQuery::create()
            ->filterByActivate(1)
            ->filterByType(BaseModule::DELIVERY_MODULE_TYPE, Criteria::EQUAL)
            ->find();
        ;

        /** @var \Thelia\Model\Module $deliveryModule */
        foreach ($deliveryModules as $deliveryModule) {
            $moduleInstance = $deliveryModule->getDeliveryModuleInstance($this->container);

            try {
                // Check if module is valid, by calling isValidDelivery(),
                // or catching a DeliveryException.
                if ($moduleInstance->isValidDelivery($country)) {
                    $postage = $moduleInstance->getPostage($country);
                    if (null === $this->postage || $this->postage > $postage) {
                        $this->postage = $postage;
                        $this->deliveryId = $deliveryModule->getId();
                    }
                }
            } catch (DeliveryException $ex) {
                // Module is not available
            }
        }
    }


    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'cart_postage';
    }
}
