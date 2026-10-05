<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Villa;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ResolvesCurrentVilla
{
    protected function currentVilla(Request $request): Villa
    {
        $villa = $request->user()->currentVilla();

        if (! $villa) {
            throw new HttpException(403, 'No villa is linked to this account yet.');
        }

        return $villa;
    }
}
