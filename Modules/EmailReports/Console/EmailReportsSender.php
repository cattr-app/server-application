<?php

namespace Modules\EmailReports\Console;

use Illuminate\Console\Command;
use Modules\EmailReports\Entities\ReportsSender;

/**
 * Class EmailReportsSender
 * @package Modules\EmailReports\Console
 */
class EmailReportsSender extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'email-reports:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reports to users';

    /**
     * @var ReportsSender
     */
    private $repository;

    /**
     * SendSavedReports constructor.
     * @param ReportsSender $repository
     */
    public function __construct(ReportsSender $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    public function handle()
    {
        $this->repository->send();
    }
}
