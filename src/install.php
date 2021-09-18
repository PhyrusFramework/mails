<?php

if (Config::get('development_mode')) {

    if (Config::get('templates') == null) {

        Config::save('templates', [
            'mail' => '/mail'
        ]);

    } else {

        $t = Config::get('templates');
        $t['mail'] = '/mail';
        Config::save('templates', $t);

    }

}