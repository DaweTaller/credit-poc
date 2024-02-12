<?php

declare(strict_types=1);

enum TransactionTypeEnum: string
{
    case REGULAR = 'regular';

    case EXPIRATION = 'expiration';

    case VALID_FROM = 'valid-from';
}