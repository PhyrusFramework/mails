<?php

if (Config::get('development_mode')) {

    if (Config::get('mails') == null) {
        Config::save('mails', [
            'templates' => '/mails',
            'smtp' => null
        ]);
    }

    $path = Path::project() . '/mails';
    if (!file_exists($path)) {
        mkdir($path, 0777);
        $example = "$path/mail_example.html";
        $origin = __DIR__ . '/mail_example.html';

        file_put_contents($example, file_get_contents($origin));
    }

}