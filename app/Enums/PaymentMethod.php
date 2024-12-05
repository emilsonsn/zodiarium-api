<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Multibanco = 'Multibanco';
    case Mbway = 'Mbway';
    case Stripe = 'Stripe';
}
