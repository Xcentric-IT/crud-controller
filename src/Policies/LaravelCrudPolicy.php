<?php

namespace XcentricItFoundation\LaravelCrudController\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Access\HandlesAuthorization;

class LaravelCrudPolicy
{
    use HandlesAuthorization;

    public function readOne(?Authenticatable $user, $model)
    {
        return true;
    }

    public function readMore(?Authenticatable $user)
    {
        return true;
    }

    public function create(?Authenticatable $user)
    {
        return true;
    }

    public function update(?Authenticatable $user, $model)
    {
        return true;
    }

    public function delete(?Authenticatable $user, $model)
    {
        return true;
    }

}
