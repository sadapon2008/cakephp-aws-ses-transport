<?php

App::uses('SmtpTransport', 'Network/Email');

class AwsSesTransport extends SmtpTransport
{
    /** @var Aws\Ses\SesClient */
    protected $ses = null;

    public function config($config = null) {
        if ($config === null) {
            return $this->_config;
        }
        $default = array(
            'region' => 'us-east-1',
            'version' => 'latest',
            'credential_key' => null,
            'credential_secret' => null,
        );
        $this->_config = array_merge($default, $this->_config, $config);
        return $this->_config;
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function generateSes() {
        if($this->ses != null) {
            return;
        }
        $options = array(
            'region' => $this->_config['region'],
            'version' => $this->_config['version'],
        );
        if(($this->_config['credential_key'] != null) && ($this->_config['credential_secret'] != null)) {
            $options['credentials'] = array(
                'key' => $this->_config['credential_key'],
                'secret' => $this->_config['credential_secret'],
            );
        }
        $this->ses = new Aws\Ses\SesClient($options);
    }

    public function destroySes() {
        $this->ses = null;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getSendQuota() {
        $this->_lastResponse = array();
        $this->generateSes();
        $result = $this->ses->getSendQuota();
        $this->_lastResponse = $result;
    }

    /**
     * @param CakeEmail $email
     * @throws \InvalidArgumentException
     */
    public function send(CakeEmail $email) {
        $this->_cakeEmail = $email;
        $this->generateSes();
        $this->_sendData();

        return $this->_content;
    }

    protected function _sendData()
    {
        $this->_lastResponse = array();
        $headers = $this->_headersToString($this->_prepareMessageHeaders());
        $message = $this->_prepareMessage();

        $rawData = $headers . "\r\n\r\n" . $message;

        $options = array(
            'RawMessage' => array(
                'Data' => $rawData,
            ),
        );
        $result = $this->ses->sendRawEmail($options);
        $this->_lastResponse = $result;

        $this->_content = array('headers' => $headers, 'message' => $message);
    }
}
