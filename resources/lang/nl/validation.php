<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | following language lines contain default error messages used by
    | validator class. Some of these rules have multiple versions such
    | as size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'             => ':attribute moet geaccepteerd worden.',
    'active_url'           => ':attribute is geen valide URL.',
    'after'                => ':attribute moet een datum na :date zijn.',
    'alpha'                => ':attribute mag alleen letters bevatten.',
    'alpha_dash'           => ':attribute mag alleen letters, nummers en dashes bevatten.',
    'alpha_num'            => ':attribute mag alleen letters en nummers bevatten.',
    'array'                => ':attribute moet een array zijn.',
    'before'               => ':attribute moet een datum voor :date zijn.',
    'between'              => [
        'numeric' => ':attribute moet tussen :min en :max liggen.',
        'file'    => ':attribute moet tussen :min en :max kilobytes zijn.',
        'string'  => ':attribute moet tussen :min en :max karakters hebben.',
        'array'   => ':attribute moet tussen de :min en :max items hebben.',
    ],
    'boolean'              => ':attribute moet true of false zijn.',
    'confirmed'            => ':attribute confirmatie matched niet.',
    'date'                 => ':attribute is geen valide datum.',
    'date_format'          => ':attribute komt niet overeen met het format :format.',
    'different'            => ':attribute en :other moeten verschillend zijn.',
    'digits'               => ':attribute moet :digits digits zijn.',
    'digits_between'       => ':attribute moeten tussen :min en :max digits zijn.',
    'email'                => ':attribute moet een valide email zijn.',
    'exists'               => ':attribute is invalide.',
    'filled'               => ':attribute veld is verplicht.',
    'image'                => ':attribute moet een image zijn.',
    'in'                   => ':attribute is invalide.',
    'integer'              => ':attribute moet een integer zijn.',
    'ip'                   => ':attribute moet een valide ip adres zijn.',
    'json'                 => ':attribute moet een valide JSON string zijn.',
    'max'                  => [
        'numeric' => ':attribute mag niet groter zijn dan :max.',
        'file'    => ':attribute mag niet groter zijn dan :max kilobytes.',
        'string'  => ':attribute mag niet groter zijn dan :max characters.',
        'array'   => ':attribute mag niet meer dan :max items hebben.',
    ],
    'mimes'                => ':attribute moet een bestand zijn van het type: :values.',
    'min'                  => [
        'numeric' => ':attribute mag niet kleiner zijn dan :min.',
        'file'    => ':attribute mag niet kleiner zijn dan :min kilobytes.',
        'string'  => ':attribute mag niet kleiner zijn dan :min characters.',
        'array'   => ':attribute mag niet minder dan :min items hebben.',
    ],
    'not_in'               => ':attribute is invalide.',
    'numeric'              => ':attribute moet een nummer zijn.',
    'regex'                => ':attribute format is invalide.',
    'required'             => ':attribute veld is verplicht.',
    'required_if'          => ':attribute veld is verplicht als :other :value is.',
    'required_unless'      => ':attribute veld is verplicht tenzij :other :values is.',
    'required_with'        => ':attribute veld is verplicht als :values aanwezig is.',
    'required_with_all'    => ':attribute veld is verplicht als :values aanwezig is.',
    'required_without'     => ':attribute veld is verplicht als :values niet aanwezig is.',
    'required_without_all' => ':attribute veld is verplicht als er geen :values aanwezig zijn.',
    'same'                 => ':attribute en :other moeten overeenkomen.',
    'size'                 => [
        'numeric' => ':attribute moet deze size hebben :size.',
        'file'    => ':attribute moet :size kilobytes zijn.',
        'string'  => ':attribute moet :size characters zijn.',
        'array'   => ':attribute moet :size items bevatten.',
    ],
    'string'               => ':attribute moet een string zijn.',
    'timezone'             => ':attribute moet een valide zone zijn.',
    'unique'               => ':attribute is niet uniek.',
    'url'                  => ':attribute format is invalide.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [],

];
