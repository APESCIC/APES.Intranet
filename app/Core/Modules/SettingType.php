<?php

namespace App\Core\Modules;

enum SettingType: string
{
    case String = 'string';
    case Int = 'int';
    case Bool = 'bool';
    case Enum = 'enum';
    case Url = 'url';
    case SecretRef = 'secret_ref';
}
