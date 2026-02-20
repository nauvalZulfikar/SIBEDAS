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

        try {
            // Panggil service untuk generate text
            $classifyResponse = $this->openAIService->classifyMainGenerateText($request->input('prompt'));
            info($classifyResponse);

            // Pastikan hasil klasifikasi valid sebelum melanjutkan
            $validCategories = [
                'reklame', 'business_or_industries', 'customers', 
                'pbg', 'retribusi', 'spatial_plannings', 
                'tourisms', 'umkms'
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
            
            $nlpResult = $this->openAIService->generateNLPFromQuery($request->input('prompt'), $formattedResultQuery);
            $finalGeneratedText =$this->openAIService->generateFinalText($nlpResult);
            
            return response()->json(['response' => $finalGeneratedText, 'nlpResponse' => $queryResponse]);
        } catch (\Exception $e) {
            // Tangani error dan log exception
            \Log::error("Error generating text: " . $e->getMessage());

            return response()->json([
                'error' => ''
            ], 500);
        }
    }

}