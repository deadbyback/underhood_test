<?php

namespace App\Models;

interface Model
{
    public function toArray(): array;

    public function toJson(): string;
}