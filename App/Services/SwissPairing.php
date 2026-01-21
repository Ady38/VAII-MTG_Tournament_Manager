<?php

//Vytvorene s pomocou GitHub Copilot

namespace App\Services;

use App\Models\TournamentPlayer;

class SwissPairing
{
    /**
     * Generate Swiss-style pairings for the next round.
     * Input $players is an array of rows as returned by TournamentPlayer::getRankingsForTournament
     * Each row must contain at least ['user_id' => int, 'points' => int]
     *
     * Returns array with keys:
     * - 'pairs' => array of [player1_id, player2_id]
     * - 'bye' => user_id|null
     *
     * Strategy used (simple but practical):
     * - Keep players in the order provided (already sorted by points/username)
     * - If odd number: pick a bye player (lowest-ranked who hasn't had a bye yet)
     * - Pair adjacent players (1-2, 3-4, ...)
     * - If a pair already played (exists in match_ table), try to swap with next players to avoid rematch
     * - If no swap avoids rematch, accept rematch
     */

    // Generate pairings for the next round (main entry)
    public static function generate(int $tournamentId, array $players): array
    {
        if (count($players) < 1) {
            throw new \InvalidArgumentException('Not enough players');
        }

        // Normalize ranking rows to ordered list of user IDs
        $uids = array_map(fn($r) => (int)$r['user_id'], $players);

        // Load all previous matches for this tournament to detect rematches and byes
        $sql = 'SELECT player1_id, player2_id, result FROM match_ WHERE tournament_id = ?';
        $rows = TournamentPlayer::executeRawSQL($sql, [$tournamentId]);
        $played = [];
        $hadBye = [];
        foreach ($rows as $r) {
            $p1 = (int)$r['player1_id'];
            $p2 = (int)$r['player2_id'];
            // mark played pairs both ways
            $played[$p1][$p2] = true;
            $played[$p2][$p1] = true;
            // detect BYE entries
            $res = isset($r['result']) ? strtoupper((string)$r['result']) : '';
            if ($p1 === $p2 || str_contains($res, 'BYE')) {
                $hadBye[$p1] = true;
            }
        }

        $bye = null;
        // Choose bye player if odd number of players
        if (count($uids) % 2 !== 0) {
            // prefer lowest-ranked player without a previous bye
            for ($k = count($uids) - 1; $k >= 0; $k--) {
                $candidate = $uids[$k];
                if (!isset($hadBye[$candidate])) {
                    $bye = $candidate;
                    array_splice($uids, $k, 1);
                    break;
                }
            }
            // fallback: everyone had a bye before, pick lowest-ranked
            if ($bye === null) {
                $bye = array_pop($uids);
            }
        }

        $pairs = [];
        $n = count($uids);
        $i = 0;
        // Pair adjacent players, attempting swaps to avoid rematches
        while ($i < $n) {
            $a = $uids[$i];
            $b = $uids[$i+1];

            // If pair already played, try swapping with later players
            if (isset($played[$a][$b])) {
                $swapped = false;
                for ($j = $i + 2; $j < $n; $j++) {
                    $candidate = $uids[$j];
                    // swap if candidate hasn't played with $a
                    if (!isset($played[$a][$candidate])) {
                        $tmp = $uids[$j];
                        $uids[$j] = $uids[$i+1];
                        $uids[$i+1] = $tmp;
                        $b = $uids[$i+1];
                        $swapped = true;
                        break;
                    }
                }
                // if no swap found, accept rematch
            }

            // Record the pair
            $pairs[] = [$a, $b];
            $i += 2;
        }

        // Return generated pairs and optional bye
        return ['pairs' => $pairs, 'bye' => $bye];
    }
}
