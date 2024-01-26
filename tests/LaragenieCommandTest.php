<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JoshEmbling\Laragenie\Commands\LaragenieCommand;
use PHPUnit\Framework\TestCase;

class LaragenieCommandTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }
}
