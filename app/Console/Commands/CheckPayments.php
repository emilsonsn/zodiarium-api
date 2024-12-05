<?php

namespace App\Console\Commands;

use App\Services\Routine\RoutineService;
use Illuminate\Console\Command;

class CheckPayments extends Command
{

    private $routineService;

    public function __construct(RoutineService $routineService = null)
    {
        parent::__construct();
        $this->routineService = $routineService;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->routineService->checkPayments();
    }
}
