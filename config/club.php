<?php

// Datos de un club FICTICIO. Esta es una demo: ningún dato pertenece a una institución real.
return [
    'name'          => 'Club Rojo Deportivo',
    'short_name'    => 'CRD',
    'founded'       => 1948,
    'address'       => 'Av. de los Deportes 1948, Buenos Aires',
    'phone'         => '+54 11 0000-0000',
    'whatsapp'      => env('CLUB_WHATSAPP', ''),
    'contact_email' => env('CLUB_CONTACT_EMAIL', 'admin@clubrojo.demo'),
    'instagram'     => env('CLUB_INSTAGRAM', ''),
    'facebook'      => env('CLUB_FACEBOOK', ''),
    'cuit'          => '00-00000000-0',

    // Key for the DNI lookup fingerprint (see App\Support\Dni). Falls back to APP_KEY.
    'dni_key'       => env('DNI_KEY'),

    // Languages offered on the public site and the admin panel.
    'locales'       => ['es', 'en', 'pt'],
];
