<?php

//Vytvorene s pomocou GitHub Copilot

namespace App\Controllers;

use Framework\Core\BaseController;
use App\Models\Tournament;
use App\Models\TournamentPlayer;
use App\Models\Decklist;
use App\Models\User;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Responses\ViewResponse;

/**
 * StatisticsController - basic stats about commanders
 */
class StatisticsController extends BaseController
{
    /**
     * Aggregate commanders from finished tournaments.
     */
    public function index(Request $request): Response
    {
        $commanders = [];

        // Load all tournaments
        $tournaments = Tournament::getAll();
        foreach ($tournaments as $tournament) {
            // consider only finished tournaments
            if (($tournament->status ?? '') !== 'finished') continue;

            // get tournament rankings and take top 8
            $rankings = TournamentPlayer::getRankingsForTournament((int)$tournament->tournament_id);
            $top = array_slice($rankings, 0, 8);
            foreach ($top as $row) {
                // get user id from ranking row
                $userId = $row['user_id'] ?? null;
                if (!$userId) continue;

                // load the latest decklist for this user in this tournament
                $decks = Decklist::getAll('tournament_id = ? AND user_id = ?', [(int)$tournament->tournament_id, (int)$userId], 'uploaded_at DESC', 1);
                if (empty($decks)) continue;
                $dl = $decks[0];
                $fileRel = $dl->file_path ?? '';
                if (!$fileRel) continue;

                // resolve full file path and check readability
                $fullPath = realpath(__DIR__ . '/../../public/' . $fileRel) ?: (__DIR__ . '/../../public/' . $fileRel);
                if (!is_readable($fullPath)) continue;

                // read non-empty lines from the deck file
                $lines = @file($fullPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($lines === false) continue;

                // look for an "SB:" line near the end of the file
                $found = '';
                for ($i = count($lines) - 1; $i >= 0; $i--) {
                    if (preg_match('/^\s*SB:\s*(.*)/i', $lines[$i], $m)) {
                        $found = trim($m[1]);
                        $found = preg_replace('/^\s*\d+\s*[x×]?\s*/i', '', $found);
                        break;
                    }
                    if (count($lines) - $i > 2) break;
                }
                if (!$found) continue;

                // aggregate occurrences by normalized name
                $key = mb_strtolower($found);
                if (!isset($commanders[$key])) {
                    $commanders[$key] = ['name' => $found, 'count' => 0, 'entries' => []];
                }
                $commanders[$key]['count']++;

                // try to resolve username (best effort)
                $username = '';
                try {
                    $urow = User::getOne($userId);
                    if ($urow) $username = $urow->username;
                } catch (\Exception $e) {}

                // store metadata for this entry
                $commanders[$key]['entries'][] = [
                    'tournament_id' => $tournament->tournament_id,
                    'tournament_name' => $tournament->name,
                    'user_id' => $userId,
                    'username' => $username,
                    'file_path' => $fileRel,
                    'uploaded_at' => $dl->uploaded_at ?? null,
                ];
            }
        }

        // sort commanders by count descending
        usort($commanders, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return $this->html(['commanders' => $commanders]);
    }

    /**
     * Show detail entries for a commander name.
     */
    public function detail(Request $request): Response
    {
        // get requested commander name
        $name = trim((string)$request->get('name'));
        if ($name === '') {
            return $this->redirect('?c=Statistics&a=index');
        }

        $entries = [];

        // iterate finished tournaments and top-8 players
        $tournaments = Tournament::getAll();
        foreach ($tournaments as $tournament) {
            if (($tournament->status ?? '') !== 'finished') continue;
            $rankings = TournamentPlayer::getRankingsForTournament((int)$tournament->tournament_id);
            $top = array_slice($rankings, 0, 8);
            foreach ($top as $row) {
                $userId = $row['user_id'] ?? null;
                if (!$userId) continue;

                // load latest decklist for the user
                $decks = Decklist::getAll('tournament_id = ? AND user_id = ?', [(int)$tournament->tournament_id, (int)$userId], 'uploaded_at DESC', 1);
                if (empty($decks)) continue;
                $dl = $decks[0];
                $fileRel = $dl->file_path ?? '';
                if (!$fileRel) continue;

                // read file and check readability
                $fullPath = realpath(__DIR__ . '/../../public/' . $fileRel) ?: (__DIR__ . '/../../public/' . $fileRel);
                if (!is_readable($fullPath)) continue;

                $lines = @file($fullPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($lines === false) continue;

                // find SB line and normalize name
                $found = '';
                for ($i = count($lines) - 1; $i >= 0; $i--) {
                    if (preg_match('/^\s*SB:\s*(.*)/i', $lines[$i], $m)) {
                        $found = trim($m[1]);
                        $found = preg_replace('/^\s*\d+\s*[x×]?\s*/i', '', $found);
                        break;
                    }
                    if (count($lines) - $i > 2) break;
                }
                if (!$found) continue;

                // include only entries that match requested name
                if (mb_strtolower($found) !== mb_strtolower($name)) continue;

                // resolve username (best effort)
                $username = '';
                try {
                    $urow = User::getOne($userId);
                    if ($urow) $username = $urow->username;
                } catch (\Exception $e) {}

                // append matching entry
                $entries[] = [
                    'tournament_id' => $tournament->tournament_id,
                    'tournament_name' => $tournament->name,
                    'user_id' => $userId,
                    'username' => $username,
                    'file_path' => $fileRel,
                    'uploaded_at' => $dl->uploaded_at ?? null,
                ];
            }
        }

        return $this->html(['commander' => $name, 'entries' => $entries], 'detail');
    }
}
