<?php

use App\Models\Article;
use App\Models\BusinessIdea;
use App\Models\Opportunity;
use App\Models\Skill;

$text = 'nullable|string|max:100000';
$money = 'nullable|numeric|min:0|max:999999999999';

return [
    'business-ideas' => [
        'model' => BusinessIdea::class,
        'table' => 'business_ideas',
        'public' => 'business-ideas',
        'label' => 'Business Ideas',
        'singular' => 'Business Idea',
        'fields' => [
            ['description', 'Full description', 'textarea', $text],
            ['currency', 'Currency code (3 letters, e.g. RWF)', 'text', 'nullable|alpha|size:3'],
            ['startup_capital', 'Estimated startup capital', 'number', $money],
            ['operating_costs', 'Expected operating costs', 'number', $money],
            ['expected_revenue', 'Expected revenue', 'number', $money],
            ['expected_profit', 'Expected profit (enter only if you have a real estimate)', 'number', $money],
            ['materials', 'Required materials', 'textarea', $text],
            ['equipment', 'Equipment', 'textarea', $text],
            ['requirements', 'Starting requirements', 'textarea', $text],
            ['target_customers', 'Target customers', 'textarea', $text],
            ['location_notes', 'Location considerations', 'textarea', $text],
            ['steps', 'Step-by-step starting instructions', 'textarea', $text],
            ['operating_procedures', 'Operating procedures', 'textarea', $text],
            ['profitability_notes', 'Profitability information', 'textarea', $text],
            ['risks', 'Potential risks', 'textarea', $text],
            ['challenges', 'Challenges', 'textarea', $text],
            ['tips', 'Practical advice and success tips', 'textarea', $text],
        ],
    ],
    'skills' => [
        'model' => Skill::class,
        'table' => 'skills',
        'public' => 'skills',
        'label' => 'Skills',
        'singular' => 'Skill',
        'fields' => [
            ['description', 'Description', 'textarea', $text],
            ['materials', 'Required materials', 'textarea', $text],
            ['equipment', 'Required equipment', 'textarea', $text],
            ['instructions', 'Learning instructions', 'textarea', $text],
            ['applications', 'Practical applications', 'textarea', $text],
        ],
    ],
    'opportunities' => [
        'model' => Opportunity::class,
        'table' => 'opportunities',
        'public' => 'opportunities',
        'label' => 'Opportunities',
        'singular' => 'Opportunity',
        'fields' => [
            ['description', 'Description', 'textarea', $text],
            ['eligibility', 'Eligibility requirements', 'textarea', $text],
            ['application_instructions', 'Application instructions', 'textarea', $text],
            ['opens_at', 'Opening date', 'date', 'nullable|date'],
            ['closes_at', 'Closing date', 'date', 'nullable|date|after_or_equal:opens_at'],
            ['status_override', 'Status (leave automatic to use the dates)', 'select', 'nullable|in:open,closing_soon,closed,upcoming', [
                'open' => 'Open',
                'closing_soon' => 'Closing Soon',
                'closed' => 'Closed',
                'upcoming' => 'Upcoming',
            ]],
            ['application_url', 'External application URL', 'url', 'nullable|url:http,https|max:500'],
        ],
    ],
    'articles' => [
        'model' => Article::class,
        'table' => 'articles',
        'public' => 'articles',
        'label' => 'Articles',
        'singular' => 'Article',
        'fields' => [
            ['content', 'Article content', 'textarea', $text],
        ],
    ],
];
