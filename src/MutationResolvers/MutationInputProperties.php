<?php

declare(strict_types=1);

namespace PoPSitesWassup\UserStateMutations\MutationResolvers;

class MutationInputProperties
{
    public const USERNAME_OR_EMAIL = 'usernameOrEmail';
    public const PASSWORD = 'password';
    public const USER_LOGIN = 'userLogin';
    public const CODE = 'code';
    public const REPEAT_PASSWORD = 'repeatPassword';
}
