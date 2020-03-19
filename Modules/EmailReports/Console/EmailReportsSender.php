<?php

namespace Modules\EmailReports\Console;

use Illuminate\Console\Command;
use Modules\EmailReports\Entities\ReportsSender;

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
     */
    public function __construct(ReportsSender $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    public function handle(): void
    {
        $this->repository->send();
    }
}
