<?php

namespace App\Core\Legal\Exceptions;

use RuntimeException;

final class PublishedLegalVersionException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Published legal page versions cannot be changed.');
    }
}
