<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
        $check = Config::get('mails.templates');
        if (empty($check)) {
            include(__DIR__ . '/install.php');
            return Config::get('templates.mails');
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

        $smtpDefaults = [
            'host' => '',
            'auth' => true,
            'username' => '',
            'password' => '',
            'secure' => 'tls',
            'port' => 587,
            'debug' => 2
        ];

        if (!empty($ops['smtp'])) {
            $ops['smtp'] = arr($ops['smtp'])->force($smtpDefaults);
        } else {
            $smpt = Config::get('mails.smtp');
            if ($smtp != null) {
                $ops['smtp'] = arr($smtp)->force($smtpDefaults);
            }
        }

        foreach($ops as $k => $v) {
            $this->{$k} = $v;
        }

    }

    /**
     * Send email.
     * 
     * @return Promise
     */
    public function send() : Promise {

        return new Promise(function($resolve, $reject) {

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
                    $mail->setFrom($this->from[0], $this->from[1]);
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

                if ($mail->send()) {
                    $resolve();
                } else {
                    $reject('mail could not be send');
                }
    
            } catch (Exception $e) {
                $reject($e->getMessage());
            }
    

        });

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
        $this->{'content'} = Text::instance($content)->replacer('{{', '}}', function($param) use ($variables) {
            return $variables[$param] ?? $param;
        });
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