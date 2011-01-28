<?php

class Sailthru_Client {
    private $api_key;
    private $secret;
    private $api_uri = 'https://api.sailthru.com';
    private $http_request_type;

    protected $user_agent_string;


    /**
     * Instantiate a new client; constructor optionally takes overrides for api_uri.
     *
     * @param string $api_key
     * @param string $secret
     * @param string $api_uri
     * @return Sailthru_Client
     */
    public function  __construct($api_key, $secret, $api_uri = false) {
        $this->api_key = $api_key;
        $this->secret = $secret;
        if ($api_uri !== false) {
            $this->api_uri = $api_uri;
        }

        $this->http_request_type = function_exists('curl_init') ? 'httpRequestCurl' : 'httpRequestWithoutCurl';
        $this->user_agent_string = "Sailthru API PHP5 Client PHP Version: " . phpversion() ;
    }


    /**
     * Remotely send an email template to a single email address.
     *
     * If you pass the $schedule_time parameter, the send will be scheduled for a future time.
     *
     * Options:
     *   replyto: override Reply-To header
     *   test: send as test email (subject line will be marked, will not count towards stats)
     *
     * @param string $template_name
     * @param string $email
     * @param array $vars
     * @param array $options
     * @param string $schedule_time
     * @return array
     */
    public function send($template, $email, $vars = array(), $options = array(), $schedule_time = null) {
        $post = array();
        $post['template'] = $template;
        $post['email'] = $email;
        $post['vars'] = $vars;
        $post['options'] = $options;
        if ($schedule_time) {
            $post['schedule_time'] = $schedule_time;
        }
        $result = $this->apiPost('send', $post);
        return $result;
    }

    /**
     * Remotely send an email template to multiple email addresses.
     *
     * Use the evars parameter to set replacement vars for a particular email address.
     *
     * @param string $template_name
     * @param array $emails
     * @param array $vars
     * @param array $evars
     * @param array $options
     * @return array
     */
    public function multisend($template_name, $emails, $vars = array(), $evars = array(), $options = array()) {
        $post['template'] = $template_name;
        $post['email'] = is_array($emails) ? implode(',', $emails) : $emails;
        $post['vars'] = $vars;
        $post['evars'] = $evars;
        $post['options'] = $options;
        $result = $this->apiPost('send', $post);
        return $result;
    }


    /**
     * Get the status of a send.
     *
     * @param string $send_id
     * @return array
     */
    public function getSend($send_id) {
        return $this->apiGet('send', array('send_id' => $send_id));
    }


    /**
     * Cancel a send that was scheduled for a future time.
     *
     * @param string $send_id
     * @return array
     */
    public function cancelSend($send_id) {
        return $this->apiPost('send', array('send_id' => $send_id), 'DELETE');
    }


    /**
     * Return information about an email address, including replacement vars and lists.
     *
     * @param string $email
     * @return array
     */
    public function getEmail($email) {
        return $this->apiGet('email', array('email' => $email));
    }


    /**
     * Set replacement vars and/or list subscriptions for an email address.
     *
     * $lists should be an assoc array mapping list name => 1 for subscribed, 0 for unsubscribed
     *
     * @param string $email
     * @param array $vars
     * @param array $lists

     * @param array $templates
     * @param integer $verified
     * @param string $optout
     * @param string $send
     * @param array $send_vars
     * @return array
     */
    public function setEmail($email, $vars = array(), $lists = array(), $templates = array(), $verified = 0, $optout = null, $send = null, $send_vars = array()) {
        $data = array('email' => $email);
        if ($vars) {
            $data['vars'] = $vars;
        }
        if ($lists) {
            $data['lists'] = $lists;
        }
        if ($templates) {
            $data['templates'] = $templates;
        }
        $data['verified'] = (int)$verified;
        if ($optout !== null)   {
            $data['optout'] = $optout;
        }
        if ($send !== null) {
            $data['send'] = $send;
        }
        if (!empty($send_vars)) {
            $data['send_vars'] = $send_vars;
        }

        return $this->apiPost('email', $data);
    }


    /**
     * Schedule a mass mail blast
     *
     * @param string $name
     * @param string $list
     * @param string $schedule_time
     * @param string $from_name
     * @param string $from_email
     * @param string $subject
     * @param string $content_html
     * @param string $content_text
     * @param array $options
     * @return array
     */
    public function scheduleBlast($name,
            $list,
            $schedule_time,
            $from_name,
            $from_email,
            $subject,
            $content_html,
            $content_text,
            $options = array()) {

        $data = $options;
        $data['name'] = $name;
        $data['list'] = $list;
        $data['schedule_time'] = $schedule_time;
        $data['from_name'] = $from_name;
        $data['from_email'] = $from_email;
        $data['subject'] = $subject;
        $data['content_html'] = $content_html;
        $data['content_text'] = $content_text;

        return $this->apiPost('blast', $data);
    }

