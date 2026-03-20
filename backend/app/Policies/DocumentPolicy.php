<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        if ($user->isStaff() || $document->owner_id === $user->id) {
            return true;
        }

        return $document->collaborators()
            ->where('users.id', $user->id)
            ->exists();
    }

    public function update(User $user, Document $document): bool
    {
        if ($user->isStaff() || $document->owner_id === $user->id) {
            return true;
        }

        return $document->collaborators()
            ->where('users.id', $user->id)
            ->whereIn('permission', ['edit', 'admin'])
            ->exists();
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->isStaff() || $document->owner_id === $user->id;
    }

    public function share(User $user, Document $document): bool
    {
        if ($user->isStaff() || $document->owner_id === $user->id) {
            return true;
        }

        return $document->collaborators()
            ->where('users.id', $user->id)
            ->where('permission', 'admin')
            ->exists();
    }
}
