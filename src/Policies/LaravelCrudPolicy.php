<?php

namespace XcentricItFoundation\LaravelCrudController\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class LaravelCrudPolicy
{
    use HandlesAuthorization;

    public function readOne(?Authenticatable $user, Model $model): bool
    {
        return true;
    }

    public function readMore(?Authenticatable $user): bool
    {
        return true;
    }

    public function create(?Authenticatable $user): bool
    {
        return true;
    }

    public function update(?Authenticatable $user, Model $model): bool
    {
        return true;
    }

    public function delete(?Authenticatable $user, Model $model): bool
    {
        return true;
    }

    public function massCreate(?Authenticatable $user): bool
    {
        return true;
    }

    public function massDelete(?Authenticatable $user): bool
    {
        return true;
    }
}
