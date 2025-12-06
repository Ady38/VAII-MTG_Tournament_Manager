<?php

namespace App\Models;

use Framework\Core\Model;

class Decklist extends Model
{
    public ?int $decklist_id = null;
    public int $user_id;
    public int $tournament_id;
    public ?string $file_path = null;
    public ?string $uploaded_at = null;
    public ?string $approved = 'N';

    protected static string $table = 'decklist';
    protected static ?string $primaryKey = 'decklist_id';

    protected static function getTableName(): string
    {
        return self::$table;
    }
}

