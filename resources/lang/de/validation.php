<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sprachdatei für Überprüfungen
    |--------------------------------------------------------------------------
    |
    | Die folgenden Zeilen enthalten die Standard-Fehlernachrichten welche
    | in der Überprüfungsklasse verwendet werden. Manche Nachrichten haben
    | mehrere Versionen wie z. B. die Größenregeln. Die Texte dürfen nach
    | Bedarf angepasst werden.
    |
    */

    'accepted'             => 'Attribut :attribute muss akzeptiert werden.',
    'active_url'           => 'Attribut :attribute ist keine valide URL.',
    'after'                => 'Attribut :attribute muss ein Datum nach dem :date sein.',
    'alpha'                => 'Attribut :attribute darf nur Zeichen enthalten.',
    'alpha_dash'           => 'Attribut :attribute darf nur Zeichen, Zahlen und Bindestriche enthalten.',
    'alpha_num'            => 'Attribut :attribute darf nur Zeichen und Zahlen enthalten.',
    'array'                => 'Attribut :attribute muss ein Array sein.',
    'before'               => 'Attribut :attribute muss ein Datum vor dem :date sein.',
    'between'              => [
        'numeric' => 'Attribut :attribute muss zwischen :min und :max liegen.',
        'file'    => 'Attribut :attribute muss zwischen :min und :max Kilobyte liegen.',
        'string'  => 'Attribut :attribute muss zwischen :min und :max Zeichen liegen.',
        'array'   => 'Attribut :attribute muss zwischen :min und :max Anzahl an Inhalten liegen.',
    ],
    'boolean'              => 'Attribut :attribute muss true oder false sein.',
    'confirmed'            => 'Bestätigung für Attribut :attribute stimmt nicht überein.',
    'date'                 => 'Attribut :attribute ist kein valides Datum.',
    'date_format'          => 'Attribut :attribute stimmt nicht mit folgendem Format überein: :format.',
    'different'            => 'Attribut :attribute und :other muss unterschiedlich sein.',
    'digits'               => 'Attribut :attribute muss :digits Zahlen lang sein.',
    'digits_between'       => 'Attribut :attribute muss zwischen :min und :max Zahlen liegen.',
    'email'                => 'Attribut :attribute muss eine valide E-Mail Adresse sein.',
    'exists'               => 'Ausgewähltes Attribut :attribute ist invalid.',
    'filled'               => 'Das Feld für Attribut :attribute fehlt.',
    'image'                => 'Attribut :attribute muss ein Bild sein.',
    'in'                   => 'Das ausgewählte Attribut :attribute ist invalid.',
    'integer'              => 'Attribut :attribute muss ein Integer sein.',
    'ip'                   => 'Attribut :attribute muss eine valide IP-Adresse sein.',
    'json'                 => 'Attribut :attribute muss ein valider JSON-String sein.',
    'max'                  => [
        'numeric' => 'Attribut :attribute darf nicht größer als :max sein.',
        'file'    => 'Attribut :attribute darf nicht größer als :max Kilobyte sein.',
        'string'  => 'Attribut :attribute darf nicht länger als :max Zeichen sein.',
        'array'   => 'Attribut :attribute darf nicht mehr als :max Inhalte haben.',
    ],
    'mimes'                => 'Attribut :attribute muss folgenden Dateityp haben: :values.',
    'min'                  => [
        'numeric' => 'Attribut :attribute muss mindestens :min sein.',
        'file'    => 'Attribut :attribute muss mindestens :min Kilobyte groß sein.',
        'string'  => 'Attribut :attribute muss mindestens :min Zeichen lang sein.',
        'array'   => 'Attribut :attribute muss mindestens :min Inhalte enthalten.',
    ],
    'not_in'               => 'Das ausgewählte Attribut :attribute ist invalid.',
    'numeric'              => 'Attribut :attribute muss eine Zahl sein.',
    'regex'                => 'Das Format vom Attribut :attribute ist falsch.',
    'required'             => 'Das Feld für Attribut :attribute wird benötigt.',
    'required_if'          => 'Das Feld für Attribut :attribute wird benötigt wenn :other = :value.',
    'required_unless'      => 'Das Feld für Attribut :attribute wird benötigt außer :other befindet sich in :values.',
    'required_with'        => 'Das Feld für Attribut :attribute wird benötigt wenn der Wert :values existiert.',
    'required_with_all'    => 'Das Feld für Attribut :attribute wird benötigt wenn der Wert :values existiert.',
    'required_without'     => 'Das Feld für Attribut :attribute wird benötigt wenn der Wert :values nicht existiert.',
    'required_without_all' => 'Das Feld für Attribut :attribute wird benötigt wenn keine der Werte existieren: :values',
    'same'                 => 'Attribut :attribute und :other müssen übereinstimmen.',
    'size'                 => [
        'numeric' => 'Attribut :attribute muss :size sein.',
        'file'    => 'Attribut :attribute muss :size Kilobyte sein.',
        'string'  => 'Attribut :attribute muss insgesamt :size Zeichen lang sein.',
        'array'   => 'Attribut :attribute muss :size Inhalte enthalten.',
    ],
    'string'               => 'Attribut :attribute muss ein String sein.',
    'timezone'             => 'Attribut :attribute muss eine valide Zone sein.',
    'unique'               => 'Attribut :attribute wurde bereits verwendet.',
    'url'                  => 'Das Format vom Attribut :attribute ist falsch.',

    /*
    |--------------------------------------------------------------------------
    | Sprachdatei für spezielle Überprüfungen
    |--------------------------------------------------------------------------
    |
    | Hier können spezielle Nachrichten für Attribute definiert werden. Die Syntax
    | der Benennung muss "attribute.rule" sein. Dies macht es leichter eine
    | eigene spezielle Übersetzung für ein bestehendes Attribut zu definieren.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Spezielle Attribute für Überprüfungen
    |--------------------------------------------------------------------------
    |
    | Die folgenden Zeilen werden verwendet um Attribut-Platzhalter mit etwas
    | mehr leserfreundlicherem wie z.B. "E-Mail-Adresse" anstatt "email" zu ersetzen.
    | Dadurch können wir Nachrichten etwas besser gestalten.
    |
    */

    'attributes' => [],

];
