<?php

namespace App\Enums\ViewPaths\Vendor;

enum Unit
{
    const INDEX = [
        URI => '/',
        VIEW => 'vendor-views.unit.index'
    ];

    const ADD = [
        URI => 'store',
        VIEW => 'vendor-views.unit.index'
    ];

    const UPDATE = [
        URI => 'edit',
        VIEW => 'vendor-views.unit.edit'
    ];

    const SEARCH = [
        URI => 'search',
        VIEW => 'vendor-views.unit.partials._table'
    ];

    const DELETE = [
        URI => 'delete',
        VIEW => ''
    ];

    const EXPORT = [
        URI => 'export',
        VIEW => ''
    ];
}

