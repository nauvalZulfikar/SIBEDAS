<?php

namespace App\Services;
use App\Models\GlobalSetting;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
class ServiceTokenSIMBG
{
    private $client;
    private $login_url;
    private $email;
    private $password;
    private $simbg_host;
    private $fetch_per_page;
    private $refresh_url;

    public function __construct()
    {
        $settings = GlobalSetting::whereIn('key', [
            'SIMBG_EMAIL', 'SIMBG_PASSWORD', 'SIMBG_HOST', 'FETCH_PER_PAGE'
        ])->pluck('value', 'key');
        $this->email = trim((string) ($settings['SIMBG_EMAIL'] ?? ""));
        $this->password = trim((string) ($settings['SIMBG_PASSWORD'] ?? ""));
        $this->simbg_host = trim((string) ($settings['SIMBG_HOST'] ?? ""));
        $this->fetch_per_page = trim((string) ($settings['FETCH_PER_PAGE'] ?? ""));
        $this->client = new Client();
        $this->login_url = $this->simbg_host . "/api/user/v1/auth/login/";
        $this->refresh_url = $this->simbg_host. "/api/user/v1/auth/token/refresh/";
    }

    public function get_token(){
        try {
            $response = $this->client->request('POST', $this->login_url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'email' => $this->email,
                    'password' => $this->password
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['token'];
        } catch (RequestException $e) {
            Log::error("Failed to get token", [
                'error' => $e->getMessage(),
                'response' => $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null
            ]);
            return null;
        }
    }

    public function refresh_token(string $refresh_token){
        try {
            $response = $this->client->request('POST', $this->refresh_url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'refresh' => $refresh_token
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data;
        } catch (\Throwable $th) {
            Log::error("Failed to refresh token", [
                'error' => $th->getMessage()
            ]);
            return null;
        }
    }
}
