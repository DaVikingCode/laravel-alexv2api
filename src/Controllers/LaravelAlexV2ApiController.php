<?php

namespace DaVikingCode\LaravelAlexV2Api\Controllers;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Role;

class LaravelAlexV2ApiController extends Controller
{
    // Pour tester :
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_user_association('987654123@test.fr', 'Dupont', 'Jean');
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_user_dissociation('ZJR028NWD');
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_id_mod('ZJR028NWD', '781989484@test.fr', 'Dupont', 'Jean');
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_id_read('ZJR028NWD');
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_id_search('987654123@test.fr', 'Dupont');
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_profile_create('admin');
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_profile_add('ZJR028NWD', 'admin');
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_profile_rem('ZJR028NWD', 'admin');
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_profile_del('admin');
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_id_profile_read('ZJR028NWD');
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_profile_read();
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_profile_delegate('SP-OC0-recn1');
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_profile_delegation_del('SP-OC0-recn1');
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_profile_delegation_read();
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_custom_info_add('ZJR028NWD', 'Ceci est un test');
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_custom_info_del('ZJR028NWD', 'Ceci est un test2');
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_custom_info_read('ZJR028NWD');
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_custom_info_delegate('SP-OC0-recn1');
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_custom_info_delegation_del('SP-OC0-recn1');
    //        $result = app('App\Http\Controllers\Api\Alexv2ApiController')->ws_custom_info_delegation_read();
    //        dd($result);

    private $sp_entity_id;
    private $cert;
    private $ssl_key;
    private $jeton;
    private $api_url;
    private $api_password;

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

