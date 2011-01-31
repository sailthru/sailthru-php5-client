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
     * updates existing blast
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
     * @return array response from server
     */
    public function updateBlast($blast_id,
            $name = null,
            $list = null,
            $schedule_time = null,
            $from_name = null,
            $from_email = null,
            $subject = null,
            $content_html = null,
            $content_text = null,
            $options = array()) {
        $data = $options;
        $data['blast_id'] = $blast_id;
        if (!is_null($name)) {
            $data['name'] = $name;
        }
        if (!is_null($list)) {
            $data['list'] = $list;
        }
        if (!is_null($schedule_time)) {
            $data['schedule_time'] = $schedule_time;
        }
        if (!is_null($from_name))  {
            $data['from_name'] = $from_name;
        }
        if (!is_null($from_email)) {
            $data['from_email'] = $from_email;
        }
        if (!is_null($subject)) {
            $data['subject'] = $subject;
        }
        if (!is_null($content_html)) {
            $data['content_html'] = $content_html;
        }
        if (!is_null($content_text)) {
            $data['content_text'] = $content_text;
        }

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
     * Get a template.
     *
     * @param string $template_name
     * @return array
     */
    function getTemplate($template_name) {
        return $this->apiGet('template', array('template' => $template_name));
    }

    /**
     * Save a template.
     *
     * @param string $template_name
     * @param array $template_fields
     * @return array
     */
    public function saveTemplate($template_name, $template_fields = array()) {
        $data = $template_fields;
        $data['template'] = $template_name;
        return $this->apiPost('template', $data);
    }



    /**
     * Download a list. Obviously, this can potentially be a very large download.
     * @param String $list
     * @param String $format
     * @return txt | json | xml
     */
    public function getList($list, $format = "txt") {
        $data = array(
            'list' => $list,
            'format' => $format
        );
        return $this->apiGet('list', $data);
    }


    /**
     * Create a list
     * @param String $list
     * @param String $emails
     */
    public function saveList($list, $emails) {
        $data = array(
            'list' => $list,
            'emails' => $emails
        );
        return $this->apiPost('list', $data);
    }


    /**
     * Deletes a list
     * @param String $list
     * @return response from server
     */
    public function deleteList($list) {
        $data = array(
            'list' => $list
        );
        return $this->apiDelete('list', $data);
    }


    /**
     * import contacts 
     * @param String $email
     * @param String $password
     * @param boolean $include_names
     */
    public function importContacts($email, $password, $include_names = true) {
        $data = array(
            'email' => $email,
            'password' => $password
        );
        if ($include_names === true) {
            $data['names'] = 1;
        }
        return $this->apiPost('contacts', $data);
    }


    /**
     * Push a new piece of content to Sailthru, triggering any applicable alerts.
     * @link http://docs.sailthru.com/api/content
     * @param String $title
     * @param String $url
     * @param array $options
     */
    public function pushContent($title, $url, $options = array()) {
        $data = $options;
        $data['title'] = $title;
        $data['url'] = $url;
        return $this->apiPost('content', $data);
    }


    /**
     * Retrieve a user's alert settings.
     * @link http://docs.sailthru.com/api/alert
     * @param String $email
     */
    public function getAlert($email) {
        $data = array(
            'email' => $email
        );
        return $this->apiGet('alert', $data);
    }


    /**
     * Add a new alert to a user.
     * @link http://docs.sailthru.com/api/alert
     * @param String $email
     * @param String $type
     * @param String $template
     * @param String $when
     * @param array $options
     */
    public function saveAlert($email, $type, $template, $when = null, $options = array()) {
        $data = $options;
        $data['email'] = $email;
        $data['type'] = $type;
        $data['template'] = $template;
        if ($type == 'weekly' || $type == 'daily') {
            $data['when'] = $when;
        }
        return $this->apiPost('alert', $data);
    }


    /**
     * Remove an alert from a user's settings.
     * @link http://docs.sailthru.com/api/alert
     * @param <type> $email
     * @param <type> $alert_id
     */
    public function deleteAlert($email, $alert_id) {
        $data = array(
            'email' => $email,
            'alert_id' => $alert_id
        );
        return $this->apiDelete('alert', $data);
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
