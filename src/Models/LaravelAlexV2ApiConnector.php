<?php

namespace DaVikingCode\LaravelAlexV2Api\Models;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\App;

class LaravelAlexV2ApiConnector
{
    public $sp_entity_id;
    public $cert;
    public $ssl_key;
    public $token;
    public $api_url;
    public $api_password;
    public $client;

    public function __construct()
    {
        // environment
        if(App::environment() == 'production')
        {
            $this->sp_entity_id = config('laravelalexv2api.sp_entity_id_prod');
            $this->api_url = config('laravelalexv2api.api_url_prod');
        }
        else // dev or local
        {
            $this->sp_entity_id = config('laravelalexv2api.sp_entity_id_dev');
            $this->api_url = config('laravelalexv2api.api_url_dev');
        }

        $this->cert = storage_path(config('laravelalexv2api.cert_path') . '/' . config('laravelalexv2api.server_pem_file'));
        $this->ssl_key = storage_path(config('laravelalexv2api.cert_path') . '/' . config('laravelalexv2api.server_key_file'));
        $this->api_password = config('laravelalexv2api.alex_v2_api_password');

        $this->token = $this->ws_auth_cta();

        $this->client = new Client();
    }

    // 6.1	Authentification d’un compte technique applicatif au Webservice ALEXV2 (service : ws_auth_cta)
    public function ws_auth_cta()
    {
        $client = new Client();
        $endpoint = $this->api_url . "ws_auth_cta";
        $headers = ['Content-Type' => 'application/json'];
        $content = ['av2_S_ct_identifiant' => $this->sp_entity_id, 'av2_S_ct_mdp' => $this->api_password];

        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
//                'connect_timeout' => 650,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );

        return json_decode($response->getBody()->getContents())->token;
    }

}
