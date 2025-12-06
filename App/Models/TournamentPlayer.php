<?php

namespace App\Models;

use Framework\Core\Model;

class TournamentPlayer extends Model
{
    public ?int $tournament_player_id = null;
    public int $tournament_id;
    public int $user_id;
    public ?int $points = 0;
    public ?int $rank_position = null;

    protected static string $table = 'tournament_player';
    protected static ?string $primaryKey = 'tournament_player_id';

    protected static function getTableName(): string
    {
        return self::$table;
    }

    public static function deleteByTournamentAndUser(int $tournamentId, int $userId): void
    {
        $sql = 'DELETE FROM tournament_player WHERE tournament_id = :t_id AND user_id = :u_id';
        self::executeRawSQL($sql, [
            ':t_id' => $tournamentId,
            ':u_id' => $userId,
        ]);
    }

    public static function getRankingsForTournament(int $tournamentId): array
    {
        $sql = 'SELECT tp.*, u.username
                FROM tournament_player tp
                JOIN app_user u ON tp.user_id = u.user_id
                WHERE tp.tournament_id = :tid
                ORDER BY tp.points DESC,
                         (tp.rank_position IS NULL) ASC,
                         tp.rank_position ASC,
                         u.username ASC';
        $rows = self::executeRawSQL($sql, [':tid' => $tournamentId]);
        return $rows;
    }
}
