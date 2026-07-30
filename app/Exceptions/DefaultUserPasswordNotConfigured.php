<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a user is provisioned before an administrator configured the
 * default password. The message carries no sensitive information.
 */
class DefaultUserPasswordNotConfigured extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('setting.user_provisioning.default_password.not_configured'));
    }
}
