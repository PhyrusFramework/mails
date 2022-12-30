<?php

if (Config::get('project.development_mode')) {

    if (Config::get('mails') == null) {
        Config::save('mails', [
            'templates' => '/mails',
            'smtp' => null
        ]);
    }

    $path = Path::root() . '/mails';
    if (!file_exists($path)) {
        mkdir($path, 0777);
        $example = "$path/mail_example.html";
        $origin = __DIR__ . '/src/mail_example.html';

        file_put_contents($example, file_get_contents($origin));
    }

}