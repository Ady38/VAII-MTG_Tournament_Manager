<?php

//Vytvorene s pomocou GitHub Copilot

namespace App\Models;

use Framework\Core\Model;

class Tournament extends Model
{
    /**
     * @var int|null
     */
    public ?int $tournament_id = null;

    /**
     * @var string
     */
    public string $name = "";

    /**
     * @var string|null
     */
    public ?string $location = null;

    /**
     * @var string|null
     */
    public ?string $start_date = null;

    /**
     * @var string|null
     */
    public ?string $end_date = null;

    /**
     * @var string
     */
    public string $status = "planned";

    /**
     * @var int
     */
    public int $organizer_id;

    protected static string $table = 'tournament';

    protected static ?string $primaryKey = 'tournament_id';

    protected static function getTableName(): string
    {
        return self::$table;
    }
}
