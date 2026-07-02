<?php

namespace Database\Seeders;

use App\Support\AnswerNormalizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestAnswerOneSeeder extends Seeder
{
    public function run(): void
    {
        $hash = Hash::make(AnswerNormalizer::normalize('1'));

        DB::table('location_tasks')
            ->whereNotNull('answer_hash')
            ->update(['answer_hash' => $hash]);

        DB::table('building_tasks')
            ->whereNotNull('answer_hash')
            ->update(['answer_hash' => $hash]);
    }
}
