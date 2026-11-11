<?php

namespace App\Policies;

use App\Models\Reenrollment;
use App\Models\User;

class ReeenrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Reenrollment $reenrollment): bool
    {
        return $user->hasRole('admin');
    }

    public function approve(User $user, Reenrollment $reenrollment): bool
    {
        return $user->hasRole('admin') && $reenrollment->status === 'pending';
    }

    public function reject(User $user, Reenrollment $reenrollment): bool
    {
        return $user->hasRole('admin') && $reenrollment->status === 'pending';
    }
}
