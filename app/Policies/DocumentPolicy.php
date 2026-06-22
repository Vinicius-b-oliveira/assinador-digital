<?php

namespace App\Policies;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        return $this->owns($user, $document);
    }

    public function update(User $user, Document $document): bool
    {
        return $this->owns($user, $document)
            && $document->status === DocumentStatus::Draft;
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->owns($user, $document)
            && $document->status === DocumentStatus::Draft;
    }

    public function send(User $user, Document $document): bool
    {
        return $this->owns($user, $document)
            && $document->status === DocumentStatus::Draft;
    }

    private function owns(User $user, Document $document): bool
    {
        return $user->id === $document->user_id;
    }
}
