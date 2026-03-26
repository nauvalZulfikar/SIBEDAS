<?php

namespace App\Services;

use OpenAI;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected $client;

    public function __construct()
    {
        // $this->client = OpenAI::client(env('OPENAI_API_KEY'));
        $this->client = OpenAI::client(env('OPENAI_API_KEY'));
    }

    public function generateQueryBasedMainContent($prompt, $mainContent, $chatHistory)
    {
        // Load file JSON
        $jsonPath = public_path('templates/contentTemplatePrompt.json'); // Sesuaikan path
        $jsonData = json_decode(file_get_contents($jsonPath), true);

        // Periksa apakah kategori ada dalam JSON
        if (!isset($jsonData[$mainContent])) {
            return "Template prompt tidak ditemukan.";
        }

        $validationResponse = $this->validatePromptTopic($prompt);
        if ($validationResponse !== 'VALID') {
            return "Prompt yang Anda masukkan tidak relevan dengan data PUPR/SIMBG/DPUTR atau pekerjaan sejenis.";
        }

        // Ambil template berdasarkan kategori
        $promptTemplate = $jsonData[$mainContent]['prompt'];

        // Menyusun pesan untuk OpenAI
        $messages = [
            ['role' => 'system', 'content' => $promptTemplate],
        ];

        // Menambahkan chat history sebagai konteks
        foreach ($chatHistory as $chat) {
            if (isset($chat['user'])) {
                $messages[] = ['role' => 'user', 'content' => $chat['user']];
            }
            if (isset($chat['rawBotResponse'])) {
                $messages[] = ['role' => 'assistant', 'content' => $chat['rawBotResponse']];
            }
        }

        // Tambahkan prompt terbaru user
        $messages[] = ['role' => 'user', 'content' => $prompt];

        // Kirim request ke OpenAI API
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
        ]);

        return trim($response['choices'][0]['message']['content'] ?? 'No response');
    }

    
    // public function generateQueryBasedMainContent($prompt, $mainContent, $chatHistory)
    // {
    //     // Load file JSON
    //     $jsonPath = public_path('templates/contentTemplatePrompt.json'); // Sesuaikan path
    //     $jsonData = json_decode(file_get_contents($jsonPath), true);

    //     // Periksa apakah kategori ada dalam JSON
    //     if (!isset($jsonData[$mainContent])) {
    //         return "Template prompt tidak ditemukan.";
    //     }

    //     // Ambil template berdasarkan kategori
    //     $promptTemplate = $jsonData[$mainContent]['prompt'];

    //     $response = $this->client->chat()->create([
    //         'model' => 'gpt-4o-mini',
    //         'messages' => [
    //             ['role' => 'system', 'content' => $promptTemplate],
    //             ['role' => 'user', 'content' => $prompt],
    //         ],
    //     ]);

    //     return trim($response['choices'][0]['message']['content'] ?? 'No response');
    // }


    public function validateSyntaxQuery($queryResponse)
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system', 
                    'content' => "You are a MariaDB SQL expert. Your task is to validate the syntax of an SQL query to ensure it follows proper MariaDB syntax rules. 

                    Guidelines:
                    - Check for any syntax errors, missing keywords, or incorrect clause usage.
                    - Ensure the query is well-structured and adheres to best practices.
                    - Verify that all SQL keywords are used correctly and in the right order.
                    - If the query is valid, respond with: \"VALID\".
                    - If the query has issues, respond with: \"INVALID\".
                    
                    Always respond with either \"VALID\" or \"INVALID\"."
                ],
                ['role' => 'user', 'content' => $queryResponse],
            ],
        ]);

        return trim($response['choices'][0]['message']['content'] ?? 'No response');
    }

    public function generateNLPFromQuery($inputUser, $resultQuery) {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system', 
                    'content' => "You are an expert assistant. Your task is to analyze the database query results and transform them into a human-readable answer based on the user's question.
    
                    Guidelines:
                    - Understand the user's question and extract the key intent.
                    - Summarize or format the query results to directly answer the user's question.
                    - Ensure the response is clear, concise, and relevant.
                    - If the query result is empty or does not match the question, provide a polite response indicating that no data is available.
    
                    Always provide a well-structured response that makes sense based on the input question."
                ],
                ['role' => 'user', 'content' => "User's question: $inputUser \nDatabase result: $resultQuery"],
            ],
        ]);
    
        return trim($response['choices'][0]['message']['content'] ?? 'No response');
    }

    public function generateFinalText($nlpResult) {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You are an expert text formatter. Your task is to take the given NLP result and format it into a structured, human-readable text suitable for rendering inside an HTML <div>.
    
                    Guidelines:
                    - Preserve the meaning and clarity of the content.
                    - Use proper line breaks for readability.
                    - If the text contains lists, convert them into bullet points.
                    - Emphasize important keywords using <strong> tags if necessary.
                    - Ensure the response remains clean and concise without extra explanations."
                ],
                ['role' => 'user', 'content' => "Here is the NLP result that needs formatting:\n\n$nlpResult"],
            ],
        ]);
    
        return trim($response['choices'][0]['message']['content'] ?? 'No response');
    }

    public function classifyMainGenerateText($prompt) {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You are an assistant that classifies text into one of the following categories:
                    - reklame (ads or product/service promotions)
                    - business_or_industries (business or industries in general)
                    - customers (customers, consumers, or service users)
                    - pbg (tasks related to Building Approval)
                    - retribusi (retributions related to PBG)
                    - spatial_plannings (spatial planning)
                    - tourisms (tourism and tourist destinations)
                    - umkms (Micro, Small, and Medium Enterprises)
    
                    Respond with only one of the categories above without any additional explanation."
                ],
                [
                    'role' => 'user',
                    'content' => "Classify the following text:\n\n" . $prompt
                ],
            ],
        ]);
    
        return trim($response['choices'][0]['message']['content'] ?? 'No response');
    }

    public function createMainQuery($classify, $prompt, $chatHistory)
    {
        // Load file JSON
        $jsonPath = public_path('templates/table_config.json');
        $jsonConfig = json_decode(file_get_contents($jsonPath), true);

        // Pastikan kategori tersedia dalam konfigurasi
        if (!isset($jsonConfig[$classify])) {
            return "Error: Kategori tidak ditemukan dalam konfigurasi.";
        }

        // Ambil nama tabel dan kolom
        $tableName = $jsonConfig[$classify]['table_name'];
        $columns = implode(', ', $jsonConfig[$classify]['list_column']);

        // Konversi chatHistory ke dalam format messages
        $messages = [
            [
                'role' => 'system',
                'content' => "You are an AI assistant that generates only valid MariaDB queries based on user requests. 
                Use the following table information to construct the SQL query:

                - Table Name: $tableName
                - Available Columns: $columns

                Generate only the SQL query without any explanation or additional text.
                The query should include `LIMIT 10` to restrict the results."
            ]
        ];

        // Menambahkan chat history sebagai konteks
        foreach ($chatHistory as $chat) {
            if (isset($chat['user'])) {
                $messages[] = ['role' => 'user', 'content' => $chat['user']];
            }
            if (isset($chat['rawBotResponse'])) {
                $messages[] = ['role' => 'assistant', 'content' => $chat['rawBotResponse']];
            }
        }

        // Tambahkan prompt utama pengguna
        $messages[] = ['role' => 'user', 'content' => $prompt];

        // Kirim permintaan ke model AI
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => $messages
        ]);

        return trim($response['choices'][0]['message']['content'] ?? 'No response');
    }

    
    // public function createMainQuery($classify, $prompt)
    // {
    //     // Load file JSON
    //     $jsonPath = public_path('templates/table_config.json');
    //     $jsonConfig = json_decode(file_get_contents($jsonPath), true);

    //     // Pastikan kategori tersedia dalam konfigurasi
    //     if (!isset($jsonConfig[$classify])) {
    //         return "Error: Kategori tidak ditemukan dalam konfigurasi.";
    //     }

    //     // Ambil nama tabel dan kolom
    //     $tableName = $jsonConfig[$classify]['table_name'];
    //     $columns = implode(', ', $jsonConfig[$classify]['list_column']);

    //     $response = $this->client->chat()->create([
    //         'model' => 'gpt-4o-mini',
    //         'messages' => [
    //             [
    //                 'role' => 'system',
    //                 'content' => "You are an AI assistant that generates only valid MariaDB queries based on user requests. 
    //                 Use the following table information to construct the SQL query:

    //                 - Table Name: $tableName
    //                 - Available Columns: $columns

    //                 Generate only the SQL query without any explanation or additional text
    //                 The query should include `LIMIT 10` to restrict the results."
    //             ],
    //             [
    //                 'role' => 'user',
    //                 'content' => $prompt
    //             ],
    //         ],
    //     ]);
    
    //     return trim($response['choices'][0]['message']['content'] ?? 'No response');
    // }

    public function validatePromptTopic($prompt)
    {
        $messages = [
            [
                'role' => 'system',
                'content' => "You are a classification expert. Determine if the user's request is related to the Indonesian Ministry of Public Works and Public Housing (PUPR), DPUTR, SIMBG, or construction-related tasks managed by these institutions. 
            
    Only respond with:
    - VALID → if it's relevant to those topics
    - INVALID → if not related at all"
            ],
            ['role' => 'user', 'content' => $prompt],
        ];

        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
        ]);

        return trim($response['choices'][0]['message']['content'] ?? 'INVALID');
    }
}
