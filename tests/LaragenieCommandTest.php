<?php

use JoshEmbling\Laragenie\Commands\LaragenieCommand;
use JoshEmbling\Laragenie\Models\Laragenie as LaragenieModel;

test('welcome choice `q` reverts to userAction when empty string is passed', function () {
    $this->artisan(LaragenieCommand::class)
        ->expectsQuestion('What do you want to do?', 'q')
        ->expectsQuestion('What is your question for '.config('laragenie.bot.name'), '')
        ->expectsOutputToContain('You must provide a question.')
        ->expectsQuestion('Do you want to do something else?', 'x')
        ->assertExitCode(0);
});

test('welcome choice `q` executes userQuestion and fetches from database when existing string is passed', function () {
    LaragenieModel::create([
        'question' => 'test',
        'answer' => 'This is a test',
    ]);

    $this->artisan(LaragenieCommand::class)
        ->expectsQuestion('What do you want to do?', 'q')
        ->expectsQuestion('What is your question for '.config('laragenie.bot.name'), 'Test')
        ->expectsOutputToContain('This is a test')
        ->expectsQuestion('Do you want to do something else?', 'x')
        ->assertExitCode(0);
});

test('welcome choice `o` returns a string and reverts to userAction', function () {
    $this->artisan(LaragenieCommand::class)
        ->expectsQuestion('What do you want to do?', 'o')
        ->expectsOutputToContain('You can contact @joshembling on Github to suggest another feature.')
        ->expectsQuestion('Do you want to do something else?', 'x')
        ->assertExitCode(0);
});
