<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CacheClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cache-clear';

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
        $this->info('Running php artisan optimize:clear...');
        $this->call('optimize:clear');

        $this->info('Running php artisan optimize...');
        $this->call('optimize');

        $this->info('Cache cleared and application optimized successfully!');
    }
}
