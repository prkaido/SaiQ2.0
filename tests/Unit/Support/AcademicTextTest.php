<?php

namespace Tests\Unit\Support;

use App\Support\AcademicText;
use PHPUnit\Framework\TestCase;

class AcademicTextTest extends TestCase
{
    /** @test */
    public function repairs_mojibake_in_origin_program_names(): void
    {
        $this->assertSame(
            'Tecnólogo en gestión logística - Indoamérica',
            AcademicText::name('TecnÃ³logo en gestiÃ³n logÃ­stica - IndoamÃ©rica')
        );
    }

    /** @test */
    public function uppercases_cleaned_names_with_accents(): void
    {
        $this->assertSame(
            'TECNOLOGÍA EN GESTIÓN LOGÍSTICA',
            AcademicText::upper('TecnologÃ­a en gestiÃ³n logÃ­stica')
        );
    }
}
