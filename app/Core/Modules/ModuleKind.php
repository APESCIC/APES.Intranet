<?php

namespace App\Core\Modules;

enum ModuleKind: string
{
    case Module = 'module';
    case Integration = 'integration';
}
