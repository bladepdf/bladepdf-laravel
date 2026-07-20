<?php

declare(strict_types=1);

return [
    'report' => [
        'title' => 'Revenue & Retention',
        'period' => 'Q2 2026',
        'generated_at' => 'July 20, 2026 at 09:42 UTC',
        'workspace' => 'Northstar Commerce',
        'metrics' => [
            ['label' => 'Net revenue', 'value' => '$428,600', 'change' => '+18.4%', 'positive' => true],
            ['label' => 'New customers', 'value' => '1,284', 'change' => '+12.1%', 'positive' => true],
            ['label' => 'Gross margin', 'value' => '72.8%', 'change' => '+2.6 pts', 'positive' => true],
            ['label' => 'Churn', 'value' => '2.9%', 'change' => '-0.8 pts', 'positive' => true],
        ],
        'monthly' => [
            ['month' => 'Jan', 'value' => 42], ['month' => 'Feb', 'value' => 47],
            ['month' => 'Mar', 'value' => 53], ['month' => 'Apr', 'value' => 61],
            ['month' => 'May', 'value' => 73], ['month' => 'Jun', 'value' => 84],
        ],
        'segments' => [
            ['name' => 'Self-serve', 'customers' => '3,842', 'revenue' => '$182,900', 'retention' => '94.2%'],
            ['name' => 'Growth', 'customers' => '814', 'revenue' => '$156,400', 'retention' => '96.8%'],
            ['name' => 'Enterprise', 'customers' => '92', 'revenue' => '$89,300', 'retention' => '98.1%'],
        ],
        'insights' => [
            'Annual upgrades accounted for 31% of expansion revenue.',
            'Activation within the first 48 hours improved by 9.7%.',
            'Enterprise retention reached a twelve-month high.',
        ],
    ],
];
