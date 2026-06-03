<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\OpenAIService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function generateText(Request $request)
    {
        $request->validate([
            'tab_active' => 'required|string',
            'prompt' => 'required|string',
        ]);

        $tab_active = $request->input('tab_active');
        $main_content = match ($tab_active) {
            "count-retribusi" => "RETRIBUTION",
            "document-validation" => "DOCUMENT VALIDATION",
            "data-information" => "DATA SUMMARY",
            default => "UNKNOWN",
        };

        $chatHistory = $request->input('chatHistory');
        Log::info('Chat history sebelum disimpan:', ['history' => $chatHistory]);

        if ($main_content === "UNKNOWN") {
            return response()->json(['response' => 'Invalid tab_active value.'], 400);
        }

        // info($main_content);

        $queryResponse = $this->openAIService->generateQueryBasedMainContent($request->input('prompt'), $main_content, $chatHistory);

        if (str_contains($queryResponse, 'tidak relevan') || str_contains($queryResponse, 'tidak valid') || str_starts_with($queryResponse, 'Prompt')) {
            return response()->json(['response' => $queryResponse], 400);
        }

        $formattedResultQuery = "[]";
        $queryResponse = str_replace(['```sql', '```'], '', $queryResponse);
        $resultQuery = DB::select($queryResponse);
        $formattedResultQuery = json_encode($resultQuery, JSON_PRETTY_PRINT);
        info($formattedResultQuery);
        
        $nlpResult = $this->openAIService->generateNLPFromQuery($request->input('prompt'), $formattedResultQuery);
        $finalGeneratedText =$this->openAIService->generateFinalText($nlpResult);
        return response()->json(['response' => $finalGeneratedText, 'nlpResponse' => $queryResponse]);
    }

    public function mainGenerateText(Request $request)
    {
        // Log hanya data yang relevan
        info("Received prompt: " . $request->input('prompt'));

        // Validasi input
        $request->validate([
            'prompt' => 'required|string',
        ]);

        $promptText = trim($request->input('prompt'));

        // LOCAL-FIRST: handle "cari/siapa/data <nama>" tanpa GPT (fuzzy match score).
        // Works without OpenAI key. Pattern: imbuhan + name with >=2 chars.
        if (preg_match('/^\s*(cari(?:\s+data)?|siapa|find|search)\s+(.{2,})$/iu', $promptText, $m)) {
            $needle = trim($m[2]);
            if ($needle !== '') {
                return $this->localPersonSearch($needle);
            }
        }

        try {
            // Panggil service untuk generate text
            $classifyResponse = $this->openAIService->classifyMainGenerateText($request->input('prompt'));
            info($classifyResponse);

            // Pastikan hasil klasifikasi valid sebelum melanjutkan
            $validCategories = [
                'reklame', 'business_or_industries', 'customers',
                'pbg', 'retribusi', 'spatial_plannings',
                'tourisms', 'umkms', 'pbg_tracking', 'dokumen_resume', 'dokumen_per_noreg'
            ];

            if (!in_array($classifyResponse, $validCategories)) {
                return response()->json([
                    'error' => ''
                ], 400);
            }

            $chatHistory = $request->input('chatHistory');
            Log::info('Chat history sebelum disimpan:', ['history' => $chatHistory]);

            $queryResponse = $this->openAIService->createMainQuery($classifyResponse, $request->input('prompt'), $chatHistory);
            info($queryResponse);

            $formattedResultQuery = "[]";
            
            $queryResponse = str_replace(['```sql', '```'], '', $queryResponse);
            $queryResult = DB::select($queryResponse);
            
            $formattedResultQuery = json_encode($queryResult, JSON_PRETTY_PRINT);
            
            $nlpResult = $this->openAIService->generateNLPFromQuery($request->input('prompt'), $formattedResultQuery, $classifyResponse);
            $finalGeneratedText = $this->openAIService->generateFinalText($nlpResult);
            
            return response()->json(['response' => $finalGeneratedText, 'nlpResponse' => $queryResponse]);
        } catch (\Exception $e) {
            // Tangani error dan log exception
            \Log::error("Error generating text: " . $e->getMessage());

            // Graceful fallback when GPT/OpenAI is unavailable so the chatbot
            // still tells the user what they CAN do (local fuzzy person search).
            $msg = str_contains($e->getMessage(), 'API key')
                ? 'Layanan AI belum dikonfigurasi. Coba ketik <b>cari [nama]</b> untuk pencarian PBG.'
                : 'Terjadi kesalahan. Coba ulangi atau gunakan <b>cari [nama]</b>.';

            return response()->json([
                'response' => $msg,
                'nlpResponse' => null,
            ]);
        }
    }

    /**
     * Local "cari [nama]" handler — runs entirely without GPT.
     * Uses the same fuzzy match-score algorithm as the PBG table search
     * (consonant-skeleton Levenshtein + wide-net SOUNDEX).
     */
    private function localPersonSearch(string $needle)
    {
        $hits = $this->fuzzyPbgQuery($needle);
        if (empty($hits)) {
            return response()->json([
                'response' => "Tidak ditemukan data PBG untuk pencarian: <b>" . htmlspecialchars($needle) . "</b>",
                'nlpResponse' => 'local-fuzzy: 0 hits',
            ]);
        }

        $count = count($hits);
        $shown = min(10, $count);
        $lines = [];
        for ($i = 0; $i < $shown; $i++) {
            $h = $hits[$i];
            $name = htmlspecialchars($h->owner_name ?: ($h->name ?: '-'));
            $status = htmlspecialchars($h->status_name ?: '-');
            $reg = htmlspecialchars($h->registration_number ?: '-');
            $lines[] = ($i + 1) . ". <b>{$name}</b> — Status: {$status} — Reg: {$reg} (skor {$h->_score})";
        }
        $more = $count > $shown ? " (menampilkan {$shown} dari {$count})" : "";
        $reply = "Ditemukan {$count} data terkait \"<b>" . htmlspecialchars($needle) . "</b>\"{$more}:<br><br>" . implode("<br>", $lines);

        return response()->json([
            'response' => $reply,
            'nlpResponse' => "local-fuzzy: {$count} hits",
        ]);
    }

    private function fuzzyPbgQuery(string $needle): array
    {
        $needle = mb_strtolower(trim($needle));
        if ($needle === '') return [];

        $nTok = array_values(array_filter(preg_split('/\s+/', $needle) ?: []));

        $conditions = [];
        $bindings = [];
        foreach (['owner_name', 'name'] as $field) {
            $conditions[] = "{$field} LIKE ?";
            $bindings[] = "%{$needle}%";
            $conditions[] = "SOUNDEX({$field}) = SOUNDEX(?)";
            $bindings[] = $needle;
            foreach ($nTok as $tok) {
                if (strlen($tok) < 3) continue;
                for ($i = 1; $i <= 4; $i++) {
                    $conditions[] = "SOUNDEX(SUBSTRING_INDEX(SUBSTRING_INDEX({$field}, ' ', ?), ' ', -1)) = SOUNDEX(?)";
                    $bindings[] = $i;
                    $bindings[] = $tok;
                }
            }
        }
        $sql = "SELECT id, name, owner_name, status_name, registration_number FROM pbg_task WHERE is_valid = 1 AND (" . implode(' OR ', $conditions) . ") LIMIT 3000";

        $rows = DB::select($sql, $bindings);

        $scored = [];
        foreach ($rows as $row) {
            $best = 0;
            foreach (['owner_name', 'name'] as $f) {
                $hay = mb_strtolower((string) ($row->{$f} ?? ''));
                $s = $this->fuzzyScore($needle, $hay);
                if ($s > $best) $best = $s;
            }
            if ($best >= 65) {
                $row->_score = $best;
                $scored[] = $row;
            }
        }
        usort($scored, fn ($a, $b) => $b->_score <=> $a->_score);
        return $scored;
    }

    /**
     * Per-token Levenshtein score with consonant-skeleton fallback.
     * Same algorithm as RequestAssignmentController::fuzzyScore.
     */
    private function fuzzyScore(string $needle, string $hay): int
    {
        if ($hay === '' || $needle === '') return 0;
        if (str_contains($hay, $needle)) return 100;

        $nTok = array_values(array_filter(preg_split('/\s+/', $needle) ?: []));
        $hTok = array_values(array_filter(preg_split('/\s+/', $hay) ?: []));
        if (empty($nTok) || empty($hTok)) return 0;

        $stripVowels = fn (string $s) => preg_replace('/[aiueo]/u', '', $s);
        $nSkel = array_map($stripVowels, $nTok);
        $hSkel = array_map($stripVowels, $hTok);

        $similar = function (string $a, string $b): int {
            $m = max(strlen($a), strlen($b));
            if ($m === 0) return 0;
            return (int) round((1 - levenshtein($a, $b) / $m) * 100);
        };
        $perTokenBest = function (array $nl, array $hl) use ($similar): array {
            $out = [];
            foreach ($nl as $n) {
                if ($n === '') continue;
                $best = 0;
                foreach ($hl as $h) {
                    if ($h === '') continue;
                    $r = $similar($n, $h);
                    if ($r > $best) $best = $r;
                }
                $out[] = $best;
            }
            return $out;
        };

        $skelBest = $perTokenBest($nSkel, $hSkel);
        if (!empty($skelBest) && min($skelBest) >= 50) {
            return (int) round(array_sum($skelBest) / count($skelBest));
        }
        $rawBest = $perTokenBest($nTok, $hTok);
        if (!empty($rawBest) && min($rawBest) >= 50) {
            return (int) round(array_sum($rawBest) / count($rawBest));
        }
        return 0;
    }
}