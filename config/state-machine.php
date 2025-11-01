<?php

use App\Domain\StateMachines\Guards\AssetStateGuard;
use App\Domain\StateMachines\Guards\RepairOrderStateGuard;
use App\Models\Asset;
use App\Models\RepairOrder;

return [
    'asset' => [
        'class' => Asset::class,
        'graph' => 'asset',
        'property_path' => 'status',
        'states' => [
            ['name' => Asset::STATUS_DRAFT],
            ['name' => Asset::STATUS_PURCHASED],
            ['name' => Asset::STATUS_IN_STOCK],
            ['name' => Asset::STATUS_IN_USE],
            ['name' => Asset::STATUS_UNDER_REPAIR],
            ['name' => Asset::STATUS_DISPOSED],
        ],
        'transitions' => [
            'purchase' => [
                'from' => [Asset::STATUS_DRAFT],
                'to' => Asset::STATUS_PURCHASED,
            ],
            'stock_in' => [
                'from' => [Asset::STATUS_PURCHASED],
                'to' => Asset::STATUS_IN_STOCK,
            ],
            'assign_to_user' => [
                'from' => [Asset::STATUS_IN_STOCK],
                'to' => Asset::STATUS_IN_USE,
            ],
            'send_to_repair' => [
                'from' => [Asset::STATUS_IN_USE],
                'to' => Asset::STATUS_UNDER_REPAIR,
            ],
            'return_from_repair' => [
                'from' => [Asset::STATUS_UNDER_REPAIR],
                'to' => Asset::STATUS_IN_USE,
            ],
            'dispose' => [
                'from' => [Asset::STATUS_UNDER_REPAIR],
                'to' => Asset::STATUS_DISPOSED,
            ],
        ],
        'callbacks' => [
            'guard' => [
                'require_assignment_context' => [
                    'on' => 'assign_to_user',
                    'do' => AssetStateGuard::class.'@canAssignToUser',
                    'args' => ['object', 'event'],
                ],
            ],
        ],
    ],
    'repair_order' => [
        'class' => RepairOrder::class,
        'graph' => 'repair_order',
        'property_path' => 'status',
        'states' => [
            ['name' => RepairOrder::STATUS_CREATED],
            ['name' => RepairOrder::STATUS_ASSIGNED],
            ['name' => RepairOrder::STATUS_DIAGNOSED],
            ['name' => RepairOrder::STATUS_WAITING_PARTS],
            ['name' => RepairOrder::STATUS_REPAIRING],
            ['name' => RepairOrder::STATUS_QA],
            ['name' => RepairOrder::STATUS_CLOSED],
            ['name' => RepairOrder::STATUS_SCRAPPED],
        ],
        'transitions' => [
            'assign_technician' => [
                'from' => [RepairOrder::STATUS_CREATED],
                'to' => RepairOrder::STATUS_ASSIGNED,
            ],
            'diagnose' => [
                'from' => [RepairOrder::STATUS_ASSIGNED],
                'to' => RepairOrder::STATUS_DIAGNOSED,
            ],
            'await_parts' => [
                'from' => [RepairOrder::STATUS_DIAGNOSED],
                'to' => RepairOrder::STATUS_WAITING_PARTS,
            ],
            'start_repair' => [
                'from' => [RepairOrder::STATUS_WAITING_PARTS, RepairOrder::STATUS_DIAGNOSED],
                'to' => RepairOrder::STATUS_REPAIRING,
            ],
            'quality_assure' => [
                'from' => [RepairOrder::STATUS_REPAIRING],
                'to' => RepairOrder::STATUS_QA,
            ],
            'close' => [
                'from' => [RepairOrder::STATUS_QA],
                'to' => RepairOrder::STATUS_CLOSED,
            ],
            'scrap' => [
                'from' => [
                    RepairOrder::STATUS_DIAGNOSED,
                    RepairOrder::STATUS_WAITING_PARTS,
                    RepairOrder::STATUS_REPAIRING,
                    RepairOrder::STATUS_QA,
                ],
                'to' => RepairOrder::STATUS_SCRAPPED,
            ],
        ],
        'callbacks' => [
            'guard' => [
                'require_actor_context' => [
                    'do' => RepairOrderStateGuard::class.'@ensureActorProvided',
                    'args' => ['object', 'event'],
                ],
            ],
        ],
    ],
];
