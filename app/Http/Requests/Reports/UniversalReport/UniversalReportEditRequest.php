<?php

namespace App\Http\Requests\Reports\UniversalReport;

use App\Enums\ReportType;
use App\Enums\UniversalReport;
use App\Exceptions\Entities\InvalidMainException;
use App\Http\Requests\CattrFormRequest;
use Exception;
use Illuminate\Validation\Rules\Enum;

class UniversalReportEditRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return auth()->check();
    }

    public function _rules(): array
    {
        $enumCase = UniversalReport::tryFrom(request('main'));
        switch ($enumCase) {
            case UniversalReport::PROJECT:
                $table = 'projects';
                break;
            case UniversalReport::USER:
                $table = 'users';
                break;
            case UniversalReport::TASK:
                $table = 'tasks';
                break;
            default:
                return throw new InvalidMainException();

        }
        return [
            'id' => 'required|int|exists:universal_reports,id',
            'name' => 'required|string',
            'main' => ['required', new Enum(UniversalReport::class)],
            'fields' => 'required|array',
            'fields.*' => 'array',
            'fields.*.*' => 'required|string|in:'.implode(',', array_map(fn($item) => implode(',', $item), $enumCase->fields())),
            'dataObjects' => 'required|array',
            'dataObjects.*' => "required|int|exists:$table,id",
            'charts' => 'nullable|array',
            'charts.*' => 'required|string|in:'.implode(',', array_map(fn($item) => $item, $enumCase->charts())),
            'type' => ['required', 'string', new Enum(ReportType::class)],
        ];
    }
}
