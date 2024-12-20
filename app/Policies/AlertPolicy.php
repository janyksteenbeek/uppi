<?php

namespace App\Policies;

use App\Models\Alert;
use App\Models\User;

class AlertPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Alert $alert): bool
    {
        return $alert->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Alert $alert): bool
    {
        return $alert->user_id === $user->id;
    }

    public function delete(User $user, Alert $alert): bool
    {
        return $alert->user_id === $user->id;
    }

    public function restore(User $user, Alert $alert): bool
    {
        return $alert->user_id === $user->id;
    }

    public function forceDelete(User $user, Alert $alert): bool
    {
        return $alert->user_id === $user->id;
    }
}
