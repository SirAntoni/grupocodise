<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.view');
    }

    public function create(User $user): bool
    {
        return $user->can('invoices.manage');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.manage')
            && in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Rejected], true);
    }

    public function issue(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.manage') && $invoice->status === InvoiceStatus::Draft;
    }

    public function resend(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.manage')
            && $invoice->electronicDocument
            && ! $invoice->electronicDocument->sunat_status->isAccepted()
            && $invoice->status !== InvoiceStatus::Draft;
    }

    public function annul(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.manage') && $invoice->status === InvoiceStatus::Accepted;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->can('invoices.manage') && $invoice->status === InvoiceStatus::Draft;
    }
}
