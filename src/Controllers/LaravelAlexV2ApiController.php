<?php

namespace DaVikingCode\LaravelAlexV2Api\Controllers;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use DaVikingCode\LaravelAlexV2Api\Models\LaravelAlexV2ApiConnector;

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

    public static function checkForProfiles() // add or remove app profiles
    {
        $connector = new LaravelAlexV2ApiConnector();

        // Get all app roles list
        $roles_list = [];
        foreach (Role::all() as $role)
        {
            array_push($roles_list, $role->name);
        }

        // Get all Alex profiles list
        $profiles_list = json_decode(self::ws_profile_read()->data);

        // delete and add profiles
        $profiles_to_add = array_diff($roles_list, $profiles_list);
        $profiles_to_delete = array_diff($profiles_list, $roles_list);
        foreach ($profiles_to_delete as $profile)
        {
            self::ws_profile_del(str_replace($connector->sp_entity_id . '_','', $profile));
        }
        foreach ($profiles_to_add as $profile)
        {
            self::ws_profile_create($profile);
        }

        // Set delegation
        $delegation = self::ws_profile_delegate($connector->sp_entity_id);
        return $delegation;
    }

    // 7.1	Déclaration / Association d’un nouvel utilisateur (service : ws_user_association)
    public static function ws_user_association(string $email, string $nom, string $prenom, array $infos_perso = [], array $profiles = [])
    {

        $connector = new LaravelAlexV2ApiConnector();

        // format profiles
        $profiles_arr = [];
        $now = Carbon::now()->format('Ymd');
        foreach($profiles as $profile)
        {
            array_push($profiles_arr, ['av2_S_profil' =>$profile, 'av2_S_profil_deb' => $now, 'av2_S_profil_fin' => $now]);
        }
        $profiles_arr = json_decode(json_encode($profiles_arr));

        $endpoint = $connector->api_url . "ws_user_association";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $connector->jeton,
            'av2_S_mail' => $email,
            'av2_S_nom' => $nom,
            'av2_S_prenom' => $prenom,
            'av2_S_informations_array' => $infos_perso,
            'av2_S_profils_array' => $profiles_arr,
        ];
        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 7.2	Déclaration du départ d’un utilisateur / dissociation (service : ws_user_dissociation)
    public static function ws_user_dissociation($cn)
    {
        $connector = new LaravelAlexV2ApiConnector();

        $endpoint = $connector->api_url . "ws_user_dissociation";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $connector->jeton,
            'av2_S_cn' => $cn,
        ];
        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 7.3	Modification d’attribut d’un utilisateur (service : ws_id_mod)
    public static function ws_id_mod($user, $email, $nom = null, $prenom = null)
    {
//        $user = User::where('id_interne', '=', $cn)->first(); // test with cn

        $connector = new LaravelAlexV2ApiConnector();

        $endpoint = $connector->api_url . "ws_id_mod";
        $headers = ['Content-Type' => 'application/json'];
        if($user->email != $email) // email changed
        {
            $content = [
                'av2_S_jeton' => $connector->jeton,
                'av2_S_cn' => $user->id_interne,
                'av2_S_mail_new' => $email,
                'av2_S_nom_new' => $nom,
                'av2_S_prenom_new' => $prenom,
            ];
        }
        else // same email
        {
            $content = [
                'av2_S_jeton' => $connector->jeton,
                'av2_S_cn' => $user->id_interne,
                'av2_S_nom_new' => $nom,
                'av2_S_prenom_new' => $prenom,
            ];
        }

        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 7.4	Lecture d’attributs d’un utilisateur (service : ws_id_read)
    public static function ws_id_read($cn)
    {
        $connector = new LaravelAlexV2ApiConnector();

        $endpoint = $connector->api_url . "ws_id_read";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $connector->jeton,
            'av2_S_cn' => $cn,
        ];
        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 7.5	Recherche de comptes utilisateurs par attributs (service : ws_id_search)
    public static function ws_id_search($email, $nom = null, $portail = null, $information = null)
    {
        $connector = new LaravelAlexV2ApiConnector();

        $endpoint = $connector->api_url . "ws_id_search";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $connector->jeton,
            'av2_S_mail' => $email,
            'av2_S_nom' => $nom,
            'av2_S_profil_portail' => $portail,
            'av2_S_information' => $information,
        ];
        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 8.1	Création d’un profil (service : ws_profile_create)
    public static function ws_profile_create($profil)
    {
        $connector = new LaravelAlexV2ApiConnector();

        $endpoint = $connector->api_url . "ws_profile_create";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $connector->jeton,
            'av2_S_profil' => $profil,
        ];
        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 8.2	Ajouter un profil d’habilitation à un utilisateur (service : ws_profile_add)
    public static function ws_profile_add($cn, $profil, $date_debut = null, $date_fin = null)
    {
        $connector = new LaravelAlexV2ApiConnector();

        if(!$date_debut)
        {
            $date_debut = Carbon::now()->format('Ymd');
        }
        if(!$date_fin)
        {
            $date_fin = Carbon::now()->addYears(100)->format('Ymd');
        }

        $endpoint = $connector->api_url . "ws_profile_add";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $connector->jeton,
            'av2_S_cn' => $cn,
            'av2_S_profil' => $profil,
            'av2_S_profil_deb' => $date_debut,
            'av2_S_profil_fin' => $date_fin,
        ];
        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 8.3	Retrait d’un profil à un utilisateur (service : ws_profile_rem)
    public static function ws_profile_rem($cn, $profil)
    {
        $connector = new LaravelAlexV2ApiConnector();

        $endpoint = $connector->api_url . "ws_profile_rem";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $connector->jeton,
            'av2_S_cn' => $cn,
            'av2_S_profil' => $profil,
        ];
        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 8.4	Suppression de profil (service : ws_profile_del)
    public static function ws_profile_del($profil)
    {
        $connector = new LaravelAlexV2ApiConnector();

        $endpoint = $connector->api_url . "ws_profile_del";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $connector->jeton,
            'av2_S_profil' => $profil,
        ];
        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 8.5	Lire les profils d’habilitation d’un utilisateur (service : ws_id_profile_read)
    public static function ws_id_profile_read($cn)
    {
        $connector = new LaravelAlexV2ApiConnector();

        $endpoint = $connector->api_url . "ws_id_profile_read";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $connector->jeton,
            'av2_S_cn' => $cn,
        ];
        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 8.6	Listing des profils d’une application (service : ws_profile_read)
    public static function ws_profile_read()
    {
        $connector = new LaravelAlexV2ApiConnector();

        $endpoint = $connector->api_url . "ws_profile_read";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $connector->jeton,
        ];
        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 8.7	Délégation de profils (service : ws_profile_delegate)
    public static function ws_profile_delegate($nna)
    {
        $connector = new LaravelAlexV2ApiConnector();

        $endpoint = $connector->api_url . "ws_profile_delegate";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $connector->jeton,
            'av2_S_NNA2' => $nna,
        ];
        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 8.8	Listing des délégations de profils accordées (service : ws_profile_delegation_read)
    public static function ws_profile_delegation_read()
    {
        $connector = new LaravelAlexV2ApiConnector();

        $endpoint = $connector->api_url . "ws_profile_delegation_read";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $connector->jeton,
        ];
        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 8.9	Suppression d’une délégation de profils accordée (service : ws_profile_delegation_del)
    public static function ws_profile_delegation_del($nna)
    {
        $connector = new LaravelAlexV2ApiConnector();

        $endpoint = $connector->api_url . "ws_profile_delegation_del";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $connector->jeton,
            'av2_S_NNA2' => $nna,
        ];
        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 9.1	Ajout d’une information personnalisée (service : ws_custom_info_add)
    public static function ws_custom_info_add($cn, $information)
    {
        $connector = new LaravelAlexV2ApiConnector();

        $endpoint = $connector->api_url . "ws_custom_info_add";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $connector->jeton,
            'av2_S_cn' => $cn,
            'av2_S_information' => $information,
        ];
        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 9.2	Suppression d’une information personnalisée (service : ws_custom_info_del)
    public static function ws_custom_info_del($cn, $information)
    {
        $connector = new LaravelAlexV2ApiConnector();

        $endpoint = $connector->api_url . "ws_custom_info_del";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $connector->jeton,
            'av2_S_cn' => $cn,
            'av2_S_information' => $information,
        ];
        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 9.3	Lecture d’information personnalisée (service : ws_custom_info_read)
    public static function ws_custom_info_read($cn)
    {
        $connector = new LaravelAlexV2ApiConnector();

        $endpoint = $connector->api_url . "ws_custom_info_read";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $connector->jeton,
            'av2_S_cn' => $cn,
        ];
        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 9.4	Délégation de droit en lecture à une application sur une information personnalisée d’une autre application (service : ws_custom_info_delegate)
    public static function ws_custom_info_delegate($nna)
    {
        $connector = new LaravelAlexV2ApiConnector();

        $endpoint = $connector->api_url . "ws_custom_info_delegate";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $connector->jeton,
            'av2_S_NNA2' => $nna,
        ];
        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 9.5	Listing des délégations accordées sur les informations personnalisées (service : ws_custom_info_delegation_read)
    public static function ws_custom_info_delegation_read()
    {
        $connector = new LaravelAlexV2ApiConnector();

        $endpoint = $connector->api_url . "ws_custom_info_delegation_read";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $connector->jeton,
        ];
        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }

    // 9.6	Suppression d’une délégation accordée à une application sur les informations personnalisées (service : ws_custom_info_delegation_del)
    public static function ws_custom_info_delegation_del($nna)
    {
        $connector = new LaravelAlexV2ApiConnector();

        $endpoint = $connector->api_url . "ws_custom_info_delegation_del";
        $headers = ['Content-Type' => 'application/json'];
        $content = [
            'av2_S_jeton' => $connector->jeton,
            'av2_S_NNA2' => $nna,
        ];
        $response = $connector->client->post(
            $endpoint, [
                'json' => $content,
                'headers' => $headers,
                'cert' => $connector->cert,
                'ssl_key' => $connector->ssl_key,
            ]
        );
        return json_decode($response->getBody()->getContents());
    }
}
