<?php

namespace App\Services;

use App\Models\GlobalSetting;
use App\Models\PbgTask;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServicePbgTask
{
    private $client;
    private $simbg_host;
    private $fetch_per_page;
    private $pbg_task_url;
    private $service_token;
    private $user_token;
    private $user_refresh_token;

    public function __construct(Client $client, ServiceTokenSIMBG $service_token)
    {
        $settings = GlobalSetting::whereIn('key', ['SIMBG_HOST', 'FETCH_PER_PAGE'])
            ->pluck('value', 'key');

        $this->simbg_host = trim((string) ($settings['SIMBG_HOST'] ?? ""));
        $this->fetch_per_page = trim((string) ($settings['FETCH_PER_PAGE'] ?? "10"));
        $this->client = $client;
        $this->service_token = $service_token;
        $this->pbg_task_url = "{$this->simbg_host}/api/pbg/v1/list/?page=1&size={$this->fetch_per_page}&sort=ASC";
        $auth_data = $this->service_token->get_token();
        $this->user_token = $auth_data['access'];
        $this->user_refresh_token = $auth_data['refresh'];
    }

    public function run_service()
    {
        try {
            $this->fetch_pbg_task();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Calculate usulan_retribusi for upserted tasks.
     * Formula: retribusi_per_m2 (from retribution_estimates by function_type) × total_area × unit
     * Auto 0 for status 3 (dibatalkan) and 9 (ditolak), or if no match found.
     */
    private function calculateUsulanRetribusi(array $savedData): void
    {
        $estimates = DB::table('retribution_estimates')
            ->whereNotNull('usulan_retribusi_per_m2')
            ->where('is_active', true)
            ->pluck('usulan_retribusi_per_m2', 'fungsi_bg');

        $uuids = array_column($savedData, 'uuid');
        $tasks = PbgTask::whereIn('uuid', $uuids)->get();

        foreach ($tasks as $task) {
            if (in_array((int) $task->status, [3, 9])) {
                $task->usulan_retribusi = 0;
            } else {
                $perM2 = $estimates[$task->function_type] ?? null;
                if ($perM2 && $task->total_area && $task->unit) {
                    $task->usulan_retribusi = $perM2 * $task->total_area * (int) $task->unit;
                } else {
                    $task->usulan_retribusi = 0;
                }
            }
            $task->save();
        }
    }

    private function fetch_pbg_task()
    {
        try {
            $currentPage = 1;
            $totalPage = 1;

            $options = [
                'headers' => [
                    'Authorization' => "Bearer {$this->user_token}",
                    'Content-Type' => 'application/json'
                ]
            ];

            $maxRetries = 3; // Maximum number of retries
            $initialDelay = 1; // Initial delay in seconds

            $fetchData = function ($url) use (&$options, $maxRetries, $initialDelay) {
                $retryCount = 0;

                while ($retryCount < $maxRetries) {
                    try {
                        return $this->client->get($url, $options);
                    } catch (\GuzzleHttp\Exception\ClientException $e) {
                        if ($e->getCode() === 401) {
                            Log::warning("Unauthorized. Refreshing token...");

                            // Refresh token
                            $auth_data = $this->service_token->refresh_token($this->user_refresh_token);
                            if (!isset($auth_data['access'])) {
                                Log::error("Token refresh failed.");
                                throw new Exception("Token refresh failed.");
                            }

                            // Update tokens
                            $this->user_token = $auth_data['access'];
                            $this->user_refresh_token = $auth_data['refresh'];

                            // Update headers
                            $options['headers']['Authorization'] = "Bearer {$this->user_token}";

                            // Retry request
                            return $this->client->get($url, $options);
                        }
                        throw $e;
                    } catch (\GuzzleHttp\Exception\ServerException | \GuzzleHttp\Exception\ConnectException $e) {
                        // Handle 502 or connection issues
                        if ($e->getCode() === 502) {
                            Log::warning("502 Bad Gateway - Retrying in {$initialDelay} seconds...");
                        } else {
                            Log::error("Network error - Retrying in {$initialDelay} seconds...");
                        }

                        $retryCount++;
                        sleep($initialDelay);
                        $initialDelay *= 2; // Exponential backoff
                    }
                }

                Log::error("Max retries reached. Failing request.");
                throw new Exception("Max retries reached. Failing request.");
            };

            do {
                $url = "{$this->simbg_host}/api/pbg/v1/list/?page={$currentPage}&size={$this->fetch_per_page}&sort=ASC&date&search&status&slf_status&type&sort_by=created_at&application_type=1&start_date&end_date";

                $fetch_data = $fetchData($url);
                if (!$fetch_data) {
                    Log::error("Failed to fetch data on page {$currentPage} after retries.");
                    throw new Exception("Failed to fetch data on page {$currentPage} after retries.");
                }

                $response = json_decode($fetch_data->getBody()->getContents(), true);
                if (!isset($response['data'])) {
                    Log::error("Invalid API response on page {$currentPage}");
                    throw new Exception("Invalid API response on page {$currentPage}");
                }

                $data = $response['data'];
                $totalPage = isset($response['total_page']) ? (int) $response['total_page'] : 1;

                Log::info("Total data scraping {$totalPage}");

                $saved_data = [];
                foreach ($data as $item) {
                    $saved_data[] = [
                        'uuid' => $item['uid'] ?? null,
                        'name' => $item['name'] ?? null,
                        'owner_name' => $item['owner_name'] ?? null,
                        'application_type' => $item['application_type'] ?? null,
                        'application_type_name' => $item['application_type_name'] ?? null,
                        'condition' => $item['condition'] ?? null,
                        'registration_number' => $item['registration_number'] ?? null,
                        'document_number' => $item['document_number'] ?? null,
                        'address' => $item['address'] ?? null,
                        'status' => $item['status'] ?? null,
                        'status_name' => $item['status_name'] ?? null,
                        'slf_status' => $item['slf_status'] ?? null,
                        'slf_status_name' => $item['slf_status_name'] ?? null,
                        'function_type' => $item['function_type'] ?? null,
                        'consultation_type' => $item['consultation_type'] ?? null,
                        'due_date' => $item['due_date'] ?? null,
                        'start_date' => $item['start_date'] ?? null,
                        'retribution' => $item['retribution'] ?? null,
                        'total_area' => $item['total_area'] ?? null,
                        'unit' => $item['unit'] ?? null,
                        'land_certificate_phase' => $item['land_certificate_phase'] ?? null,
                        'task_created_at' => isset($item['created_at']) ? Carbon::parse($item['created_at'])->format('Y-m-d H:i:s') : null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ];
                }

                if (!empty($saved_data)) {
                    PbgTask::upsert($saved_data, ['uuid'], [
                        'name',
                        'owner_name',
                        'application_type',
                        'application_type_name',
                        'condition',
                        'registration_number',
                        'document_number',
                        'address',
                        'status',
                        'status_name',
                        'slf_status',
                        'slf_status_name',
                        'function_type',
                        'consultation_type',
                        'due_date',
                        'start_date',
                        'retribution',
                        'total_area',
                        'unit',
                        'land_certificate_phase',
                        'task_created_at',
                        'updated_at'
                    ]);

                    $this->calculateUsulanRetribusi($saved_data);
                }

                $currentPage++;
                sleep(1);
            } while ($currentPage <= $totalPage);

            return true;
        } catch (Exception $e) {
            Log::error("Error fetching PBG tasks", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

}
