<?php

namespace Database\Factories\Concerns;

/**
 * Faker has no Arabic text provider, so factories build Arabic strings from a small word list.
 * Seeders use hand-written copy; this is only for tests.
 */
trait ArabicText
{
    private const ARABIC_WORDS = [
        'عقار', 'منزل', 'فيلا', 'شقة', 'حديقة', 'مسبح', 'إطلالة', 'بحر', 'مدينة', 'هادئ',
        'فاخر', 'واسع', 'مشرق', 'حديث', 'موقع', 'ممتاز', 'غرفة', 'نوم', 'مطبخ', 'شرفة',
        'خدمة', 'فريق', 'عميل', 'ثقة', 'استثمار', 'سوق', 'سعر', 'عائلة', 'راحة', 'أمان',
    ];

    protected function arabicWords(int $count): string
    {
        return implode(' ', fake()->randomElements(self::ARABIC_WORDS, $count, true));
    }

    protected function arabicSentence(int $words = 6): string
    {
        return $this->arabicWords($words).'.';
    }

    protected function arabicParagraph(int $sentences = 3): string
    {
        return implode(' ', array_map(fn () => $this->arabicSentence(fake()->numberBetween(5, 10)), range(1, $sentences)));
    }
}
