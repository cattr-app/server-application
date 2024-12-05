<?php

namespace App\Http\Requests\Reports\UniversalReport;

use App\Enums\UniversalReportType;
use App\Enums\UniversalReportBase;
use App\Exceptions\Entities\InvalidMainException;
use App\Http\Requests\CattrFormRequest;
use Exception;
use Illuminate\Validation\Rules\Enum;

class UniversalReportStoreRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return auth()->check();
    }

    public function _rules(): array
    {
        $enumCase = UniversalReportBase::tryFrom(request('base'));
        switch ($enumCase) {
            case UniversalReportBase::PROJECT:
                $table = 'projects';
                break;
            case UniversalReportBase::USER:
                $table = 'users';
                break;
            case UniversalReportBase::TASK:
                $table = 'tasks';
                break;
            default:
                return throw new InvalidMainException();

        }
        return [
            'name' => 'required|string',
            'base' => ['required', new Enum(UniversalReportBase::class)],
            'fields' => 'required|array',
            'fields.*' => 'array',
            'fields.*.*' => 'required|string|in:'.implode(',', array_map(fn($item) => implode(',', $item), $enumCase->fields())),
            'dataObjects' => 'required|array',
            'dataObjects.*' => "required|int|exists:$table,id",
            'charts' => 'nullable|array',
            'charts.*' => 'required|string|in:'.implode(',', array_map(fn($item) => $item, $enumCase->charts())),
            'type' => ['required', 'string', new Enum(UniversalReportType::class)]
        ];
    }
}
