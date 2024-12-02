<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'Pending';
    case Successful = 'Successful';
    case Error = 'Error';
}
