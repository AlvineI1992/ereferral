<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReferralFacilityReportRequest;
use App\Http\Requests\ReferralFacilityPatientListRequest;
use App\Services\ReferralFacilityReportService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReferralFacilityReportController extends Controller
{
    public function __construct(private readonly ReferralFacilityReportService $reportService) {}

    public function index(): Response
    {
        return Inertia::render('Reports/ReferralFacility');
    }

    public function data(ReferralFacilityReportRequest $request): JsonResponse
    {
        return response()->json($this->reportService->report($request->validated(), $request->user()));
    }

    public function patients(ReferralFacilityPatientListRequest $request): JsonResponse
    {
        return response()->json($this->reportService->patients($request->validated(), $request->user()));
    }

    public function csv(ReferralFacilityReportRequest $request): StreamedResponse
    {
        $report = $this->reportService->report($request->validated(), $request->user());
        $filename = "referrals-by-facility-{$report['filters']['date_from']}-to-{$report['filters']['date_to']}.csv";

        return response()->streamDownload(function () use ($report) {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['Facility Code', 'Receiving Facility', 'Facility Type', 'Referrals Sent', 'Sent by RHU', 'Received', 'Pending', 'Receipt Rate']);

            foreach ($report['data'] as $row) {
                fputcsv($output, [
                    $this->safeCsvValue($row['facility_code']),
                    $this->safeCsvValue($row['facility_name']),
                    $this->safeCsvValue($row['facility_type']),
                    $row['sent_count'],
                    $row['rhu_sent_count'],
                    $row['received_count'],
                    $row['pending_count'],
                    $row['receipt_rate'].'%',
                ]);
            }

            fputcsv($output, []);
            fputcsv($output, ['Referrals Sent by RHU']);
            fputcsv($output, ['RHU Code', 'Rural Health Unit', 'Destination Facilities', 'Sent', 'Received', 'Pending', 'Receipt Rate']);

            foreach ($report['rhu_data'] as $row) {
                fputcsv($output, [
                    $this->safeCsvValue($row['rhu_code']),
                    $this->safeCsvValue($row['rhu_name']),
                    $row['destination_count'],
                    $row['sent_count'],
                    $row['received_count'],
                    $row['pending_count'],
                    $row['receipt_rate'].'%',
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function safeCsvValue(string $value): string
    {
        return preg_match('/^[=+\-@]/', ltrim($value)) ? "'{$value}" : $value;
    }
}
