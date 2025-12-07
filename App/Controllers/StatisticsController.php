<?php

namespace App\Controllers;

use Framework\Core\BaseController;
use App\Models\Tournament;
use App\Models\TournamentPlayer;
use App\Models\Decklist;
use App\Models\User;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Responses\ViewResponse;

class StatisticsController extends BaseController
{
    public function index(Request $request): Response
    {
        // Aggregate top-8 commanders from tournaments
        $commanders = [];

        $tournaments = Tournament::getAll();
        foreach ($tournaments as $tournament) {
            // only finished tournaments
            if (($tournament->status ?? '') !== 'finished') continue;
            $rankings = TournamentPlayer::getRankingsForTournament((int)$tournament->tournament_id);
            // take top 8 rows
            $top = array_slice($rankings, 0, 8);
            foreach ($top as $row) {
                $userId = $row['user_id'] ?? null;
                if (!$userId) continue;
                // Load latest decklist for this user in this tournament
                $decks = Decklist::getAll('tournament_id = ? AND user_id = ?', [(int)$tournament->tournament_id, (int)$userId], 'uploaded_at DESC', 1);
                if (empty($decks)) continue;
                $dl = $decks[0];
                $fileRel = $dl->file_path ?? '';
                if (!$fileRel) continue;
                $fullPath = realpath(__DIR__ . '/../../public/' . $fileRel) ?: (__DIR__ . '/../../public/' . $fileRel);
                if (!is_readable($fullPath)) continue;
                $lines = @file($fullPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($lines === false) continue;
                // search from end for a line starting with SB:
                $found = '';
                for ($i = count($lines) - 1; $i >= 0; $i--) {
                    if (preg_match('/^\s*SB:\s*(.*)/i', $lines[$i], $m)) {
                        $found = trim($m[1]);
                        // remove leading quantity like "1 ", "2x ", "2 × " or leading numbers
                        $found = preg_replace('/^\s*\d+\s*(?:x|×)?\s*/i', '', $found);
                        break;
                    }
                    // only consider last two non-empty lines per earlier rule
                    if (count($lines) - $i > 2) break;
                }
                if (!$found) continue;
                $key = mb_strtolower($found);
                if (!isset($commanders[$key])) {
                    $commanders[$key] = [
                        'name' => $found,
                        'count' => 0,
                        'entries' => [],
                    ];
                }
                $commanders[$key]['count']++;
                // find username
                $username = '';
                try {
                    $urow = User::getOne($userId);
                    if ($urow) $username = $urow->username;
                } catch (\Exception $e) {}
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

        // sort by count desc
        usort($commanders, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return $this->html(['commanders' => $commanders]);
    }

    public function detail(Request $request): Response
    {
        $name = trim((string)$request->get('name'));
        if ($name === '') {
            return $this->redirect('?c=Statistics&a=index');
        }
        // Rebuild entries same as index but filter by name (case-insensitive)
        $entries = [];

        $tournaments = Tournament::getAll();
        foreach ($tournaments as $tournament) {
            // only finished tournaments
            if (($tournament->status ?? '') !== 'finished') continue;
            $rankings = TournamentPlayer::getRankingsForTournament((int)$tournament->tournament_id);
            $top = array_slice($rankings, 0, 8);
            foreach ($top as $row) {
                $userId = $row['user_id'] ?? null;
                if (!$userId) continue;
                $decks = Decklist::getAll('tournament_id = ? AND user_id = ?', [(int)$tournament->tournament_id, (int)$userId], 'uploaded_at DESC', 1);
                if (empty($decks)) continue;
                $dl = $decks[0];
                $fileRel = $dl->file_path ?? '';
                if (!$fileRel) continue;
                $fullPath = realpath(__DIR__ . '/../../public/' . $fileRel) ?: (__DIR__ . '/../../public/' . $fileRel);
                if (!is_readable($fullPath)) continue;
                $lines = @file($fullPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($lines === false) continue;
                $found = '';
                for ($i = count($lines) - 1; $i >= 0; $i--) {
                    if (preg_match('/^\s*SB:\s*(.*)/i', $lines[$i], $m)) {
                        $found = trim($m[1]);
                        $found = preg_replace('/^\s*\d+\s*(?:x|×)?\s*/i', '', $found);
                        break;
                    }
                    if (count($lines) - $i > 2) break;
                }
                if (!$found) continue;
                if (mb_strtolower($found) !== mb_strtolower($name)) continue;
                $username = '';
                try {
                    $urow = User::getOne($userId);
                    if ($urow) $username = $urow->username;
                } catch (\Exception $e) {}
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
