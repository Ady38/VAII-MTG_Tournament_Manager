<?php

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

    public function getTournamentId(): ?int
    {
        return $this->tournament_id;
    }

    public function setTournamentId(int $tournament_id): void
    {
        $this->tournament_id = $tournament_id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): void
    {
        $this->location = $location;
    }

    public function getStartDate(): ?string
    {
        return $this->start_date;
    }

    public function setStartDate(?string $start_date): void
    {
        $this->start_date = $start_date;
    }

    public function getEndDate(): ?string
    {
        return $this->end_date;
    }

    public function setEndDate(?string $end_date): void
    {
        $this->end_date = $end_date;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getOrganizerId(): int
    {
        return $this->organizer_id;
    }

    public function setOrganizerId(int $organizer_id): void
    {
        $this->organizer_id = $organizer_id;
    }

    protected static function getTableName(): string
    {
        return self::$table;
    }
}
