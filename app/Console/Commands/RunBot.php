<?php

namespace App\Console\Commands;

use App\Discord\Bot;
use Illuminate\Console\Command;

class RunBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to run the bot';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $discord = (new Bot())->discord();
        $discord->run();

        return Command::SUCCESS;
    }
}