        $this->jeton = $this->ws_auth_cta();

//        $this->checkForProfiles(); // execute once
    }

    public function checkForProfiles() // add or remove app profiles
    {
        // Get all app roles list
        $roles_list = [];
        foreach (Role::all() as $role)
        {
            array_push($roles_list, $role->name);
        }

        // Get all Alex profiles list
        $profiles_list = json_decode($this->ws_profile_read()->data);

        // delete and add profiles
        $profiles_to_add = array_diff($roles_list, $profiles_list);
        $profiles_to_delete = array_diff($profiles_list, $roles_list);
        foreach ($profiles_to_delete as $profile)
        {
            $this->ws_profile_del(str_replace($this->sp_entity_id . '_','', $profile));
        }
        foreach ($profiles_to_add as $profile)
        {
            $this->ws_profile_create($profile);
        }

        // Set delegation
        $delegation = $this->ws_profile_delegate($this->sp_entity_id);
        return $delegation;
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
        return json_decode($response->getBody()->getContents())->jeton;

//        // Autre méthode : Curl (fonctionne).
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, 'https://alexv2-api-recn1.enedis.fr:10443/alex-api/av2/ws_auth_cta');
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"av2_S_ct_identifiant\": \"SP-OC0-recn1\",\"av2_S_ct_mdp\": \"SKLtJMh-cU\"}");
//        curl_setopt($ch, CURLOPT_POST, 1);
//        curl_setopt($ch, CURLOPT_SSLCERT, $this->cert);
//        curl_setopt($ch, CURLOPT_SSLKEY, $this->ssl_key);
//        $headers = array();
//        $headers[] = 'Content-Type: application/json';
//        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//        $result = curl_exec($ch);
//        if (curl_errno($ch)) {
//            echo 'Error:' . curl_error($ch);
//        }
//        curl_close($ch);
//        return json_decode($result)->jeton;
    }

    // 7.1	Déclaration / Association d’un nouvel utilisateur (service : ws_user_association)
    public function ws_user_association(string $email, string $nom, string $prenom, array $infos_perso = [], array $profiles = [])
    {
        // format profiles
        $profiles_arr = [];
        $now = Carbon::now()->format('Ymd');
        foreach($profiles as $profile)
        {
            array_push($profiles_arr, ['av2_S_profil' =>$profile, 'av2_S_profil_deb' => $now, 'av2_S_profil_fin' => $now]);
        }
        $profiles_arr = json_decode(json_encode($profiles_arr));

        $client = new Client();
        $endpoint = $this->api_url . "ws_user_association";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $this->jeton,
            'av2_S_mail' => $email,
            'av2_S_nom' => $nom,
            'av2_S_prenom' => $prenom,
            'av2_S_informations_array' => $infos_perso,
            'av2_S_profils_array' => $profiles_arr,
        ];
        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 7.2	Déclaration du départ d’un utilisateur / dissociation (service : ws_user_dissociation)
    public function ws_user_dissociation($cn)
    {
        $client = new Client();
        $endpoint = $this->api_url . "ws_user_dissociation";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $this->jeton,
            'av2_S_cn' => $cn,
        ];
        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 7.3	Modification d’attribut d’un utilisateur (service : ws_id_mod)
    public function ws_id_mod($user, $email, $nom = null, $prenom = null)
    {
//        $user = User::where('id_interne', '=', $cn)->first(); // test with cn

        $client = new Client();
        $endpoint = $this->api_url . "ws_id_mod";
        $headers = ['Content-Type' => 'application/json'];
        if($user->email != $email) // email changed
        {
            $content = [
                'av2_S_jeton' => $this->jeton,
                'av2_S_cn' => $user->id_interne,
                'av2_S_mail_new' => $email,
                'av2_S_nom_new' => $nom,
                'av2_S_prenom_new' => $prenom,
            ];
        }
        else // same email
        {
            $content = [
                'av2_S_jeton' => $this->jeton,
                'av2_S_cn' => $user->id_interne,
                'av2_S_nom_new' => $nom,
                'av2_S_prenom_new' => $prenom,
            ];
        }

        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 7.4	Lecture d’attributs d’un utilisateur (service : ws_id_read)
    public function ws_id_read($cn)
    {
        $client = new Client();
        $endpoint = $this->api_url . "ws_id_read";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $this->jeton,
            'av2_S_cn' => $cn,
        ];
        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 7.5	Recherche de comptes utilisateurs par attributs (service : ws_id_search)
    public function ws_id_search($email, $nom = null, $portail = null, $information = null)
    {
        $client = new Client();
        $endpoint = $this->api_url . "ws_id_search";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $this->jeton,
            'av2_S_mail' => $email,
            'av2_S_nom' => $nom,
            'av2_S_profil_portail' => $portail,
            'av2_S_information' => $information,
        ];
        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 8.1	Création d’un profil (service : ws_profile_create)
    public function ws_profile_create($profil)
    {
        $client = new Client();
        $endpoint = $this->api_url . "ws_profile_create";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $this->jeton,
            'av2_S_profil' => $profil,
        ];
        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 8.2	Ajouter un profil d’habilitation à un utilisateur (service : ws_profile_add)
    public function ws_profile_add($cn, $profil, $date_debut = null, $date_fin = null)
    {
        if(!$date_debut)
        {
            $date_debut = Carbon::now()->format('Ymd');
        }
        if(!$date_fin)
        {
            $date_fin = Carbon::now()->addYears(100)->format('Ymd');
        }
        $client = new Client();
        $endpoint = $this->api_url . "ws_profile_add";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $this->jeton,
            'av2_S_cn' => $cn,
            'av2_S_profil' => $profil,
            'av2_S_profil_deb' => $date_debut,
            'av2_S_profil_fin' => $date_fin,
        ];
        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 8.3	Retrait d’un profil à un utilisateur (service : ws_profile_rem)
    public function ws_profile_rem($cn, $profil)
    {
        $client = new Client();
        $endpoint = $this->api_url . "ws_profile_rem";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $this->jeton,
            'av2_S_cn' => $cn,
            'av2_S_profil' => $profil,
        ];
        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 8.4	Suppression de profil (service : ws_profile_del)
    public function ws_profile_del($profil)
    {
        $client = new Client();
        $endpoint = $this->api_url . "ws_profile_del";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $this->jeton,
            'av2_S_profil' => $profil,
        ];
        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 8.5	Lire les profils d’habilitation d’un utilisateur (service : ws_id_profile_read)
    public function ws_id_profile_read($cn)
    {
        $client = new Client();
        $endpoint = $this->api_url . "ws_id_profile_read";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $this->jeton,
            'av2_S_cn' => $cn,
        ];
        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 8.6	Listing des profils d’une application (service : ws_profile_read)
    public function ws_profile_read()
    {
        $client = new Client();
        $endpoint = $this->api_url . "ws_profile_read";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $this->jeton,
        ];
        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 8.7	Délégation de profils (service : ws_profile_delegate)
    public function ws_profile_delegate($nna)
    {
        $client = new Client();
        $endpoint = $this->api_url . "ws_profile_delegate";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $this->jeton,
            'av2_S_NNA2' => $nna,
        ];
        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 8.8	Listing des délégations de profils accordées (service : ws_profile_delegation_read)
    public function ws_profile_delegation_read()
    {
        $client = new Client();
        $endpoint = $this->api_url . "ws_profile_delegation_read";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $this->jeton,
        ];
        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 8.9	Suppression d’une délégation de profils accordée (service : ws_profile_delegation_del)
    public function ws_profile_delegation_del($nna)
    {
        $client = new Client();
        $endpoint = $this->api_url . "ws_profile_delegation_del";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $this->jeton,
            'av2_S_NNA2' => $nna,
        ];
        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 9.1	Ajout d’une information personnalisée (service : ws_custom_info_add)
    public function ws_custom_info_add($cn, $information)
    {
        $client = new Client();
        $endpoint = $this->api_url . "ws_custom_info_add";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $this->jeton,
            'av2_S_cn' => $cn,
            'av2_S_information' => $information,
        ];
        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 9.2	Suppression d’une information personnalisée (service : ws_custom_info_del)
    public function ws_custom_info_del($cn, $information)
    {
        $client = new Client();
        $endpoint = $this->api_url . "ws_custom_info_del";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $this->jeton,
            'av2_S_cn' => $cn,
            'av2_S_information' => $information,
        ];
        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 9.3	Lecture d’information personnalisée (service : ws_custom_info_read)
    public function ws_custom_info_read($cn)
    {
        $client = new Client();
        $endpoint = $this->api_url . "ws_custom_info_read";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $this->jeton,
            'av2_S_cn' => $cn,
        ];
        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 9.4	Délégation de droit en lecture à une application sur une information personnalisée d’une autre application (service : ws_custom_info_delegate)
    public function ws_custom_info_delegate($nna)
    {
        $client = new Client();
        $endpoint = $this->api_url . "ws_custom_info_delegate";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $this->jeton,
            'av2_S_NNA2' => $nna,
        ];
        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 9.5	Listing des délégations accordées sur les informations personnalisées (service : ws_custom_info_delegation_read)
    public function ws_custom_info_delegation_read()
    {
        $client = new Client();
        $endpoint = $this->api_url . "ws_custom_info_delegation_read";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $this->jeton,
        ];
        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 9.6	Suppression d’une délégation accordée à une application sur les informations personnalisées (service : ws_custom_info_delegation_del)
    public function ws_custom_info_delegation_del($nna)
    {
        $client = new Client();
        $endpoint = $this->api_url . "ws_custom_info_delegation_del";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $this->jeton,
            'av2_S_NNA2' => $nna,
        ];
        $response = $client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $this->cert,
                'ssl_key' => $this->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }
}