    /**
     *
     * @param string/integer $blast_id
     * @param string $name
     * @param string $list
     * @param string $schedule_time
     * @param string $from_name
     * @param string $from_email
     * @param string $subject
     * @param string $content_html
     * @param string $content_text
     * @param array $options
     * @return <type>
     */
    public function updateBlast($blast_id,
            $name,
            $list,
            $schedule_time,
            $from_name,
            $from_email,
            $subject,
            $content_html,
            $content_text,
            $options = array()) {

        $data = $options;
        $data['blast_id'] = $blast_id;
        $data['name'] = $name;
        $data['list'] = $list;
        $data['schedule_time'] = $schedule_time;
        $data['from_name'] = $from_name;
        $data['from_email'] = $from_email;
        $data['subject'] = $subject;
        $data['content_html'] = $content_html;
        $data['content_text'] = $content_text;

        return $this->apiPost('blast', $data);
    }


    /**
     * Get Blast information
     * @param string/integer $blast_id
     * @return array response from server
     */
    public function getBlast($blast_id) {
        return $this->apiGet('blast', array('blast_id' => $blast_id));
    }


    /**
     * Delete Blast
     * @param ineteger/string $blast_id
     * @return array response from server
     */
    public function deleteBlast($blast_id) {
        return $this->apiDelete('blast', array('blast_id' => $blast_id));
    }


    /**
     * Cancel scheduled Blast
     * @param ineteger/string $blast_id
     * @return array response from server
     */
    public function cancelBlast($blast_id) {
        $data = array(
            'blast_id' => $blast_id,
            'schedule_time' => ''
        );
        return $this->apiPost('blast', $data);
    }

    /**
     * Perform an HTTP request using the curl extension
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return string
     */
    private function httpRequestCurl($url, $data, $method = 'POST') {
        $ch = curl_init();
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
        } else {
            $url .= '?' . http_build_query($data, '', '&');
            if ($method != 'GET') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("User-Agent: {$this->user_agent_string}"));
        $data = curl_exec($ch);
        if (!$data) {
            throw new Sailthru_Client_Exception("Bad response received from $url");
        }
        return $data;
    }


    /**
     * Adapted from: http://netevil.org/blog/2006/nov/http-post-from-php-without-curl
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return string
     */
    private function httpRequestWithoutCurl($url, $data, $method = 'POST') {
        $params = array('http' => array('method' => $method));
        if ($method == 'POST') {
            $params['http']['content'] = is_array($data) ? http_build_query($data, '', '&') : $data;
        } else {
            $url .= '?' . http_build_query($data, '', '&');
        }
        $params['http']['header'] = "User-Agent: {$this->user_agent_string}\nContent-Type: application/x-www-form-urlencoded";
        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', false, $ctx);
        if (!$fp) {
            throw new Sailthru_Client_Exception("Unable to open stream: $url");
        }
        $response = @stream_get_contents($fp);
        if ($response === false) {
            throw new Sailthru_Client_Exception("No response received from stream: $url");
        }
        return $response;
    }


    /**
     * Perform an HTTP request, checking for curl extension support
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return string
     */
    protected function httpRequest($url, $data, $method = 'POST') {
        return $this->{$this->http_request_type}($url, $data, $method);
    }

    /**
     * Perform an API POST (or other) request, using the shared-secret auth hash.
     *
     * @param array $data
     * @return array
     */
    protected  function apiPost($action, $data, $method = 'POST') {
        $data['api_key'] = $this->api_key;
        $data['format'] = isset($data['format']) ? $data['format'] : 'php';
        $data['sig'] = Sailthru_Util::getSignatureHash($data, $this->secret);
        $result = $this->httpRequest("$this->api_uri/$action", $data, $method);
        $unserialized = @unserialize($result);
        return $unserialized ? $unserialized : $result;
    }


    /**
     * Perform an API GET request, using the shared-secret auth hash.
     *
     * @param string $action
     * @param array $data
     * @return array
     */
    protected  function apiGet($action, $data) {
        $data['api_key'] = $this->api_key;
        $data['format'] = isset($data['format']) ? $data['format'] : 'php';
        $data['sig'] = Sailthru_Util::getSignatureHash($data, $this->secret);
        $result = $this->httpRequest("$this->api_uri/$action", $data, 'GET');
        $unserialized = @unserialize($result);
        return $unserialized ? $unserialized : $result;
    }


     /**
     * Perform an API DELETE request, using the shared-secret auth hash.
     *
     * @param string $action
     * @param array $data
     * @return array

     */
    protected function apiDelete($action, $data) {
        return $this->apiPost($action, $data, 'DELETE');
    }

}
?>
