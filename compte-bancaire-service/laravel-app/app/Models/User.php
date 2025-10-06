<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * This is a placeholder User model for the Account microservice.
 * The actual User data resides in the User microservice.
 * This model is only used for reference and should not be used directly.
 */
class User extends Model
{
    // This model is not used in this microservice
    // User data is managed by the User microservice
    protected $table = null;
    
    public function __construct(array $attributes = [])
    {
        throw new \Exception('User model should not be instantiated in Account microservice. Use HTTP calls to User service instead.');
    }
}
