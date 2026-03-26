<?php

namespace App\Services;

use App\Models\GlobalSetting;
use App\Models\PbgStatus;
use App\Models\PbgTask;
use App\Models\PbgTaskDetail;
use App\Models\PbgTaskDetailDataList;
use App\Models\PbgTaskIndexIntegrations;
use App\Models\PbgTaskPrasarana;
use App\Models\PbgTaskRetributions;
use App\Models\TaskAssignment;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
class ServiceTabPbgTask
{
    private $client;
    private $simbg_host;
    private $fetch_per_page;
    private $service_token;
    private $user_token;
    private $user_refresh_token;
    protected $current_uuid = null;

    public function __construct(Client $client, ServiceTokenSIMBG $service_token)
    {
        $settings = GlobalSetting::whereIn('key', ['SIMBG_HOST', 'FETCH_PER_PAGE'])
            ->pluck('value', 'key');

        $this->simbg_host = trim((string) ($settings['SIMBG_HOST'] ?? ""));
        $this->fetch_per_page = trim((string) ($settings['FETCH_PER_PAGE'] ?? "10"));
        $this->client = $client;
        $this->service_token = $service_token;
        $auth_data = $this->service_token->get_token();
        $this->user_token = $auth_data['access'];
        $this->user_refresh_token = $auth_data['refresh'];
    }

