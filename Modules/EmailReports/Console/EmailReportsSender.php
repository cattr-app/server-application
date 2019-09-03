<?php


namespace Modules\EmailReports\Console;

use Illuminate\Console\Command;
use Modules\EmailReports\Http\Controllers\EmailReportsController;

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
    protected $description = 'Send email reports to users emails (working like export report on Projects Report tab).';

    /**
     * @var EmailReportsController
     */
    private $emailReportsController;

    /**
     * SendSavedReports constructor.
     * @param EmailReportsController $emailReportsController
     */
    public function __construct(EmailReportsController $emailReportsController)
    {
        parent::__construct();
        $this->emailReportsController = $emailReportsController;
    }

    public function handle()
    {
        $this->emailReportsController->send();
    }
}
