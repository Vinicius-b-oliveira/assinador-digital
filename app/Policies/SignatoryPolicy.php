<?php

namespace App\Policies;

use App\Enums\DocumentStatus;
use App\Enums\SignatoryStatus;
use App\Models\Document;
use App\Models\Signatory;
use App\Models\User;

class SignatoryPolicy
{
    public function manage(User $user, Document $document): bool
    {
        return $user->id === $document->user_id
            && $document->status === DocumentStatus::Draft;
    }

    public function remind(User $user, Signatory $signatory): bool
    {
        return $user->id === $signatory->document->user_id
            && $signatory->document->status === DocumentStatus::Pending
            && $signatory->status === SignatoryStatus::Pending;
    }

    public function update(User $user, Signatory $signatory): bool
    {
        return $this->manage($user, $signatory->document);
    }

    public function delete(User $user, Signatory $signatory): bool
    {
        return $this->manage($user, $signatory->document);
    }
}
