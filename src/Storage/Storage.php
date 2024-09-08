<?php

namespace App\Storage;

use App\Models\Model;

interface Storage
{
    public function save(Model $input, string $sourceName, array $params = []): bool;
}