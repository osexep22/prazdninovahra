<?php

use App\Support\AnswerNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $answers = [
            'ukol-1' => 'POSKOZENI SE TRESTA',
            'ukol-2' => '1942',
            'ukol-3' => 'KADIBUDKA',
            'ukol-4' => '8',
            'ukol-5' => 'VIP MRAVENCI',
            'ukol-6' => '217',
            'ukol-7' => 'DRATENICKA SKALA',
            'ukol-8' => 'MACESKA',
            'ukol-9' => '496',
            'ukol-10' => 'HRUSEN',
        ];

        foreach ($answers as $slug => $answer) {
            DB::table('location_tasks')
                ->join('locations', 'locations.id', '=', 'location_tasks.location_id')
                ->where('locations.slug', $slug)
                ->update([
                    'location_tasks.answer_hash' => Hash::make(AnswerNormalizer::normalize($answer)),
                    'location_tasks.updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $hash = Hash::make(AnswerNormalizer::normalize('1'));

        DB::table('location_tasks')
            ->whereNotNull('answer_hash')
            ->update([
                'answer_hash' => $hash,
                'updated_at' => now(),
            ]);
    }
};
