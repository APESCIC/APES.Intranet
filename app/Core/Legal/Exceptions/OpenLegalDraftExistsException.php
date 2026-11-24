<?php

namespace App\Core\Legal\Exceptions;

use RuntimeException;

final class OpenLegalDraftExistsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This legal page already has an open draft.');
    }
}
