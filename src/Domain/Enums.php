<?php

namespace App\Domain;

final class Enums
{
    public const TICKET_STATUS_NEW = 'NEW';
    public const TICKET_STATUS_WO_CREATED = 'WO_CREATED';

    public const WORK_ORDER_STATUS_PENDING = 'PENDING';
    public const WORK_ORDER_STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const WORK_ORDER_STATUS_PAUSED = 'PAUSED';
    public const WORK_ORDER_STATUS_PENDING_QA = 'PENDING_QA';
    public const WORK_ORDER_STATUS_CLOSED = 'CLOSED';

    public const INVENTORY_TXN_ISSUE = 'issue';
    public const INVENTORY_TXN_RETURN = 'return';

    public const ROLE_DISPATCHER = 'dispatcher';
    public const ROLE_TECH = 'tech';
    public const ROLE_WH = 'wh';
    public const ROLE_VIEWER = 'viewer';

    public const ROLES = [
        self::ROLE_DISPATCHER,
        self::ROLE_TECH,
        self::ROLE_WH,
        self::ROLE_VIEWER,
    ];
}
