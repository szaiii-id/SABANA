<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Repositories\Contracts\ReportRepositoryInterface;

final readonly class ReportService
{
    public function __construct(
        private ReportRepositoryInterface $reportRepository
    ) {}

    public function budgetSummary(array $filters): array
    {
        return $this->reportRepository->budgetSummary($filters);
    }

    public function programRecipients(array $filters): array
    {
        return $this->reportRepository->programRecipients($filters);
    }

    public function mostAppliedPrograms(array $filters): array
    {
        return $this->reportRepository->mostAppliedPrograms($filters);
    }

    public function citizenRegisteredByAdmin(array $filters): array
    {
        return $this->reportRepository->citizenRegisteredByAdmin($filters);
    }

    public function readyForDisbursement(array $filters): array
    {
        return $this->reportRepository->readyForDisbursement($filters);
    }

    public function pendingEvaluation(array $filters): array
    {
        return $this->reportRepository->pendingEvaluation($filters);
    }

    public function revokedRecipients(array $filters): array
    {
        return $this->reportRepository->revokedRecipients($filters);
    }

    public function approvedRecipients(array $filters): array
    {
        return $this->reportRepository->approvedRecipients($filters);
    }

    public function disbursedRecipients(array $filters): array
    {
        return $this->reportRepository->disbursedRecipients($filters);
    }
}