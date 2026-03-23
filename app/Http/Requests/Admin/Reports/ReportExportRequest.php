<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Reports;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;

class ReportExportRequest extends ExportReportRequest
{
    use InteractsWithValidationPayload;
}
