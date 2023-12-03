<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('laragenie_responses', function (Blueprint $table) {
            $table->id();
            $table->text('question', 50000);
            $table->text('answer', 50000)->nullable();
            $table->double('cost', 8, 2)->nullable();
            $table->json('vectors')->default(new Expression('(JSON_ARRAY())'));
            $table->timestamps();
        });
    }
};
