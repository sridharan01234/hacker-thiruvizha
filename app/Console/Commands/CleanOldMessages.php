<?php

namespace App\Console\Commands;

use App\Models\Message;
use Illuminate\Console\Command;

class CleanOldMessages extends Command
{
    protected $signature = 'messages:clean';
    protected $description = 'Clean old messages from the database';

    public function handle()
    {
        $cutoff = now();
        $deleted = Message::where('created_at', '<', $cutoff)->delete();
        
        $this->info("Deleted {$deleted} old messages");
    }
}