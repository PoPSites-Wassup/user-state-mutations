<?php

declare(strict_types=1);

namespace PoPSitesWassup\UserStateMutations\MutationResolvers;

use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\Hooks\Facades\HooksAPIFacade;
use PoP\ComponentModel\State\ApplicationState;
use PoP\ComponentModel\Misc\GeneralUtils;
use PoP\ComponentModel\MutationResolvers\AbstractMutationResolver;
use PoP\ComponentModel\Error;

class LoginMutationResolver extends AbstractMutationResolver
{
    public function validateErrors(array $form_data): ?array
    {
        $errors = [];
        $username_or_email = $form_data['username_or_email'];
        $pwd = $form_data['pwd'];

        if (!$username_or_email) {
            $errors[] = TranslationAPIFacade::getInstance()->__('Please supply your username or email', 'ure-pop');
        }
        if (!$pwd) {
            $errors[] = TranslationAPIFacade::getInstance()->__('Please supply your password', 'ure-pop');
        }

        $vars = ApplicationState::getVars();
        if ($vars['global-userstate']['is-user-logged-in']) {
            $user_id = $vars['global-userstate']['current-user-id'];
            $cmsusersapi = \PoPSchema\Users\FunctionAPIFactory::getInstance();
            $cmsuseraccountapi = \PoP\UserAccount\FunctionAPIFactory::getInstance();
            $errors[] = sprintf(
                TranslationAPIFacade::getInstance()->__('You are already logged in as <a href="%s">%s</a>, <a href="%s">logout</a>?', 'pop-application'),
                $cmsusersapi->getUserURL($user_id),
                $cmsusersapi->getUserDisplayName($user_id),
                $cmsuseraccountapi->getLogoutURL()
            );
        }
        return $errors;
    }

    /**
     * @return mixed
     */
    public function execute(array $form_data)
    {
        // If the user is already logged in, then return the error
        $cmsusersapi = \PoPSchema\Users\FunctionAPIFactory::getInstance();
        $cmsusersresolver = \PoPSchema\Users\ObjectPropertyResolverFactory::getInstance();
        $cmsuseraccountapi = \PoP\UserAccount\FunctionAPIFactory::getInstance();

        $username_or_email = $form_data['username_or_email'];
        $pwd = $form_data['pwd'];

        // Find out if it was a username or an email that was provided
        $is_email = strpos($username_or_email, '@');
        if ($is_email) {
            $user = $cmsusersapi->getUserByEmail($username_or_email);
            if (!$user) {
                return new Error(
                    'no-user',
                    TranslationAPIFacade::getInstance()->__('There is no user registered with that email address.')
                );
            }
            $username = $cmsusersresolver->getUserLogin($user);
        } else {
            $username = $username_or_email;
        }

        $credentials = array(
            'login' => $username,
            'password' => $pwd,
            'remember' => true,
        );
        $loginResult = $cmsuseraccountapi->login($credentials);

        if (GeneralUtils::isError($loginResult)) {
            return $loginResult;
        }

        $user = $loginResult;

        // Modify the routing-state with the newly logged in user info
        PoP_UserLogin_Engine_Utils::calculateAndSetVarsUserState(ApplicationState::$vars);

        $userID = $cmsusersresolver->getUserId($user);
        HooksAPIFacade::getInstance()->doAction('gd:user:loggedin', $userID);
        return $userID;
    }
}
