<?php

namespace App\Helpers;

class CommentOptions
{
    public const TEACHER_COMMENTS = [
        'excellent' => [
            'Excellent performance! Shows exceptional understanding and application of concepts.',
            'Outstanding academic achievement. Keep up the excellent work.',
            'Demonstrates remarkable progress and dedication to learning.',
            'Exceptional performance across all subjects. A role model student.'
        ],
        'very_good' => [
            'Very good performance. Shows consistent effort and understanding.',
            'Strong academic performance with good participation in class.',
            'Shows great potential and dedication to studies.',
            'Maintains a very good standard of work consistently.'
        ],
        'good' => [
            'Good overall performance. Shows steady progress.',
            'Demonstrates good understanding of subjects.',
            'Shows good effort and participation in class activities.',
            'Maintains a good standard of work.'
        ],
        'average' => [
            'Shows average performance. More effort needed in some areas.',
            'Fair performance with room for improvement.',
            'Demonstrates basic understanding but needs more consistency.',
            'Making steady progress but needs to maintain focus.'
        ],
        'needs_improvement' => [
            'Needs to put in more effort to improve performance.',
            'Shows potential but requires more dedication to studies.',
            'More attention and practice needed in key areas.',
            'Needs to focus more on assignments and class participation.'
        ]
    ];

    public const PRINCIPAL_COMMENTS = [
        'excellent' => [
            'Exemplary performance. Continue to maintain these high standards.',
            'Outstanding results. A credit to the school and family.',
            'Exceptional achievement across all areas. Keep it up.',
            'Demonstrates excellence in both academics and character.'
        ],
        'very_good' => [
            'Very good results. Shows great promise.',
            'Commendable performance. Keep up the good work.',
            'Very good academic standing. Continue to strive for excellence.',
            'Shows very good potential for further growth.'
        ],
        'good' => [
            'Good overall performance. Encourage to aim higher.',
            'Shows good progress. Continue to work hard.',
            'Maintains good academic standards.',
            'Good results with potential for improvement.'
        ],
        'average' => [
            'Fair performance. Encouraged to work harder.',
            'Shows average progress. More effort required.',
            'Need for more consistent effort in studies.',
            'Can improve with more dedication and focus.'
        ],
        'needs_improvement' => [
            'Needs significant improvement. Parents\' attention required.',
            'Must show more commitment to academic work.',
            'Requires more focus and dedication to studies.',
            'Need for immediate improvement in academic performance.'
        ]
    ];

    public static function getTeacherCommentsByCategory(string $category): array
    {
        return self::TEACHER_COMMENTS[$category] ?? [];
    }

    public static function getPrincipalCommentsByCategory(string $category): array
    {
        return self::PRINCIPAL_COMMENTS[$category] ?? [];
    }

    public static function getAllTeacherComments(): array
    {
        return collect(self::TEACHER_COMMENTS)
            ->flatMap(fn($comments) => $comments)
            ->toArray();
    }

    public static function getAllPrincipalComments(): array
    {
        return collect(self::PRINCIPAL_COMMENTS)
            ->flatMap(fn($comments) => $comments)
            ->toArray();
    }
}
