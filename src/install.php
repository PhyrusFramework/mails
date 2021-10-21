<?php

if (Config::get('development_mode')) {

    if (Config::get('templates') == null) {

        Config::save('templates', [
            'mails' => '/mails'
        ]);

    } else {

        $t = Config::get('templates');
        $t['mails'] = '/mails';
        Config::save('templates', $t);

    }

    $path = Path::project() . '/mails';
    if (!file_exists($path)) {
        mkdir($path, 0777);
        $example = "$path/mail_example.html";
        $origin = __DIR__ . '/mail_example.html';

        file_put_contents($example, file_get_contents($origin));
    }

}