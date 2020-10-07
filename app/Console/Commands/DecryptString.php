<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Crypt;

class DecryptString extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'decrypt:string {string}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Decrypts an input string.';

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
     * @return mixed
     */
    public function handle()
    {
        $str = $this->argument('string');
        $this->info(Crypt::decrypt($str));
    }
}
