<?php

namespace App\Policies;

use App\Models\Test;
use App\Models\User;

class TestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Test $test): bool
    {
        return $user->id === $test->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Test $test): bool
    {
        return $user->id === $test->user_id;
    }

    public function delete(User $user, Test $test): bool
    {
        return $user->id === $test->user_id;
    }

    public function restore(User $user, Test $test): bool
    {
        return $user->id === $test->user_id;
    }

    public function forceDelete(User $user, Test $test): bool
    {
        return $user->id === $test->user_id;
    }
}