    public function run_service($retry_uuid = null, $chunk_size = 50)
    {
        try {
            $query = PbgTask::orderBy('id');
            
            // If retry_uuid is provided, start from that UUID
            if ($retry_uuid) {
                $retryTask = PbgTask::where('uuid', $retry_uuid)->first();
                if ($retryTask) {
                    $query->where('id', '>=', $retryTask->id);
                    Log::info("Resuming sync from UUID: {$retry_uuid} (ID: {$retryTask->id})");
                }
            }

            $totalTasks = $query->count();
            $processedCount = 0;
            
            Log::info("Starting sync for {$totalTasks} PBG Tasks with chunk size: {$chunk_size}");

            // Process in chunks to reduce memory usage
            $query->chunk($chunk_size, function ($pbg_tasks) use (&$processedCount, $totalTasks) {
                $chunkStartTime = now();
                
                foreach ($pbg_tasks as $pbg_task) {
                    try {
                        $this->current_uuid = $pbg_task->uuid;
                        $taskStartTime = now();
                        
                        // Process all endpoints for this task
                        $this->processTaskEndpoints($pbg_task->uuid);
                        
                        $processedCount++;
                        $taskTime = now()->diffInSeconds($taskStartTime);
                        
                        // Log progress every 10 tasks
                        if ($processedCount % 10 === 0) {
                            $progress = round(($processedCount / $totalTasks) * 100, 2);
                            Log::info("Progress: {$processedCount}/{$totalTasks} ({$progress}%) - Last task took {$taskTime}s");
                        }
                        
                    } catch (\Exception $e) {
                        Log::error("Failed on UUID: {$this->current_uuid}, Error: " . $e->getMessage());
                        
                        // Check if this is a critical error that should stop the process
                        if ($this->isCriticalError($e)) {
                            throw $e;
                        }
                        
                        // For non-critical errors, log and continue
                        Log::warning("Skipping UUID {$this->current_uuid} due to non-critical error");
                        continue;
                    }
                }
                
                $chunkTime = now()->diffInSeconds($chunkStartTime);
                Log::info("Processed chunk of {$pbg_tasks->count()} tasks in {$chunkTime} seconds");
                
                // Small delay between chunks to prevent API rate limiting
                if ($pbg_tasks->count() === $chunk_size) {
                    sleep(1);
                }
            });
            
            Log::info("Successfully completed sync for {$processedCount} PBG Tasks");
            
        } catch (\Exception $e) {
            Log::error("Failed to synchronize: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process all endpoints for a single task
     */
    private function processTaskEndpoints(string $uuid): void
    {
        $this->scraping_task_details($uuid);
        $this->scraping_pbg_data_list($uuid);
        // $this->scraping_task_assignments($uuid);
        $this->scraping_task_retributions($uuid);
        $this->scraping_task_integrations($uuid);
    }

    /**
     * Determine if an error is critical and should stop the process
     */
    private function isCriticalError(\Exception $e): bool
    {
        $message = $e->getMessage();
        
        // Critical authentication errors
        if (strpos($message, 'Token refresh and login failed') !== false) {
            return true;
        }
        
        // Critical system errors
        if (strpos($message, 'Connection refused') !== false) {
            return true;
        }
        
        // Database connection errors
        if (strpos($message, 'database') !== false && strpos($message, 'connection') !== false) {
            return true;
        }
        
        return false;
    }

    public function getFailedUUID(){
        return $this->current_uuid;
    }

    public function scraping_task_details($uuid)
    {
        $url = "{$this->simbg_host}/api/pbg/v1/detail/{$uuid}/";
        $options = [
            'headers' => [
                'Authorization' => "Bearer {$this->user_token}",
                'Content-Type' => 'application/json'
            ]
        ];

        $maxRetries = 3;
        $initialDelay = 1;
        $retriedAfter401 = false;

        for ($retryCount = 0; $retryCount < $maxRetries; $retryCount++) {
            try {
                $response = $this->client->get($url, $options);
                $responseData = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

                if (empty($responseData['data']) || !is_array($responseData['data'])) {
                    return true;
                }

                $data = $responseData['data'];

                Log::info("Executed uid : {$uuid}");

                // Use the static method from PbgTaskDetail model to create/update
                PbgTaskDetail::createFromApiResponse($data, $uuid);

                return $responseData;
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                if ($e->getCode() === 401 && !$retriedAfter401) {
                    Log::warning("401 Unauthorized - Refreshing token and retrying...");
                    try{
                        $this->refreshToken();
                        $options['headers']['Authorization'] = "Bearer {$this->user_token}";
                        $retriedAfter401 = true;
                        continue;
                    }catch(\Exception $refreshError){
                        Log::error("Token refresh and login failed: " . $refreshError->getMessage());
                        return false;
                    }
                }

                return false;
            } catch (\GuzzleHttp\Exception\ServerException | \GuzzleHttp\Exception\ConnectException $e) {
                if ($e->getCode() === 502) {
                    Log::warning("502 Bad Gateway - Retrying in {$initialDelay} seconds...");
                } else {
                    Log::error("Network error ({$e->getCode()}) - Retrying in {$initialDelay} seconds...");
                }

                sleep($initialDelay);
                $initialDelay *= 2;
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                Log::error("Request error ({$e->getCode()}): " . $e->getMessage());
                return false;
            } catch (\JsonException $e) {
                Log::error("JSON decoding error: " . $e->getMessage());
                return false;
            } catch (\Throwable $e) {
                Log::critical("Unhandled error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                return false;
            }
        }

        Log::error("Failed to fetch task details for UUID {$uuid} after {$maxRetries} retries.");
        throw new \Exception("Failed to fetch task details for UUID {$uuid} after retries.");
    }

    public function scraping_task_detail_status($uuid)
    {
        $url = "{$this->simbg_host}/api/pbg/v1/detail/{$uuid}/status/";
        $options = [
            'headers' => [
                'Authorization' => "Bearer {$this->user_token}",
                'Content-Type' => 'application/json'
            ]
        ];

        $maxRetries = 3;
        $initialDelay = 1;
        $retriedAfter401 = false;

        for ($retryCount = 0; $retryCount < $maxRetries; $retryCount++) {
            try {
                $response = $this->client->get($url, $options);
                $responseData = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

                if (empty($responseData['data']) || !is_array($responseData['data'])) {
                    return true;
                }

                $data = $responseData['data'];

                // Use the static method from PbgTaskDetail model to create/update
                PbgStatus::createOrUpdateFromApi($data, $uuid);

                return $responseData;
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                if ($e->getCode() === 401 && !$retriedAfter401) {
                    Log::warning("401 Unauthorized - Refreshing token and retrying...");
                    try{
                        $this->refreshToken();
                        $options['headers']['Authorization'] = "Bearer {$this->user_token}";
                        $retriedAfter401 = true;
                        continue;
                    }catch(\Exception $refreshError){
                        Log::error("Token refresh and login failed: " . $refreshError->getMessage());
                        return false;
                    }
                }

                return false;
            } catch (\GuzzleHttp\Exception\ServerException | \GuzzleHttp\Exception\ConnectException $e) {
                if ($e->getCode() === 502) {
                    Log::warning("502 Bad Gateway - Retrying in {$initialDelay} seconds...");
                } else {
                    Log::error("Network error ({$e->getCode()}) - Retrying in {$initialDelay} seconds...");
                }

                sleep($initialDelay);
                $initialDelay *= 2;
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                Log::error("Request error ({$e->getCode()}): " . $e->getMessage());
                return false;
            } catch (\JsonException $e) {
                Log::error("JSON decoding error: " . $e->getMessage());
                return false;
            } catch (\Throwable $e) {
                Log::critical("Unhandled error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                return false;
            }
        }

        Log::error("Failed to fetch task detail status for UUID {$uuid} after {$maxRetries} retries.");
        throw new \Exception("Failed to fetch task details for UUID {$uuid} after retries.");
    }

    public function scraping_task_assignments($uuid)
    {
        $url = "{$this->simbg_host}/api/pbg/v1/list-tim-penilai/{$uuid}/?page=1&size=10";
        $options = [
            'headers' => [
                'Authorization' => "Bearer {$this->user_token}",
                'Content-Type' => 'application/json'
            ]
        ];

        $maxRetries = 3;
        $initialDelay = 1;
        $retriedAfter401 = false;

        for ($retryCount = 0; $retryCount < $maxRetries; $retryCount++) {
            try {
                $response = $this->client->get($url, $options);
                $responseData = json_decode($response->getBody()->getContents(), true);

                if (empty($responseData['data']) || !is_array($responseData['data'])) {
                    return true;
                }

                $task_assignments = [];

                foreach ($responseData['data'] as $data) {
                    $task_assignments[] = [
                        'pbg_task_uid' => $uuid,
                        'user_id' => $data['user_id'] ?? null,
                        'name' => $data['name'] ?? null,
                        'username' => $data['username'] ?? null,
                        'email' => $data['email'] ?? null,
                        'phone_number' => $data['phone_number'] ?? null,
                        'role' => $data['role'] ?? null,
                        'role_name' => $data['role_name'] ?? null,
                        'is_active' => $data['is_active'] ?? false,
                        'file' => !empty($data['file']) ? json_encode($data['file']) : null,
                        'expertise' => !empty($data['expertise']) ? json_encode($data['expertise']) : null,
                        'experience' => !empty($data['experience']) ? json_encode($data['experience']) : null,
                        'is_verif' => $data['is_verif'] ?? false,
                        'uid' => $data['uid'] ?? null,
                        'status' => $data['status'] ?? null,
                        'status_name' => $data['status_name'] ?? null,
                        'note' => $data['note'] ?? null,
                        'ta_id' => $data['id'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($task_assignments)) {
                    TaskAssignment::upsert(
                        $task_assignments,
                        ['uid'],
                        ['ta_id', 'name', 'username', 'email', 'phone_number', 'role', 'role_name', 'is_active', 'file', 'expertise', 'experience', 'is_verif', 'status', 'status_name', 'note', 'updated_at']
                    );
                }

                return $responseData;
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                if ($e->getCode() === 401 && !$retriedAfter401) {
                    Log::warning("401 Unauthorized - Refreshing token and retrying...");
                    try{
                        $this->refreshToken();
                        $options['headers']['Authorization'] = "Bearer {$this->user_token}";
                        $retriedAfter401 = true;
                        continue;
                    }catch(\Exception $refreshError){
                        Log::error("Token refresh and login failed: " . $refreshError->getMessage());
                        return false;
                    }
                }

                throw $e;
            } catch (\GuzzleHttp\Exception\ServerException | \GuzzleHttp\Exception\ConnectException $e) {
                if ($e->getCode() === 502) {
                    Log::warning("502 Bad Gateway - Retrying in {$initialDelay} seconds...");
                } else {
                    Log::error("Network error ({$e->getCode()}) - Retrying in {$initialDelay} seconds...");
                }

                sleep($initialDelay);
                $initialDelay *= 2;
            } catch (\Exception $e) {
                Log::error("Unexpected error: " . $e->getMessage());
                throw $e;
            }
        }

        Log::error("Failed to fetch task assignments for UUID {$uuid} after {$maxRetries} retries.");
        throw new \Exception("Failed to fetch task assignments for UUID {$uuid} after retries.");
    }

    public function scraping_pbg_data_list($uuid){
        $url = "{$this->simbg_host}/api/pbg/v1/detail/{$uuid}/list-data/?sort=DESC";
        $options = [
            'headers' => [
                'Authorization' => "Bearer {$this->user_token}",
                'Content-Type' => 'application/json'
            ]
        ];

        $maxRetries = 3;
        $initialDelay = 1;
        $retriedAfter401 = false;

        for ($retryCount = 0; $retryCount < $maxRetries; $retryCount++) {
            try{
                $response = $this->client->get($url, $options);
                $responseData = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

                if (empty($responseData['data']) || !is_array($responseData['data'])) {
                    Log::info("No data list found for UUID: {$uuid}");
                    return true;
                }

                $data = $responseData['data'];
                
                Log::info("Processing data list for UUID: {$uuid}, found " . count($data) . " items");

                // Process each data list item and save to database
                $this->processDataListItems($data, $uuid);

                return $responseData;
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                if ($e->getCode() === 401 && !$retriedAfter401) {
                    Log::warning("401 Unauthorized - Refreshing token and retrying...");
                    try{
                        $this->refreshToken();
                        $options['headers']['Authorization'] = "Bearer {$this->user_token}";
                        $retriedAfter401 = true;
                        continue;
                    }catch(\Exception $refreshError){
                        Log::error("Token refresh and login failed: " . $refreshError->getMessage());
                        return false;
                    }
                }

                return false;
            } catch (\GuzzleHttp\Exception\ServerException | \GuzzleHttp\Exception\ConnectException $e) {
                if ($e->getCode() === 502) {
                    Log::warning("502 Bad Gateway - Retrying in {$initialDelay} seconds...");
                } else {
                    Log::error("Network error ({$e->getCode()}) - Retrying in {$initialDelay} seconds...");
                }

                sleep($initialDelay);
                $initialDelay *= 2;
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                Log::error("Request error ({$e->getCode()}): " . $e->getMessage());
                return false;
            } catch (\JsonException $e) {
                Log::error("JSON decoding error: " . $e->getMessage());
                return false;
            } catch (\Throwable $e) {
                Log::critical("Unhandled error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                return false;
            }
        }

        Log::error("Failed to fetch task data list for UUID {$uuid} after {$maxRetries} retries.");
        throw new \Exception("Failed to fetch task data list for UUID {$uuid} after retries.");
    }

    /**
     * Process and save data list items to database (Optimized with bulk operations)
     */
    private function processDataListItems(array $dataListItems, string $pbgTaskUuid): void
    {
        try {
            if (empty($dataListItems)) {
                return;
            }

            $batchData = [];
            $validItems = 0;

            foreach ($dataListItems as $item) {
                // Validate required fields
                if (empty($item['uid'])) {
                    Log::warning("Skipping data list item with missing UID for PBG Task: {$pbgTaskUuid}");
                    continue;
                }

                // Parse created_at if exists
                $createdAt = null;
                if (!empty($item['created_at'])) {
                    try {
                        $createdAt = Carbon::parse($item['created_at'])->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        Log::warning("Invalid created_at format for data list UID: {$item['uid']}, Error: " . $e->getMessage());
                    }
                }

                $batchData[] = [
                    'uid' => $item['uid'],
                    'name' => $item['name'] ?? null,
                    'description' => $item['description'] ?? null,
                    'status' => $item['status'] ?? null,
                    'status_name' => $item['status_name'] ?? null,
                    'data_type' => $item['data_type'] ?? null,
                    'data_type_name' => $item['data_type_name'] ?? null,
                    'file' => $item['file'] ?? null,
                    'note' => $item['note'] ?? null,
                    'pbg_task_uuid' => $pbgTaskUuid,
                    'created_at' => $createdAt ?: now(),
                    'updated_at' => now(),
                ];

                $validItems++;
            }

            if (!empty($batchData)) {
                // Use upsert for bulk insert/update operations
                PbgTaskDetailDataList::upsert(
                    $batchData,
                    ['uid'], // Unique columns
                    [
                        'name', 'description', 'status', 'status_name', 
                        'data_type', 'data_type_name', 'file', 'note', 
                        'pbg_task_uuid', 'updated_at'
                    ] // Columns to update
                );

                Log::info("Successfully bulk processed {$validItems} data list items for PBG Task: {$pbgTaskUuid}");
            }

        } catch (\Exception $e) {
            Log::error("Error bulk processing data list items for PBG Task {$pbgTaskUuid}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Alternative method using PbgTask model's syncDataLists for cleaner code
     */
    private function processDataListItemsWithModel(array $dataListItems, string $pbgTaskUuid): void
    {
        try {
            // Find the PbgTask
            $pbgTask = PbgTask::where('uuid', $pbgTaskUuid)->first();
            
            if (!$pbgTask) {
                Log::error("PBG Task not found with UUID: {$pbgTaskUuid}");
                return;
            }

            // Use the model's syncDataLists method
            $pbgTask->syncDataLists($dataListItems);
            
            $processedCount = count($dataListItems);
            Log::info("Successfully synced {$processedCount} data list items for PBG Task: {$pbgTaskUuid} using model method");

        } catch (\Exception $e) {
            Log::error("Error syncing data list items for PBG Task {$pbgTaskUuid}: " . $e->getMessage());
            throw $e;
        }
    }

    public function scraping_task_retributions($uuid)
    {
        $url = "{$this->simbg_host}/api/pbg/v1/detail/" . $uuid . "/retribution/submit/";
        $options = [
            'headers' => [
                'Authorization' => "Bearer {$this->user_token}",
                'Content-Type' => 'application/json'
            ]
        ];

        $maxRetries = 3;
        $initialDelay = 1;
        $retriedAfter401 = false;

        for ($retryCount = 0; $retryCount < $maxRetries; $retryCount++) {
            try {
                $response = $this->client->get($url, $options);
                $responseData = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

                if (empty($responseData['data']) || !is_array($responseData['data'])) {
                    return true;
                }

                $data = $responseData['data'];

                $detailCreatedAt = isset($data['created_at']) 
                    ? Carbon::parse($data['created_at'])->format('Y-m-d H:i:s') 
                    : null;
        
                $detailUpdatedAt = isset($data['updated_at']) 
                    ? Carbon::parse($data['updated_at'])->format('Y-m-d H:i:s') 
                    : null;
        
                $pbg_task_retributions = PbgTaskRetributions::updateOrCreate(
                    ['detail_id' => $data['id']],
                    [
                        'detail_uid' => $data['uid'] ?? null,
                        'detail_created_at' => $detailCreatedAt ?? null,
                        'detail_updated_at' => $detailUpdatedAt ?? null,
                        'luas_bangunan' => $data['luas_bangunan'] ?? null,
                        'indeks_lokalitas' => $data['indeks_lokalitas'] ?? null,
                        'wilayah_shst' => $data['wilayah_shst'] ?? null,
                        'kegiatan_id' => $data['kegiatan']['id'] ?? null,
                        'kegiatan_name' => $data['kegiatan']['name'] ?? null,
                        'nilai_shst' => $data['nilai_shst'] ?? null,
                        'indeks_terintegrasi' => $data['indeks_terintegrasi'] ?? null,
                        'indeks_bg_terbangun' => $data['indeks_bg_terbangun'] ?? null,
                        'nilai_retribusi_bangunan' => $data['nilai_retribusi_bangunan'] ?? null,
                        'nilai_prasarana' => $data['nilai_prasarana'] ?? null,
                        'created_by' => $data['created_by'] ?? null,
                        'pbg_document' => $data['pbg_document'] ?? null,
                        'underpayment' => $data['underpayment'] ?? null,
                        'skrd_amount' => $data['skrd_amount'] ?? null,
                        'pbg_task_uid' => $uuid,
                    ]
                );
        
                $pbg_task_retribution_id = $pbg_task_retributions->id;
        
                $prasaranaData = $data['prasarana'] ?? [];
                if (!empty($prasaranaData)) {
                    $insertData = array_map(fn($item) => [
                        'pbg_task_uid' => $uuid,
                        'pbg_task_retribution_id' => $pbg_task_retribution_id,
                        'prasarana_id' => $item['id'] ?? null,
                        'prasarana_type' => $item['prasarana_type'] ?? null,
                        'building_type' => $item['building_type'] ?? null,
                        'total' => $item['total'] ?? null,
                        'quantity' => $item['quantity'] ?? null,
                        'unit' => $item['unit'] ?? null,
                        'index_prasarana' => $item['index_prasarana'] ?? null,
                    ], $prasaranaData);
            
                    PbgTaskPrasarana::upsert($insertData, ['prasarana_id']);
                }

                return $responseData;
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                if ($e->getCode() === 401 && !$retriedAfter401) {
                    Log::warning("401 Unauthorized - Refreshing token and retrying...");
                    try{
                        $this->refreshToken();
                        $options['headers']['Authorization'] = "Bearer {$this->user_token}";
                        $retriedAfter401 = true;
                        continue;
                    }catch(\Exception $refreshError){
                        Log::error("Token refresh and login failed: " . $refreshError->getMessage());
                        return false;
                    }
                }

                return false;
            } catch (\GuzzleHttp\Exception\ServerException | \GuzzleHttp\Exception\ConnectException $e) {
                if ($e->getCode() === 502) {
                    Log::warning("502 Bad Gateway - Retrying in {$initialDelay} seconds...");
                } else {
                    Log::error("Network error ({$e->getCode()}) - Retrying in {$initialDelay} seconds...");
                }

                sleep($initialDelay);
                $initialDelay *= 2;
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                Log::error("Request error ({$e->getCode()}): " . $e->getMessage());
                return false;
            } catch (\JsonException $e) {
                Log::error("JSON decoding error: " . $e->getMessage());
                return false;
            } catch (\Throwable $e) {
                Log::critical("Unhandled error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                return false;
            }
        }

        Log::error("Failed to fetch task retributions for UUID {$uuid} after retries.");
        throw new \Exception("Failed to fetch task retributions for UUID {$uuid} after retries.");
    }

    public function scraping_task_integrations($uuid){
        $url = "{$this->simbg_host}/api/pbg/v1/detail/" . $uuid . "/retribution/indeks-terintegrasi/";
        $options = [
            'headers' => [
                'Authorization' => "Bearer {$this->user_token}",
                'Content-Type' => 'application/json'
            ]
        ];
        $maxRetries = 3;
        $initialDelay = 1;
        $retriedAfter401 = false;
        for ($retryCount = 0; $retryCount < $maxRetries; $retryCount++) {
            try {
                $response = $this->client->get($url, $options);
                $responseData = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

                if (empty($responseData['data']) || !is_array($responseData['data'])) {
                    return true;
                }

                $data = $responseData['data'];

                $integrations[] = [
                    'pbg_task_uid' => $uuid,
                    'indeks_fungsi_bangunan' => $data['indeks_fungsi_bangunan'] ?? null,
                    'indeks_parameter_kompleksitas' => $data['indeks_parameter_kompleksitas'] ?? null,
                    'indeks_parameter_permanensi' => $data['indeks_parameter_permanensi'] ?? null,
                    'indeks_parameter_ketinggian' => $data['indeks_parameter_ketinggian'] ?? null,
                    'faktor_kepemilikan' => $data['faktor_kepemilikan'] ?? null,
                    'indeks_terintegrasi' => $data['indeks_terintegrasi'] ?? null,
                    'total' => $data['total'] ?? null,
                ];

                if (!empty($integrations)) {
                    PbgTaskIndexIntegrations::upsert($integrations, ['pbg_task_uid'], ['indeks_fungsi_bangunan', 
                    'indeks_parameter_kompleksitas', 'indeks_parameter_permanensi', 'indeks_parameter_ketinggian', 'faktor_kepemilikan', 'indeks_terintegrasi', 'total']);
                }

                return $responseData;
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                if ($e->getCode() === 401 && !$retriedAfter401) {
                    Log::warning("401 Unauthorized - Refreshing token and retrying...");
                    try{
                        $this->refreshToken();
                        $options['headers']['Authorization'] = "Bearer {$this->user_token}";
                        $retriedAfter401 = true;
                        continue;
                    }catch(\Exception $refreshError){
                        Log::error("Token refresh and login failed: " . $refreshError->getMessage());
                        return false;
                    }
                }

                return false;
            } catch (\GuzzleHttp\Exception\ServerException | \GuzzleHttp\Exception\ConnectException $e) {
                if ($e->getCode() === 502) {
                    Log::warning("502 Bad Gateway - Retrying in {$initialDelay} seconds...");
                } else {
                    Log::error("Network error ({$e->getCode()}) - Retrying in {$initialDelay} seconds...");
                }

                sleep($initialDelay);
                $initialDelay *= 2;
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                Log::error("Request error ({$e->getCode()}): " . $e->getMessage());
                return false;
            } catch (\JsonException $e) {
                Log::error("JSON decoding error: " . $e->getMessage());
                return false;
            } catch (\Throwable $e) {
                Log::critical("Unhandled error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                return false;
            }
        }

        Log::error("Failed to fetch task index integration for UUID {$uuid} after retries.");
        throw new \Exception("Failed to fetch task index integration for UUID {$uuid} after retries.");
    }

    private function refreshToken()
    {
        $maxRetries = 3; // Maximum retry attempts
        $attempt = 0; 

        while ($attempt < $maxRetries) {
            try {
                $attempt++;
                Log::info("Attempt $attempt: Refreshing token...");

                $newAuthToken = $this->service_token->refresh_token($this->user_refresh_token);

                if (!isset($newAuthToken['access']) || !isset($newAuthToken['refresh'])) {
                    throw new \Exception("Invalid refresh token response.");
                }

                $this->user_token = $newAuthToken['access'];
                $this->user_refresh_token = $newAuthToken['refresh'];

                Log::info("Token refreshed successfully on attempt $attempt.");
                return; // Exit function on success
            } catch (\Exception $e) {
                Log::error("Token refresh failed on attempt $attempt: " . $e->getMessage());

                if ($attempt >= $maxRetries) {
                    Log::info("Max retries reached. Attempting to log in again...");
                    break;
                }

                sleep(30); // Wait for 30 seconds before retrying
            }
        }

        // If refresh fails after retries, attempt re-login
        $attempt = 0;
        while ($attempt < $maxRetries) {
            try {
                $attempt++;
                Log::info("Attempt $attempt: Re-logging in...");

                $loginAgain = $this->service_token->get_token(); // Login again

                if (!isset($loginAgain['access']) || !isset($loginAgain['refresh'])) {
                    throw new \Exception("Invalid login response.");
                }

                $this->user_token = $loginAgain['access'];
                $this->user_refresh_token = $loginAgain['refresh'];

                Log::info("Re-login successful on attempt $attempt.");
                return; // Exit function on success
            } catch (\Exception $e) {
                Log::error("Re-login failed on attempt $attempt: " . $e->getMessage());

                if ($attempt >= $maxRetries) {
                    throw new \Exception("Both token refresh and login failed after $maxRetries attempts. " . $e->getMessage());
                }

                sleep(30); // Wait for 30 seconds before retrying
            }
        }
    }

}
