<?php

namespace App\Enums;

enum SignatoryStatus: string
{
    case Pending = 'pending';
    case Signed = 'signed';
    case Declined = 'declined';
}
