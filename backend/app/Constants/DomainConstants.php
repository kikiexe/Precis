<?php

declare(strict_types=1);

namespace App\Constants;

final class DomainConstants
{
    public const DEFAULT_MONTHLY_PLAN_PRICE = 150000.00;
    public const MONTHS_PER_YEAR = 12;
    public const DEFAULT_SUBSCRIPTION_EXTENSION_DAYS = 30;
    public const MAX_SUBSCRIPTION_EXTENSION_DAYS = 3650;

    public const DEFAULT_LATE_PENALTY_PER_MINUTE = 1000.00;
    public const DEFAULT_OVERTIME_PAY_PER_HOUR = 20000.00;
    public const DEFAULT_BASE_SALARY = 3000000.00;
}
