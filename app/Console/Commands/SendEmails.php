<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'echo:date';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '输出日期和时间';

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
        //
        $time = date("Y-m-d H:i:s\n");
        file_put_contents('/wwwroot/oop/new_special/public/error/date.log',$time,FILE_APPEND);
    }
}
