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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Thelia\Core\Security\Exception\AuthenticationException;
use Thelia\Core\Security\Exception\AuthorizationException;
use Thelia\Core\Security\SecurityContext;
use TheliaTwig\Template\TokenParsers\Auth;

/**
 * Class Security
 * @package TheliaTwig\Template\Extension
 * @author Manuel Raynaud <manu@thelia.net>
 */
class Security extends BaseExtension
{
    protected $dispatcher;
    protected $request;
    private $securityContext;

    public function __construct(Request $request, EventDispatcherInterface $dispatcher, SecurityContext $securityContext)
    {
        $this->securityContext = $securityContext;
        $this->request = $request;
        $this->dispatcher = $dispatcher;
    }

    public function getTokenParsers()
    {
        return [
            new Auth()
        ];
    }

    public function checkAuth($parameters)
    {
        $roles = $this->explode($this->getParam($parameters, 'role'));
        $resources = $this->explode($this->getParam($parameters, 'resource'));
        $modules = $this->explode($this->getParam($parameters, 'module'));
        $accesses = $this->explode($this->getParam($parameters, 'access'));

        if (! $this->securityContext->isGranted($roles, $resources, $modules, $accesses)) {
            if (! $this->securityContext->hasLoggedInUser()) {
                // The current user is not logged-in.
                $ex = new AuthenticationException(
                    sprintf(
                        "User not granted for roles '%s', to access resources '%s' with %s.",
                        implode(',', $roles),
                        implode(',', $resources),
                        implode(',', $accesses)
                    )
                );

                $loginTpl = $this->getParam($parameters, 'login_tpl');

                if (null != $loginTpl) {
                    $ex->setLoginTemplate($loginTpl);
                }
            } else {
                // We have a logged-in user, who do not have the proper permission. Issue an AuthorizationException.
                $ex = new AuthorizationException(
                    sprintf(
                        "User not granted for roles '%s', to access resources '%s' with %s.",
                        implode(',', $roles),
                        implode(',', $resources),
                        implode(',', $accesses)
                    )
                );
            }

            throw $ex;
        }
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'security';
    }
}
