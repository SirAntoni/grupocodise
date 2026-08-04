<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait GeneratesSequentialCode
{
    /**
     * Marcador provisional para insertar la fila y recién entonces derivar el
     * código definitivo del id autoincremental (así dos guardados simultáneos
     * nunca chocan contra el unique de `code`).
     *
     * OJO: la columna `code` es varchar(20); esto debe caber. Un uuid (36) no
     * cabe y MySQL rechaza el insert con "Data too long for column 'code'".
     */
    public static function temporaryCode(): string
    {
        return 'TMP-'.Str::upper(Str::random(15));
    }
}
