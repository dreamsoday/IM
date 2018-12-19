<?php
namespace Lgy\IM;

use \Illuminate\Support\Facades\Facade;

class IMFacade extends Facade {

    protected static function getFacadeAccessor() {
        return 'im';
    }
}

