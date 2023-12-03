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

        // Set environment variables
        putenv('OPENAI_API_KEY='.$this->faker->uuid);
        putenv('PINECONE_API_KEY='.$this->faker->uuid);
        putenv('PINECONE_ENVIRONMENT="starter"');
        putenv('PINECONE_INDEX="test-index"');
    }

    public function test_this()
    {
        //$command = new LaragenieCommand();

        //$command->handle();
    }
}
