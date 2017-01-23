<?php

require_once(PATH_CORE . 'external/phpmailer/class.phpmailer.php');

class CMail extends PHPMailer
{
    function __construct()
    {
        $config = config::sys(array(), array(), 'mail', true);

        $this->From = $config['noreply'];
        $this->FromName = $config['fromname'];
        $this->CharSet = 'UTF-8';
        $this->XMailer = ' ';
        $this->Host = '';
        $this->Hostname = SITEHOST;

        $this->isHTML(true);

        $this->isMail();

        switch ($config['method']) {
            case 'sendmail':
            {
                $this->isSendmail();
            }
            break;
            case 'smtp':
            {
                if (empty($config['smtp'])) {
                    break;
                }

                require_once(PATH_CORE . 'external/phpmailer/class.smtp.php');
                $this->isSMTP();
                $this->SMTPKeepAlive = true;
                $config = array_merge(array(
                        'host' => 'localhost',
                        'port' => 25,
                        'user' => '',
                        'pass' => '',
                        'secure' => '',
                    ), $config['smtp']
                );

                if ( ! empty($config['secure'])) {
                    $this->SMTPSecure = strval($config['secure']);
                }

                $this->Host = $config['host'] . ':' . intval($config['port']);
                $this->SMTPAuth = !empty($config['user']);
                if ($this->SMTPAuth) {
                    $this->Username = $config['user'];
                    $this->Password = $config['pass'];
                }

            }
            break;
        }
    }
}
