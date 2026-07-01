<?php

namespace App\Support;

class AnswerNormalizer
{
    public static function normalize(?string $answer): string
    {
        $answer = trim((string) $answer);
        $answer = mb_strtolower($answer, 'UTF-8');
        $answer = str_replace(["\u{2013}", "\u{2014}", "\u{2212}"], '-', $answer);
        $answer = strtr($answer, [
            'á' => 'a', 'ä' => 'a', 'č' => 'c', 'ď' => 'd', 'é' => 'e', 'ě' => 'e',
            'í' => 'i', 'ľ' => 'l', 'ĺ' => 'l', 'ň' => 'n', 'ó' => 'o', 'ö' => 'o',
            'ř' => 'r', 'ŕ' => 'r', 'š' => 's', 'ť' => 't', 'ú' => 'u', 'ů' => 'u',
            'ü' => 'u', 'ý' => 'y', 'ž' => 'z',
        ]);

        $answer = preg_replace('/\s+/u', ' ', $answer) ?? $answer;

        return trim($answer);
    }
}
