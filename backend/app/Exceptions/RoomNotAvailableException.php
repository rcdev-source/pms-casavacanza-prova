<?php

namespace App\Exceptions;

use RuntimeException;

class RoomNotAvailableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('La camera non è disponibile per le date richieste.');
    }
}
