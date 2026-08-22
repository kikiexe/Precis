<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Payroll\DisbursePayrollRequest;
use App\Http\Requests\Payroll\ExportPayrollCsvRequest;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class PayrollController
{
    public function __construct(
        private readonly PayrollService $payrollService,
    ) {
    }

    /**
     * ambil pratinjau kalkulasi penggajian seluruh staf (khusus OWNER dan ADMIN)
     */
    public function preview(Request $request): JsonResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $periodStart = (string) ($request->query('period_start') ?? Carbon::today()->startOfMonth()->toDateString());
        $periodEnd = (string) ($request->query('period_end') ?? Carbon::today()->endOfMonth()->toDateString());
        $branchId = $request->query('branch_id') ? (string) $request->query('branch_id') : null;

        $preview = $this->payrollService->calculatePreview(
            workspaceId: $workspaceId,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            branchId: $branchId,
        );

        return new JsonResponse([
            'message' => 'Pratinjau rekapitulasi penggajian berhasil dimuat.',
            'data' => $preview,
        ], Response::HTTP_OK);
    }

    /**
     * eksekusi pencairan gaji staf (disburse) dan pelunasan kasbon (khusus OWNER dan ADMIN)
     */
    public function disburse(DisbursePayrollRequest $request): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $workspaceId = (string) $request->attributes->get('current_workspace_id');

        $result = $this->payrollService->disbursePayroll(
            admin: $admin,
            workspaceId: $workspaceId,
            periodStart: (string) $request->validated('period_start'),
            periodEnd: (string) $request->validated('period_end'),
            branchId: $request->validated('branch_id'),
        );

        return new JsonResponse([
            'message' => 'Pencairan penggajian berhasil dieksekusi.',
            'data' => $result,
        ], Response::HTTP_OK);
    }

    /**
     * ekspor data penggajian ke file CSV transfer massal bank (BCA / Mandiri)
     */
    public function exportCsv(ExportPayrollCsvRequest $request): HttpResponse
    {
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $periodStart = (string) $request->validated('period_start');
        $periodEnd = (string) $request->validated('period_end');
        $format = (string) ($request->validated('format') ?? 'BCA');
        $branchId = $request->validated('branch_id');

        $csv = $this->payrollService->generateBankCsv(
            workspaceId: $workspaceId,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            bankFormat: $format,
            branchId: $branchId,
        );

        $filename = sprintf('payroll_%s_%s.csv', strtolower($format), $periodStart);

        return response($csv, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * ambil rincian slip gaji digital staf yang sedang login
     */
    public function mySlip(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $workspaceId = (string) $request->attributes->get('current_workspace_id');
        $periodStart = $request->query('period_start') ? (string) $request->query('period_start') : null;
        $periodEnd = $request->query('period_end') ? (string) $request->query('period_end') : null;

        $slip = $this->payrollService->getMyPayrollSlip(
            user: $user,
            workspaceId: $workspaceId,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
        );

        return new JsonResponse([
            'message' => 'Rincian slip gaji berhasil dimuat.',
            'data' => $slip,
        ], Response::HTTP_OK);
    }
}
