<?php

require_once 'colander.php';

mkBolilerplate();

$data_in = [
    'name' => 'This is my name',
    'number_of_pets' => 12
];

try {
    $data = call(
        map([
            'name' => seq([fIsString(), fMaxLen(6)]),
            'number_of_pets' => seq([fIsInt(), fMaxNum(5)])
        ]),
        $data_in
    );

    var_dump($data);
} catch (Exception $e) {
    print $e->getMessage();
}
