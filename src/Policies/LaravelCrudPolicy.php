<?php

namespace XcentricItFoundation\LaravelCrudController\Policies;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LaravelCrudPolicy
{
    use HandlesAuthorization;

    public function readOne(User $user, $model)
    {
        return true;
    }

    public function readMore(User $user)
    {
        return true;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, $model)
    {
        return true;
    }

    public function delete(User $user, $model)
    {
        return true;
    }

}
