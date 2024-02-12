<?php

declare(strict_types=1);

enum TransactionTypeEnum: string
{
    case STANDARD = 'standard';

    case CREDIT_EXPIRATION = 'credit-expiration';

    case VALID_FROM = 'valid-from';
}