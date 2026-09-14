<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'CASH';
    case CARD = 'CARD';
    case BANK_TRANSFER = 'BANK_TRANSFER';
    case ONLINE = 'ONLINE';
    case OTHER = 'OTHER';
}
