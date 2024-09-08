<?php

namespace App\Storage;

use App\Models\Model;

class PostgreSQLStorage implements Storage
{

    public function save(Model $input, string $sourceName, array $params = []): bool
    {
        // TODO: Implement save() method.
    }
}