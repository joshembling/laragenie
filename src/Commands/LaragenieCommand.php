<?php

namespace JoshEmbling\Laragenie\Commands;

use Illuminate\Console\Command;

class LaragenieCommand extends Command
{
    public $signature = 'laragenie';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
