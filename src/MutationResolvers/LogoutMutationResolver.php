<?php

declare(strict_types=1);

namespace PoPSitesWassup\UserStateMutations\MutationResolvers;

use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\Hooks\Facades\HooksAPIFacade;
use PoP\ComponentModel\State\ApplicationState;
use PoP\ComponentModel\MutationResolvers\AbstractMutationResolver;

class LogoutMutationResolver extends AbstractMutationResolver
{
    public function validateErrors(array $form_data): ?array
    {
        $errors = [];
        // If the user is not logged in, then return the error
        $vars = ApplicationState::getVars();
        if (!$vars['global-userstate']['is-user-logged-in']) {
            $errors[] = TranslationAPIFacade::getInstance()->__('You are not logged in.', 'pop-application');
        }
        return $errors;
    }
    /**
     * @return mixed
     */
    public function execute(array $form_data)
    {
        $vars = ApplicationState::getVars();
        $user_id = $vars['global-userstate']['current-user-id'];

        $cmsuseraccountapi = \PoP\UserAccount\FunctionAPIFactory::getInstance();
        $cmsuseraccountapi->logout();

        // Modify the routing-state with the newly logged in user info
        PoP_UserLogin_Engine_Utils::calculateAndSetVarsUserState(ApplicationState::$vars);

        HooksAPIFacade::getInstance()->doAction('gd:user:loggedout', $user_id);
        return $user_id;
    }
}
