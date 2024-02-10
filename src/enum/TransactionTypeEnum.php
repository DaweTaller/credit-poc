<?php

declare(strict_types=1);

enum TransactionTypeEnum: string
{
    case ACCOUNT_MOVEMENT = 'account-movement';

    case CREDIT_EXPIRATION = 'credit-expiration';
}