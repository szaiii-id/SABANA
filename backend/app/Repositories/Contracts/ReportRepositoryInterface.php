<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

interface ReportRepositoryInterface
{
    public function budgetSummary(array $filters): array;
    public function programRecipients(array $filters): array;
    public function mostAppliedPrograms(array $filters): array;
    public function citizenRegisteredByAdmin(array $filters): array;
    public function readyForDisbursement(array $filters): array;
    public function pendingEvaluation(array $filters): array;
    public function revokedRecipients(array $filters): array;
    public function approvedRecipients(array $filters): array;
    public function disbursedRecipients(array $filters): array;
}