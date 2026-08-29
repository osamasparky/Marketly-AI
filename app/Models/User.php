<?php

namespace App\Models;

use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;

/**
 * Compatibility Proxy for Laravel Framework conventions.
 * Primary persistence definition resides in App\Domains\Identity\Infrastructure\Persistence\Models\UserModel.
 */
class User extends UserModel
{
    // Persistence definition is inherited from Infrastructure UserModel
}
