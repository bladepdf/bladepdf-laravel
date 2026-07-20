<?php

declare(strict_types=1);

return [
    'invoice' => [
        'number' => 'INV-2026-0042',
        'issued_at' => 'July 20, 2026',
        'due_at' => 'August 3, 2026',
        'currency' => 'USD',
        'company' => [
            'name' => 'Northstar Studio',
            'email' => 'hello@northstar.example',
            'address' => ['71 Market Street', 'San Francisco, CA 94105'],
        ],
        'customer' => [
            'name' => 'Acme Commerce, Inc.',
            'contact' => 'Finance team',
            'email' => 'billing@acme.example',
            'address' => ['250 Mission Street', 'San Francisco, CA 94105'],
        ],
        'items' => [
            ['description' => 'Product design sprint', 'detail' => 'Discovery, flows, and interface system', 'quantity' => 1, 'rate' => '$4,800.00', 'total' => '$4,800.00'],
            ['description' => 'Laravel implementation', 'detail' => 'Customer portal and PDF workflows', 'quantity' => 1, 'rate' => '$3,200.00', 'total' => '$3,200.00'],
            ['description' => 'Launch support', 'detail' => 'QA, deployment, and handoff', 'quantity' => 8, 'rate' => '$175.00', 'total' => '$1,400.00'],
        ],
        'subtotal' => '$9,400.00',
        'tax' => '$752.00',
        'total' => '$10,152.00',
        'payment_terms' => 'Payment is due within 14 days. Please include INV-2026-0042 with your transfer.',
    ],
];
