<?php

use App\Core\CoreServiceProvider;
use App\Providers\AppServiceProvider;
use Modules\People\PeopleServiceProvider;

return [
    AppServiceProvider::class,
    CoreServiceProvider::class,
    PeopleServiceProvider::class,
];
