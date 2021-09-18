<?php

class Mail {

    /**
     * Receivers of the email.
     * 
     * @var array $receivers
     */
    private array $receivers = [];

    /**
     * Callback when email can't be sent.
     * 
     * @var callable $onError
     */
    public static $onError;

    /**
     * Get the directory where templates are located.
     * 
     * @return string
     */
    private static function templatesDir() : string {
        $check = Config::get('templates.mails');
        if (empty($check)) {
            Config::save('templates.mails', '/mails');
            Folder::instance(Path::project() . '/mails')->create();
            return '/mails';
        }
        return $check;
    }

    function __construct($options = []) {

        $ops = arr($options)->force([
            'exceptions' => true,
            'smtp' => null,
            'from' => null,
            'receivers' => [],
            'html' => true,
            'subject' => '',
            'content' => '',
            'charset' => 'UTF-8'
        ]);
        ;
        if (!empty($ops['smtp'])) {

            $ops['smtp'] = arr($ops['smtp'])->force([
                'host' => '',
                'auth' => true,
                'username' => '',
                'password' => '',
                'secure' => 'tls',
                'port' => 587,
                'debug' => 2
            ]);
        }

        foreach($ops as $k => $v) {
            $this->{$k} = $v;
        }

    }

    /**
     * Send email.
     */
    public function send() {

        $mail = new PHPMailer($this->exceptions);

        try {
            if (is_array($this->smtp)) {

                $mail->isSMTP();
                $mail->SMTPDebug = $this->smtp['debug'];
                $mail->Host       = $this->smtp['host'];
                $mail->SMTPAuth   = $this->smtp['auth'];
                $mail->Username   = $this->smtp['username'];
                $mail->Password   = $this->smtp['password'];
                $mail->SMTPSecure = $this->smtp['secure'];
                $mail->Port       = $this->smtp['port'];
            }

            if (is_array($this->from)) {
                foreach($this->from as $k => $v) {
                    $mail->setFrom($v, $k);
                }
            } else {
                $mail->setFrom($this->from);
            }

            if (isset($this->receivers)) {
                foreach($this->receivers as $receiver) {
                    if (is_string($receiver)) {
                        $mail->addAddress($receiver);
                    } else if (is_array($receiver)){
                        $addr = $receiver['address'];
                        $name = $receiver['name'];
                        $mail->addAddress($addr, $name);
                    }
                }
            }

            // Content
            if ($this->html)
                $mail->isHTML(true);                                  // Set email format to HTML
            $mail->CharSet = $this->charset;
            $mail->Subject = $this->subject;
            $mail->Body    = $this->content;

            $mail->send();
        } catch (Exception $e) {
            if (self::$onError != null) {
                self::$onError($e);
            }
        }

    }

    /**
     * Load a template.
     * 
     * @param string $name Template file name.
     * @param array $variables
     */
    public function loadTemplate(string $name, array $variables = []) {

        $path = Path::project() . self::templatesDir();
        if (!is_dir($path)) return;

        $file = "$path/$name";
        if (!file_exists($file)) return;

        $content = file_get_contents($file);
        $this->{'content'} = $this->generateContent($variables);
    }

    /**
     * Places variables into string.
     * 
     * @param string $content
     * @param array $variables
     */
    private function generateContent(string $content, array $variables = []) {
        $aux = '';
        $in = false;
        $last = '';
        $current = '';
        for($i = 0; $i<strlen($content); ++$i) {

            $ch = $content[$i];

            if ($current == '{' && $last == '{') {
                $in = true;
                $last = '';
            }
            else if ($current == '}' && $last == '}') {
                $in = false;

                $tr = trim($current);
                if (isset($variables[$tr]))
                    $aux .= $variables[$tr];
                else
                    $aux .= "{{$current}}";

                $current = false;
                $last = '';
            }
            else if ($in) {
                $current .= $last;
                $last = $ch;
            }
            else {
                $aux .= $last;
                $last = $ch;
            }

        }
        $aux .= $last;

        return $aux;
    }

    /**
     * Get the receivers of this mail.
     * 
     * @return array
     */
    public function getReceivers() : array {
        return $this->receivers;
    }

    /**
     * Set the receivers of this mail.
     * 
     * @param array ...$receivers
     */
    public function setReceivers(...$receivers) {

        $this->receivers = [];

        $this->addReceivers($receivers);
    }

    /**
     * Add receivers to the list.
     * 
     * @param array ...$receivers
     */
    public function addReceivers(...$receivers) {

        foreach($receivers as $receiver) {

            if (is_string($receiver)) {
                $this->addReceiver($receiver);
            }
            else if (is_array($receiver)) {

                foreach($receiver as $r) {
                    if (is_string($r))
                        $this->addReceiver($r);
                }
            }

        }

    }

    /**
     * Add a single receiver.
     * 
     * @param mixed $receiver
     */
    public function addReceiver($receiver) {
        if (!is_string($receiver)) return;

        if (strpos($receiver, ',')) {
            $all = explode(',', $receiver);
            foreach($all as $e) {
                $this->receivers[] = str_replace(' ', '', $e);
            }
        } else {
            $this->receivers[] = $receiver;
        }
    }

}