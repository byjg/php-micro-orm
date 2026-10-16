<?php

namespace Tests\Model;

enum OrderStatus: string
{
    case Pending = 'PENDING';
    case Paid = 'PAID';
}
