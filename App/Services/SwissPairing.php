<?php

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
    public static function generate(int $tournamentId, array $players): array
    {
        if (count($players) < 1) {
            throw new \InvalidArgumentException('Not enough players');
        }

        // Normalize to list of user_ids in the provided ranking order
        $uids = array_map(fn($r) => (int)$r['user_id'], $players);

        // Load existing matchups for this tournament (both directions)
        $sql = 'SELECT player1_id, player2_id, result FROM match_ WHERE tournament_id = ?';
        $rows = TournamentPlayer::executeRawSQL($sql, [$tournamentId]);
        $played = [];
        $hadBye = [];
        foreach ($rows as $r) {
            $p1 = (int)$r['player1_id'];
            $p2 = (int)$r['player2_id'];
            // mark played pairs
            $played[$p1][$p2] = true;
            $played[$p2][$p1] = true;
            // detect BYE records either by matching player1==player2 or result contains 'BYE'
            $res = isset($r['result']) ? strtoupper((string)$r['result']) : '';
            if ($p1 === $p2 || str_contains($res, 'BYE')) {
                $hadBye[$p1] = true;
            }
        }

        $bye = null;
        // If odd, determine bye recipient: prefer lowest-ranked (last in $uids) who hasn't had bye
        if (count($uids) % 2 !== 0) {
            // iterate from end (lowest-ranked)
            for ($k = count($uids) - 1; $k >= 0; $k--) {
                $candidate = $uids[$k];
                if (!isset($hadBye[$candidate])) {
                    $bye = $candidate;
                    // remove candidate from $uids
                    array_splice($uids, $k, 1);
                    break;
                }
            }
            // if all had bye before, pick the lowest-ranked anyway
            if ($bye === null) {
                $bye = array_pop($uids);
            }
        }

        $pairs = [];
        $n = count($uids);
        $i = 0;
        while ($i < $n) {
            $a = $uids[$i];
            $b = $uids[$i+1];

            // If they already played, try to find a swap with next items
            if (isset($played[$a][$b])) {
                $swapped = false;
                // try swap b with later players j (i+2 .. n-1)
                for ($j = $i + 2; $j < $n; $j++) {
                    $candidate = $uids[$j];
                    // ensure a hasn't played candidate and b hasn't played the element that would take candidate's place
                    if (!isset($played[$a][$candidate])) {
                        // perform swap: place candidate to i+1, shift others
                        $tmp = $uids[$j];
                        $uids[$j] = $uids[$i+1];
                        $uids[$i+1] = $tmp;
                        $b = $uids[$i+1];
                        $swapped = true;
                        break;
                    }
                }
                // If still swapped==false, accept rematch
            }

            $pairs[] = [$a, $b];
            $i += 2;
        }

        return ['pairs' => $pairs, 'bye' => $bye];
    }
}
