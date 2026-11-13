<?php

namespace App\Exceptions;

use RuntimeException;

class GiftAlreadyClaimed extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This gift has already been claimed.');
    }
}
