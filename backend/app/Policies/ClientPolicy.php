<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

/**
 * Company isolation is enforced by route binding (other companies' records
 * are "not found"); policies only add role rules on top.
 */
class ClientPolicy
{
    public function delete(User $user, Client $client): bool
    {
        return $user->isOwner();
    }
}
