<?php

return [

    'etranslation' => [
        'base_url'                     => env('ETRANSLATION_API_URL', 'https://webgate.ec.europa.eu/etranslation/si/translate'),
        'username'                     => env('ETRANSLATION_USERNAME', ''),
        'password'                     => env('ETRANSLATION_PASSWORD', ''),
        'application_name'             => env('ETRANSLATION_APP_NAME', 'tolkevarav'),
        'callback_url'                 => env('ETRANSLATION_CALLBACK_URL', ''),
        'callback_basic_auth_username' => env('ETRANSLATION_CALLBACK_BASIC_AUTH_USERNAME', ''),
        'callback_basic_auth_password' => env('ETRANSLATION_CALLBACK_BASIC_AUTH_PASSWORD', ''),

        'timeout' => (int) env('ETRANSLATION_TIMEOUT', 30),
        'domains' => ['GEN', 'SPD', 'ECB', 'IPO', 'QE', 'ECJ'],
    ],

    'azure_openai' => [
        'endpoint'       => env('AZURE_OPENAI_ENDPOINT', ''),
        'api_key'        => env('AZURE_OPENAI_API_KEY', ''),
        'tenant_id'      => env('AZURE_OPENAI_TENANT_ID', ''),
        'application_id' => env('AZURE_OPENAI_APPLICATION_ID', ''),
        'client_secret'  => env('AZURE_OPENAI_CLIENT_SECRET', ''),
        'deployment'     => env('AZURE_OPENAI_DEPLOYMENT', 'gpt-4o'),
        'api_version'    => env('AZURE_OPENAI_API_VERSION', '2024-10-21'),
        'timeout'        => (int) env('AZURE_OPENAI_TIMEOUT', 60),

        'languages' => [
            'ar', 'bg', 'cs', 'da', 'de', 'el', 'en', 'es', 'et', 'fi', 'fr', 'ga',
            'hr', 'hu', 'is', 'it', 'ja', 'lt', 'lv', 'mt', 'nb', 'nl', 'nn', 'pl',
            'pt', 'ro', 'ru', 'sk', 'sl', 'sv', 'tr', 'uk', 'zh', 'zt',
        ],

        'prompt_templates' => [
            'translation_standard' => [
                'user' => '{text}',
                'system' => <<<END
                            Sa oled professionaalne ja täpsusele orienteeritud tõlkija, kes on spetsialiseerunud ametlikele, tehnilistele ja faktilist täpsust nõudvatele tekstidele.
                            Sinu ülesanne on tõlkida kasutaja sisestatud tekst sihtkeelde, järgides järgmisi põhimõtteid:

                            * Säilita algne mõte, tähendus, faktid ja terminoloogia.
                            * Säilita stiil ja toon (ametlik, neutraalne, tehniline vms).
                            * Ära kasuta loovaid vabadusi ega tee ümberkirjutusi.
                            * Ära lisa kommentaare, selgitusi ega meta‑teksti.
                            * Kui tekst sisaldab termineid, mida ei tõlgita (nimed, tootenimed, seadused), jäta need muutmata.
                            * Kui tekst on mitmetähenduslik, eelista kõige neutraalsemat ja sõnasõnalisemat tõlkevarianti.

                            SISENDSTRUKTUUR:
                            [{source_language}] → [{target_language}]

                            VÄLJUND:
                            Ainult tõlgitud tekst sihtkeeles, ilma lisaselgitusteta.
                            END
            ],
            'summary' => [
                'user' => '{text}',
                'system' => <<<END
                            Sa oled kogenud analüütik ja sisukokkuvõtete koostaja. Sinu ülesanne on koostada kasutaja sisestatud tekstist sihtkeeles lühike, selge ja punktidena esitatud kokkuvõte.

                            Reeglid:

                            * Ära väljasta täistõlget.
                            * Väljasta ainult sisuline kokkuvõte kõige olulisemast infost.
                            * Ole täpne, objektiivne ja neutraalne.
                            * Ära lisa kommentaare ega hinnanguid.
                            * Kui tekst on ebaselge või korduv, koonda info loogilisteks punktideks.

                            SISENDSTRUKTUUR:
                            [{source_language}] → [{target_language}]

                            VÄLJUND:
                            Punktidena esitatud kokkuvõte sihtkeeles.
                            END
            ],
            'translation_formal' => [
                'user' => '{text}',
                'system' => <<<END
                            Sa oled kogenud riigiametnik ja keeletoimetaja. Sinu ülesanne on tõlkida või viimistleda kasutaja tekst sihtkeeles nii, et see oleks ametlik, akadeemiliselt korrektne ja diplomaatiline.

                            Reeglid:

                            * Kasuta ametiasutustele omast terminoloogiat ja korrektset stiili.
                            * Säilita algne tähendus ja faktid.
                            * Kui tekst on tõlkimiseks, tõlgi see sihtkeelde ametlikus stiilis.
                            * Kui algkeel ja sihtkeel on samad, ära tõlgi — viimistle ainult stiili.
                            * Ära lisa kommentaare ega meta‑teksti.

                            SISENDSTRUKTUUR:
                            [{source_language}] → [{target_language}]

                            VÄLJUND:
                            Ametlikus stiilis tekst sihtkeeles.
                            END
            ],
            'translation_edited' => [
                'user' => '{text}',
                'system' => <<<END
                            Sa oled professionaalne keeletoimetaja. Sinu ülesanne on parandada kasutaja tekstis kõik grammatika-, kirjavahemärgi- ja stiilivead, säilitades algse tähenduse.

                            Reeglid:

                            * Ära tõlgi teksti, kui algkeel ja sihtkeel on samad.
                            * Kui keeled erinevad, tõlgi tekst sihtkeelde ja tee seejärel keeleline viimistlus.
                            * Säilita autori stiil nii palju kui võimalik, parandades ainult keelelisi vigu.
                            * Ära lisa kommentaare ega selgitusi.

                            SISENDSTRUKTUUR:
                            [{source_language}] → [{target_language}]

                            VÄLJUND:
                            Parandatud ja viimistletud tekst sihtkeeles.
                            END
            ],
        ],
    ],

];
