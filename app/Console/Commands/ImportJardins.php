<?php

namespace App\Console\Commands;

use App\Http\Controllers\DataController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportJardins extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:jardins';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import des Jardins';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $data = new DataController();
        $data->jardins();
    }
}
