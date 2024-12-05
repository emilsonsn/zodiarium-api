<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Pending = 'Pending';
    case Rejected = 'Rejected';
    case Finished = 'Finished';
}