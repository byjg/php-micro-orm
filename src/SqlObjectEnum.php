<?php

namespace ByJG\MicroOrm;

enum SqlObjectEnum
{
    case SELECT;
    case INSERT;
    case UPDATE;
    case DELETE;
}
