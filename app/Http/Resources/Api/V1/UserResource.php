<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    /**
     * The resource's attributes.
     *
     * @var array<int, string>
     */
    public $attributes = [
        'name',
        'email',
        'email_verified_at',
        'created_at',
        'updated_at',
    ];
}
