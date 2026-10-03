<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

/**
 * Who may do what with a bill (decision P2):
 *  - view: anyone with "transactions.view" (Staff, Vet/Admin, Super Admin)
 *  - pay:  the cashier ("pos.manage"), only while there is a balance
 *  - void: "transactions.void" (Vet/Admin), only if it is not void yet
 */
class TransactionPolicy
{
    public function view(User $user, Transaction $transaction): bool
    {
        return $user->can('transactions.view');
    }

    public function pay(User $user, Transaction $transaction): bool
    {
        return $user->can('pos.manage') && in_array($transaction->status, ['unpaid', 'partial'], true);
    }

    public function void(User $user, Transaction $transaction): bool
    {
        return $user->can('transactions.void') && $transaction->status !== 'void';
    }
}
