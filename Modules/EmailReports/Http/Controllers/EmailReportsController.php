<?php

namespace Modules\EmailReports\Http\Controllers;

use App\Http\Controllers\Api\v1\ItemController;
use App\Models\Project;
use Event;
use Filter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\EmailReports\Models\EmailReports;
use Modules\EmailReports\Models\EmailReportsEmails;
use Modules\EmailReports\Models\EmailReportsProjects;

/**
 * Class EmailReportsController
 * @package Modules\EmailReports\Http\Controllers
 */
class EmailReportsController extends ItemController
{
    /**
     * @return array
     */
    public function getQueryWith(): array
    {
        return [
            'projects',
            'emails'
        ];
    }

    /**
     * Returns current item's class name
     *
     * @return string|Model
     */
    public function getItemClass(): string
    {
        return EmailReports::class;
    }

    /**
     * Returns validation rules for current item
     *
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'name'    => 'required|string',
            'emails.*'    => 'required|email',
            'frequency'    => 'required|int',
            'sending_day'    => 'required|date',
        ];
    }

    /**
     * Returns unique part of event name for current item
     *
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'email-reports';
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        Event::listen($this->getEventUniqueName('item.create.after'), static::class.'@'.'saveRelations');
        return parent::create($request);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function edit(Request $request): JsonResponse
    {
        Event::listen($this->getEventUniqueName('item.edit.after'), static::class.'@'.'saveRelations');
        return parent::edit($request);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function show(Request $request): JsonResponse
    {
        Filter::listen($this->getEventUniqueName('answer.success.item.show'), static function ($item) {
            $projectIds = collect($item->projects)->pluck('project_id')->toArray();
            $projects = Project::whereIn('id', $projectIds)
                        ->pluck('name')
                        ->toArray();

            $item->project_names = implode(',' . EmailReports::SPACE, $projects);
            $item->project_ids = $projectIds;
            $item->emails = $item->emails->pluck('email')->toArray();
            return $item->unsetRelations();
        });
        return parent::show($request);
    }

    /**
     * @param $emailReport EmailReports
     * @param $requestData array
     * @return EmailReports
     */
    public function saveRelations(EmailReports $emailReport, array $requestData): EmailReports
    {
        $projectIds = $requestData['project_ids'] ?? [];
        $requestEmails = $requestData['emails'] ?? [];

        $emailReport->projects()->delete();
        $emailReport->emails()->delete();

        $projects = [];
        foreach ($projectIds as $projectId) {
            $projects[] = new EmailReportsProjects([
                'project_id' => $projectId,
                'email_projects_id' => $emailReport->id,
            ]);
        }

        $emails = [];
        foreach ($requestEmails as $email) {
            $emails[] = new EmailReportsEmails([
                'email' => $email,
                'email_report_id' => $emailReport->id,
            ]);
        }

        $emailReport->projects()->saveMany($projects);
        $emailReport->emails()->saveMany($emails);
        return $emailReport;
    }
}
