<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'Pending';
    case System = 'System';
    case Successful = 'Successful';
    case Error = 'Error';
}
