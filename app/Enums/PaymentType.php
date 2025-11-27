<?php

namespace App\Enums;

enum PaymentType: string
{
    case API = 'api';
    case REDIRECT = 'redirect';
    case ALTERNATIVE = 'alternative';
}

