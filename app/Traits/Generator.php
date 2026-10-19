<?php

namespace App\Traits;

trait Generator
{
    protected function generateReferenceNumber($prefix = 'REF', $length = 8) {
        $randomString = strtoupper(bin2hex(random_bytes($length / 2)));
        return $prefix . '-' . $randomString;
    }
}
