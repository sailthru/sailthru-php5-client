<?php

/**
 *
 * Makes HTTP Request to Sailthru API server
 * Response from server depends on the format being queried
 * if 'json' format is requested, client will recieve JSON object and 'php' is requested, client will recieve PHP array
 * XML format is also available but not has not been tested thoroughly
 *
 */
class Sailthru_Client {
	/**
	 *
	 * Sailthru API Key
	 * @var string
	 */
	protected $api_key;

	/**
	 *
	 * Sailthru Secret
	 * @var string
	 */
	protected $secret;

	/**
	 *
	 * Sailthru API URL, can be different for different users according to their settings
	 * @var string
	 */
	protected $api_uri = 'https://api.sailthru.com';

	/**
	 *
	 * cURL or non-cURL request
	 * @var string
	 */
	protected $http_request_type;

	/**
	 *
	 * User agent making request to Sailthru API server
	 * Even, if you modify user-agent, please try to include 'PHP5' somewhere in the user-agent
	 * @var String
	 */
	protected $user_agent_string;

	/**
	 * Get information regarding last response from server
	 */
	private $last_response_info = null;

	/**
	 * Rate Limit information for last API call
	 */
	private $last_rate_limit_info = [];

	/**
	 * File Upload Flag variable
	 */
	private $file_upload = false;

	private $http_headers = [ 'User-Agent: Sailthru API PHP5 Client' ];

	const DEFAULT_READ_TIMEOUT = 10000;
	const DEFAULT_CONNECT_TIMEOUT = 10000;

	private $options = [ 'timeout' => Sailthru_Client::DEFAULT_READ_TIMEOUT, 'connect_timeout' => Sailthru_Client::DEFAULT_CONNECT_TIMEOUT ];

	/**
	 * Instantiate a new client; constructor optionally takes overrides for api_uri and whether
	 * to share the version of PHP that is being used.
	 *
	 * @param string $api_key
	 * @param string $secret
	 * @param string $api_uri
	 * @param array $options - optional parameters for connect/read timeout
	 */
	public function __construct( $api_key, $secret, $api_uri = false, $options = null ) {
		$this->api_key = $api_key;
		$this->secret = $secret;
		if ( false !== $api_uri ) {
			$this->api_uri = $api_uri;
		}

		if ( function_exists( 'wp_remote_get' ) ) {
			$this->http_request_type = 'http_request_wordpress';
		} elseif ( function_exists( 'curl_init' ) ) {
			$this->http_request_type = 'http_request_curl';
		} else {
			$this->http_request_type = 'http_request_without_curl';
		}

		if ( isset( $options ) ) {
			$this->options['timeout'] = isset( $options['timeout'] ) ? (int) $options['timeout'] : Sailthru_Client::DEFAULT_READ_TIMEOUT;
			$this->options['connect_timeout'] =
				isset( $options['connect_timeout'] ) ? (int) $options['connect_timeout'] : Sailthru_Client::DEFAULT_CONNECT_TIMEOUT;
		}
	}

	public function get_connect_timeout() {
		return $this->options['connect_timeout'];
	}

	public function get_timeout() {
		return $this->options['timeout'];
	}

	public function set_http_headers( array $headers ) {
		$this->http_headers = array_merge( $this->http_headers, $headers );
		return true;
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
	 * @param string $template
	 * @param string $email
	 * @param array $vars
	 * @param array $options
	 * @param string $schedule_time
	 * @link http://docs.sailthru.com/api/send
	 * @return array API result
	 */
	public function send( $template, $email, $vars = [], $options = [], $schedule_time = null ) {
		$post = [];
		$post['template'] = $template;
		$post['email'] = $email;
		$post['vars'] = $vars;
		$post['options'] = $options;
		if ( $schedule_time ) {
			$post['schedule_time'] = $schedule_time;
		}
		$result = $this->api_post( 'send', $post );
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
	 * @link http://docs.sailthru.com/api/send
	 * @return array API result
	 */
	public function multisend( $template_name, $emails, $vars = [], $evars = [], $options = [] ) {
		$post['template'] = $template_name;
		$post['email'] = is_array( $emails ) ? implode( ',', $emails ) : $emails;
		$post['vars'] = $vars;
		$post['evars'] = $evars;
		$post['options'] = $options;
		$result = $this->api_post( 'send', $post );
		return $result;
	}

	/**
	 * Get the status of a send.
	 *
	 * @param string $send_id
	 * @link http://docs.sailthru.com/api/send
	 * @return array API result
	 */
	public function get_send( $send_id ) {
		return $this->apt_get( 'send', [ 'send_id' => $send_id ] );
	}

	/**
	 * Cancel a send that was scheduled for a future time.
	 *
	 * @param string $send_id
	 * @link http://docs.sailthru.com/api/send
	 * @return array API result
	 */
	public function cancel_send( $send_id ) {
		return $this->api_delete( 'send', [ 'send_id' => $send_id ] );
	}

	/**
	 * Return information about an email address, including replacement vars and lists.
	 *
	 * @param string $email
	 * @param array $options
	 * @link http://docs.sailthru.com/api/email
	 * @return array API result
	 */
	public function get_email( $email, array $options = [] ) {
		return $this->apt_get( 'email', array_merge( [ 'email' => $email ], $options ) );
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
	 * @param integer $verified 1 or 0
	 * @param string $optout
	 * @param string $send
	 * @param array $send_vars
	 * @link http://docs.sailthru.com/api/email
	 * @return array API result
	 */
	public function set_email( $email, $vars = [], $lists = [], $templates = [], $verified = 0, $optout = null, $send = null, $send_vars = [] ) {
		$data = [ 'email' => $email ];
		if ( $vars ) {
			$data['vars'] = $vars;
		}
		if ( $lists ) {
			$data['lists'] = $lists;
		}
		if ( $templates ) {
			$data['templates'] = $templates;
		}
		$data['verified'] = (int) $verified;
		if ( null !== $optout ) {
			$data['optout'] = $optout;
		}
		if ( null !== $send ) {
			$data['send'] = $send;
		}
		if ( ! empty( $send_vars ) ) {
			$data['send_vars'] = $send_vars;
		}

		return $this->api_post( 'email', $data );
	}

	/**
	 * Update / add email address
	 *
	 * @link http://docs.sailthru.com/api/email
	 * @return array API result
	 */
	public function set_email2( $email, array $options = [] ) {
		$options['email'] = $email;
		return $this->api_post( 'email', $options );
	}

	/**
	 * Schedule a mass mail blast
	 *
	 * @param string $name the name to give to this new blast
	 * @param string $list the mailing list name to send to
	 * @param string $schedule_time when the blast should send. Dates in the past will be scheduled for immediate delivery. Any English textual datetime format known to PHP's strtotime function is acceptable, such as 2009-03-18 23:57:22 UTC, now (immediate delivery), +3 hours (3 hours from now), or February 14, 9:30 EST. Be sure to specify a timezone if you use an exact time.
	 * @param string $from_name the name appearing in the "From" of the email
	 * @param string $from_email The email address to use as the "from" – choose from any of your verified emails
	 * @param string $subject the subject line of the email
	 * @param string $content_html the HTML-format version of the email
	 * @param string $content_text the text-format version of the email
	 * @param array $options associative array
	 *         blast_id
	 *         copy_blast
	 *         copy_template
	 *         replyto
	 *         report_email
	 *         is_link_tracking
	 *         is_google_analytics
	 *         is_public
	 *         suppress_list
	 *         test_vars
	 *         email_hour_range
	 *         abtest
	 *         test_percent
	 *         data_feed_url
	 * @link http://docs.sailthru.com/api/blast
	 * @return array API result
	 */
	public function schedule_blast( $name, $list, $schedule_time, $from_name,
								  $from_email, $subject, $content_html, $content_text, $options = []
	) {
		$data = $options;
		$data['name'] = $name;
		$data['list'] = $list;
		$data['schedule_time'] = $schedule_time;
		$data['from_name'] = $from_name;
		$data['from_email'] = $from_email;
		$data['subject'] = $subject;
		$data['content_html'] = $content_html;
		$data['content_text'] = $content_text;

		return $this->api_post( 'blast', $data );
	}

	/**
	 * Schedule a mass mail from a template
	 *
	 * @param String $template
	 * @param String $list
	 * @param String $schedule_time
	 * @param array $options
	 * @link http://docs.sailthru.com/api/blast
	 * @return array API result
	 **/
	public function schedule_blast_from_template( $template, $list, $schedule_time, $options = [] ) {
		$data = $options;
		$data['copy_template'] = $template;
		$data['list'] = $list;
		$data['schedule_time'] = $schedule_time;
		return $this->api_post( 'blast', $data );
	}

	/**
	 * Schedule a mass mail blast from previous blast
	 *
	 * @param String|Integer $blast_id
	 * @param String $schedule_time
	 * @param array $options
	 * @link http://docs.sailthru.com/api/blast
	 * @return array API result
	 **/
	public function schedule_blast_from_blast( $blast_id, $schedule_time, $options = [] ) {
		$data = $options;
		$data['copy_blast'] = $blast_id;
		$data['schedule_time'] = $schedule_time;
		return $this->api_post( 'blast', $data );
	}

	/**
	 * updates existing blast
	 *
	 * @param string /integer $blast_id
	 * @param string $name
	 * @param string $list
	 * @param string $schedule_time
	 * @param string $from_name
	 * @param string $from_email
	 * @param string $subject
	 * @param string $content_html
	 * @param string $content_text
	 * @param array $options associative array
	 *         blast_id
	 *         copy_blast
	 *         copy_template
	 *         replyto
	 *         report_email
	 *         is_link_tracking
	 *         is_google_analytics
	 *         is_public
	 *         suppress_list
	 *         test_vars
	 *         email_hour_range
	 *         abtest
	 *         test_percent
	 *         data_feed_url
	 * @link http://docs.sailthru.com/api/blast
	 * @return array API result
	 */
	public function update_blast( $blast_id, $name = null, $list = null,
								$schedule_time = null, $from_name = null, $from_email = null,
								$subject = null, $content_html = null, $content_text = null,
								$options = []
	) {
		$data = $options;
		$data['blast_id'] = $blast_id;
		if ( ! is_null( $name ) ) {
			$data['name'] = $name;
		}
		if ( ! is_null( $list ) ) {
			$data['list'] = $list;
		}
		if ( ! is_null( $schedule_time ) ) {
			$data['schedule_time'] = $schedule_time;
		}
		if ( ! is_null( $from_name ) ) {
			$data['from_name'] = $from_name;
		}
		if ( ! is_null( $from_email ) ) {
			$data['from_email'] = $from_email;
		}
		if ( ! is_null( $subject ) ) {
			$data['subject'] = $subject;
		}
		if ( ! is_null( $content_html ) ) {
			$data['content_html'] = $content_html;
		}
		if ( ! is_null( $content_text ) ) {
			$data['content_text'] = $content_text;
		}

		return $this->api_post( 'blast', $data );
	}

	/**
	 * Get Blast information
	 * @param string /integer $blast_id
	 * @link http://docs.sailthru.com/api/blast
	 * @return array API result
	 */
	public function get_blast( $blast_id ) {
		return $this->apt_get( 'blast', [ 'blast_id' => $blast_id ] );
	}

	/**
	 * Get info on multiple blasts
	 * @param array $options associative array
	 *       start_date (required)
	 *       end-date (required)
	 *       status
	 * @link http://docs.sailthru.com/api/blast
	 * @return array API result
	 */
	public function get_blasts( $options ) {
		return $this->apt_get( 'blast', $options );
	}

	/**
	 * Delete Blast
	 * @param integer /string $blast_id
	 * @link http://docs.sailthru.com/api/blast
	 * @return array API result
	 */
	public function delete_blast( $blast_id ) {
		return $this->api_delete( 'blast', [ 'blast_id' => $blast_id ] );
	}

	/**
	 * Cancel a scheduled Blast
	 * @param integer /string $blast_id
	 * @link http://docs.sailthru.com/api/blast
	 * @return array API result
	 */
	public function cancel_blast( $blast_id ) {
		$data = [
			'blast_id' => $blast_id,
			'schedule_time' => '',
		];
		return $this->api_post( 'blast', $data );
	}

	/**
	 * Fetch information about a template
	 *
	 * @param string $template_name
	 * @param array $options
	 * @return array API result
	 * @link http://docs.sailthru.com/api/template
	 */
	public function get_template( $template_name, array $options = [] ) {
		$options['template'] = $template_name;
		return $this->apt_get( 'template', $options );
	}

	/**
	 * Fetch name of all existing templates
	 * @link http://docs.sailthru.com/api/template
	 * @return array API result
	 */
	public function get_templates() {
		return $this->apt_get( 'template' );
	}

	public function get_template_from_revision( $revision_id ) {
		return $this->apt_get( 'template', [ 'revision' => (int) $revision_id ] );
	}

	/**
	 * Save a template.
	 *
	 * @param string $template_name
	 * @param array $template_fields
	 * @link http://docs.sailthru.com/api/template
	 * @return array API result
	 */
	public function save_template( $template_name, array $template_fields = [] ) {
		$data = $template_fields;
		$data['template'] = $template_name;
		return $this->api_post( 'template', $data );
	}

	/**
	 * Save a template from revision
	 *
	 * @param string $template_name
	 * @param $revision_id
	 * @return array API result
	 * @link http://docs.sailthru.com/api/template
	 */
	public function save_template_from_revision( $template_name, $revision_id ) {
		$revision_id = (int) $revision_id;
		return $this->save_template( $template_name, [ 'revision' => $revision_id ] );
	}

	/**
	 * Delete a template.
	 *
	 * @param string $template_name
	 * @return array API result
	 * @link http://docs.sailthru.com/api/template
	 */
	public function delete_template( $template_name ) {
		return $this->api_delete( 'template', [ 'template' => $template_name ] );
	}

	/**
	 * Fetch information about an include
	 *
	 * @param string $include_name
	 * @return array API result
	 */
	public function get_include( $include_name, array $options = [] ) {
		$options['include'] = $include_name;
		return $this->apt_get( 'include', $options );
	}

	/**
	 * Fetch name of all existing includes
	 * @return array API result
	 */
	public function get_includes() {
		return $this->apt_get( 'include' );
	}

	/**
	 * Save an include
	 *
	 * @param string $include_name
	 * @param array $include_fields
	 * @return array API result
	 */
	public function save_include( $include_name, array $include_fields = [] ) {
		$data = $include_fields;
		$data['include'] = $include_name;
		return $this->api_post( 'include', $data );
	}

	/**
	 * Get information about a list.
	 *
	 * @param string $list
	 * @return array
	 * @link http://docs.sailthru.com/api/list
	 */
	public function get_list( $list ) {
		return $this->apt_get( 'list', [ 'list' => $list ] );
	}

	/**
	 * Get information about all lists
	 * @return array
	 * @link http://docs.sailthru.com/api/list
	 */
	public function get_lists() {
		return $this->apt_get( 'list', [] );
	}

	/**
	 * Create a list, or update a list.
	 *
	 * @param string $list
	 * @param string $list
	 * @param string $type
	 * @param bool $primary
	 * @param array $query
	 * @return array
	 * @link http://docs.sailthru.com/api/list
	 * @link http://docs.sailthru.com/api/query
	 */
	public function save_list( $list, $type = null, $primary = null, $query = [] ) {
		$data = [
			'list' => $list,
			'type' => $type,
			'primary' => $primary ? 1 : 0,
			'query' => $query,
		];
		return $this->api_post( 'list', $data );
	}

	/**
	 * Deletes a list.
	 *
	 * @param string $list
	 * @return array
	 * @link http://docs.sailthru.com/api/list
	 */
	public function delete_list( $list ) {
		return $this->api_delete( 'list', [ 'list' => $list ] );
	}

	/**
	 *
	 * Push a new piece of content to Sailthru, triggering any applicable alerts.
	 *
	 * @param String $title
	 * @param String $url
	 * @param String $date
	 * @param Mixed $tags Null for empty values, or String or arrays
	 * @link http://docs.sailthru.com/api/content
	 * @return array API result
	 */
	public function push_content( $title, $url, $date = null, $tags = null, $vars = [], $options = [] ) {
		$data = $options;
		$data['title'] = $title;
		$data['url'] = $url;
		if ( ! is_null( $tags ) ) {
			$data['tags'] = is_array( $tags ) ? implode( ',', $tags ) : $tags;
		}
		if ( ! is_null( $date ) ) {
			$data['date'] = $date;
		}
		if ( ! empty( $vars ) ) {
			$data['vars'] = $vars;
		}
		return $this->api_post( 'content', $data );
	}

	/**
	 *
	 * Retrieve a user's alert settings.
	 *
	 * @link http://docs.sailthru.com/api/alert
	 * @param String $email
	 * @return array API result
	 */
	public function get_alert( $email ) {
		$data = [
			'email' => $email,
		];
		return $this->apt_get( 'alert', $data );
	}

	/**
	 *
	 * Add a new alert to a user. You can add either a realtime or a summary alert (daily/weekly).
	 * $when is only required when alert type is weekly or daily
	 *
	 * <code>
	 * <?php
	 * $options = array(
	 *     'match' => array(
	 *         'type' => array('shoes', 'shirts'),
	 *         'min' => array('price' => 3000),
	 *         'tags' => array('blue', 'red'),
	 *     )
	 * );
	 * $response = $sailthruClient->save_alert("praj@sailthru.com", 'realtime', 'default', null, $options);
	 * ?>
	 * </code>
	 *
	 * @link http://docs.sailthru.com/api/alert
	 * @param String $email
	 * @param String $type
	 * @param String $template
	 * @param String $when
	 * @param array $options Associative array of additive nature
	 *         match  Exact-match a custom variable  match[type]=shoes
	 *         min    Minimum-value variables        min[price]=30000
	 *         max    Maximum-value match            max[price]=50000
	 *         tags   Tag-match                      tags[]=blue
	 * @return array API result
	 */
	public function save_alert( $email, $type, $template, $when = null, $options = [] ) {
		$data = $options;
		$data['email'] = $email;
		$data['type'] = $type;
		$data['template'] = $template;
		if ( 'weekly' === $type || 'daily' === $type ) {
			$data['when'] = $when;
		}
		return $this->api_post( 'alert', $data );
	}

	/**
	 * Remove an alert from a user's settings.
	 * @link http://docs.sailthru.com/api/alert
	 * @param <type> $email
	 * @param <type> $alert_id
	 * @return array API result
	 */
	public function delete_alert( $email, $alert_id ) {
		$data = [
			'email' => $email,
			'alert_id' => $alert_id,
		];
		return $this->api_delete( 'alert', $data );
	}

	/**
	 * Record that a user has made a purchase, or has added items to their purchase total.
	 * @link http://docs.sailthru.com/api/purchase
	 * @return array API result
	 */
	public function purchase( $email, array $items, $incomplete = null, $message_id = null, array $options = [] ) {
		$data = $options;
		$data['email'] = $email;
		$data['items'] = $items;
		if ( ! is_null( $incomplete ) ) {
			$data['incomplete'] = (int) $incomplete;
		}
		if ( ! is_null( $message_id ) ) {
			$data['message_id'] = $message_id;
		}
		return $this->api_post( 'purchase', $data );
	}

	/**
	 * Make a purchase API call with incomplete flag
	 * @link http://docs.sailthru.com/api/purchase
	 * @return array API result
	 */
	public function purchase_incomplete( $email, array $items, $message_id, array $options = [] ) {
		return $this->purchase( $email, $items, 1, $message_id, $options );
	}

	/**
	 * Retrieve information about your subscriber counts on a particular list, on a particular day.
	 * @link http://docs.sailthru.com/api/stats
	 * @param String $list
	 * @param String $date
	 * @return array API result
	 */
	public function stats_list( $list = null, $date = null ) {
		$data = [];
		if ( ! is_null( $list ) ) {
			$data['list'] = $list;
		}

		if ( ! is_null( $date ) ) {
			$data['date'] = $date;
		}
		$data['stat'] = 'list';
		return $this->stats( $data );
	}

	/**
	 * Retrieve information about a particular blast or aggregated information from all of blasts over a specified date range.
	 * @param array $data
	 * @return array API result
	 */
	public function stats_blast( $blast_id = null, $start_date = null, $end_date = null, array $data = [] ) {
		$data['stat'] = 'blast';
		if ( ! is_null( $blast_id ) ) {
			$data['blast_id'] = $blast_id;
		}
		if ( ! is_null( $start_date ) ) {
			$data['start_date'] = $start_date;
		}
		if ( ! is_null( $end_date ) ) {
			$data['end_date'] = $end_date;
		}
		return $this->stats( $data );
	}

	/**
	 * Retrieve information about a particular send or aggregated information from all of templates over a specified date range.
	 * @param array $data
	 * @return array API result
	 */
	public function stats_send( $template = null, $start_date = null, $end_date = null, array $data = [] ) {
		$data['stat'] = 'send';

		if ( ! is_null( $template ) ) {
			$data['template'] = $template;
		}
		if ( ! is_null( $start_date ) ) {
			$data['start_date'] = $start_date;
		}
		if ( ! is_null( $end_date ) ) {
			$data['end_date'] = $end_date;
		}

		return $this->stats( $data );
	}

	/**
	 * Make Stats API Request
	 * @param array $data
	 * @return array API result
	 */
	public function stats( array $data ) {
		return $this->apt_get( 'stats', $data );
	}

	/**
	 *
	 * Returns true if the incoming request is an authenticated verify post.
	 * @link http://docs.sailthru.com/api/postbacks
	 * @return boolean
	 */
	public function receive_verify_post() {
		$params = $_POST; // @codingStandardsIgnoreLine
		foreach ( [ 'action', 'email', 'send_id', 'sig' ] as $k ) {
			if ( ! isset( $params[ $k ] ) ) {
				return false;
			}
		}

		if ( 'verify' !== $params['action'] ) {
			return false;
		}
		$sig = $params['sig'];
		unset( $params['sig'] );
		if ( Sailthru_Util::get_signature_hash( $params, $this->secret ) !== $sig ) {
			return false;
		}
		$send = $this->get_send( $params['send_id'] );
		if ( ! isset( $send['email'] ) ) {
			return false;
		}
		if ( $send['email'] !== $params['email'] ) {
			return false;
		}
		return true;
	}

	/**
	 *
	 * Optout postbacks
	 * @return boolean
	 * @link http://docs.sailthru.com/api/postbacks
	 */
	public function receive_optout_post() {
		$params = $_POST; // @codingStandardsIgnoreLine
		foreach ( [ 'action', 'email', 'sig' ] as $k ) {
			if ( ! isset( $params[ $k ] ) ) {
				return false;
			}
		}

		if ( 'optout' !== $params['action'] ) {
			return false;
		}
		$sig = $params['sig'];
		unset( $params['sig'] );
		if ( Sailthru_Util::get_signature_hash( $params, $this->secret ) !== $sig ) {
			return false;
		}
		return true;
	}

	/**
	 *
	 * Update postbacks
	 * @return boolean
	 * @link http://docs.sailthru.com/api/postbacks
	 */
	public function receive_update_post() {
		$params = $_POST; // @codingStandardsIgnoreLine
		foreach ( [ 'action', 'sid', 'sig' ] as $k ) {
			if ( ! isset( $params[ $k ] ) ) {
				return false;
			}
		}

		if ( 'update' !== $params['action'] ) {
			return false;
		}
		$sig = $params['sig'];
		unset( $params['sig'] );
		if ( Sailthru_Util::get_signature_hash( $params, $this->secret ) !== $sig ) {
			return false;
		}
		return true;
	}

	/**
	 *
	 * Hard bounce postbacks
	 * @return boolean
	 * @link http://docs.sailthru.com/api/postbacks
	 */
	public function receive_hard_bounce_post() {
		$params = $_POST; // @codingStandardsIgnoreLine
		foreach ( [ 'action', 'email', 'sig' ] as $k ) {
			if ( ! isset( $params[ $k ] ) ) {
				return false;
			}
		}
		if ( 'hardbounce' !== $params['action'] ) {
			return false;
		}
		$sig = $params['sig'];
		unset( $params['sig'] );
		if ( Sailthru_Util::get_signature_hash( $params, $this->secret ) !== $sig ) {
			return false;
		}
		if ( isset( $params['send_id'] ) ) {
			$send_id = $params['send_id'];
			$send = $this->get_send( $send_id );
			if ( ! isset( $send['email'] ) ) {
				return false;
			}
		} else if ( isset( $params['blast_id'] ) ) {
			$blast_id = $params['blast_id'];
			$blast = $this->get_blast( $blast_id );
			if ( isset( $blast['error'] ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Get status of a job
	 * @param String $job_id
	 * @return array
	 */
	public function get_job_status( $job_id ) {
		return $this->apt_get( 'job', [ 'job_id' => $job_id ] );
	}

	/**
	 * process job api call
	 * @param String $job
	 * @param array $data
	 * @param bool|String $report_email
	 * @param bool|String $postback_url
	 * @param array $binary_data_param
	 * @param array $options
	 * @return array
	 */
	protected function process_job( $job, array $data = [], $report_email = false, $postback_url = false, array $binary_data_param = [],
								  array $options = [] ) {
		$data['job'] = $job;
		if ( $report_email ) {
			$data['report_email'] = $report_email;
		}
		if ( $postback_url ) {
			$data['postback_url'] = $postback_url;
		}
		return $this->api_post( 'job', $data, $binary_data_param, $options );
	}

	/**
	 * Process import job from given email string CSV
	 * @param String $list
	 * @param String $emails
	 * @param bool|String $report_email
	 * @param bool|String $postback_url
	 * @return array
	 */
	public function process_import_job( $list, $emails, $report_email = false, $postback_url = false, array $options = [] ) {
		$data = [
			'emails' => $emails,
			'list' => $list,
		];
		return $this->process_job( 'import', $data, $report_email, $postback_url, [], $options );
	}

	/**
	 * Process import job from given file path of a CSV or email per line file
	 *
	 * @param String $list
	 * @param $file_path
	 * @param bool|String $report_email
	 * @param bool|String $postback_url
	 * @param array $options
	 * @return array
	 */
	public function process_import_job_from_file( $list, $file_path, $report_email = false, $postback_url = false, array $options = [] ) {
		$data = [
			'file' => $file_path,
			'list' => $list,
		];
		return $this->process_job( 'import', $data, $report_email, $postback_url, [ 'file' ], $options );
	}

	/**
	 * Process purchase import job from given file path of an email per line JSON file
	 *
	 * @param String $file_path
	 * @param bool|String $report_email
	 * @param bool|String $postback_url
	 * @param array $options
	 * @return array
	 */
	public function process_purchase_import_job_from_file( $file_path, $report_email = false, $postback_url = false, array $options = [] ) {
		$data = [
			'file' => $file_path,
		];
		return $this->process_job( 'purchase_import', $data, $report_email, $postback_url, [ 'file' ], $options );
	}

	/**
	 * Process a snapshot job
	 *
	 * @param array $query
	 * @param bool|String $report_email
	 * @param bool|String $postback_url
	 * @return array
	 */
	public function process_snapshot_job( array $query, $report_email = false, $postback_url = false, array $options = [] ) {
		$data = [ 'query' => $query ];
		return $this->process_job( 'snaphot', $data, $report_email, $postback_url, [], $options );
	}

	/**
	 * Process a export list job
	 * @param String $list
	 * @param bool|String $report_email
	 * @param bool|String $postback_url
	 * @param array $options
	 * @return array
	 */
	public function process_export_list_job( $list, $report_email = false, $postback_url = false, array $options = [] ) {
		$data = [ 'list' => $list ];
		return $this->process_job( 'export_list_data', $data, $report_email, $postback_url, [], $options );
	}

	/**
	 * Export blast data in CSV format
	 * @param integer $blast_id
	 * @param bool|String $report_email
	 * @param bool|String $postback_url
	 * @param array $options
	 * @return array
	 */
	public function process_blast_query_job( $blast_id, $report_email = false, $postback_url = false, array $options = [] ) {
		return $this->process_job( 'blast_query', [ 'blast_id' => $blast_id ], $report_email, $postback_url, [], $options );
	}

	/**
	 * Perform a bulk update of any number of user profiles from given context: String CSV, file, URL or query
	 * @param String $context
	 * @param $value
	 * @param array $update
	 * @param bool|String $report_email
	 * @param bool|String $postback_url
	 * @param array $file_params
	 * @return array
	 */
	public function process_update_job( $context, $value, array $update = [], $report_email = false, $postback_url = false, array $file_params = [],
									 array $options = [] ) {
		$data = [
			$context => $value,
		];
		if ( count( $update ) > 0 ) {
			$data['update'] = $update;
		}
		return $this->process_job( 'update', $data, $report_email, $postback_url, $file_params, $options );
	}

	/**
	 * Perform a bulk update of any number of user profiles from given URL
	 * @param String $url
	 * @param array $update
	 * @param bool|String $report_email
	 * @param bool|String $postback_url
	 * @param array $options
	 * @return array
	 */
	public function process_update_job_from_url( $url, array $update = [], $report_email = false, $postback_url = false, array $options = [] ) {
		return $this->process_update_job( 'url', $url, $update, $report_email, $postback_url, [], $options );
	}

	/**
	 * Perform a bulk update of any number of user profiles from given file
	 * @param $file
	 * @param Array $update
	 * @param bool|String $report_email
	 * @param bool|String $postback_url
	 * @param array $options
	 * @return array
	 * @internal param String $url
	 */
	public function process_update_job_from_file( $file, array $update = [], $report_email = false, $postback_url = false, array $options = [] ) {
		return $this->process_update_job( 'file', $file, $update, $report_email, $postback_url, [ 'file' ], $options );
	}

	/**
	 * Perform a bulk update of any number of user profiles from a query
	 * @param Array $query
	 * @param Array $update
	 * @param String $report_email
	 * @param String $postback_url
	 */
	public function process_update_job_from_query( $query, array $update = [], $report_email = false, $postback_url = false, array $options = [] ) {
		return $this->process_update_job( 'query', $query, $update, $report_email, $postback_url, [], $options );
	}

	/**
	 * Perform a bulk update of any number of user profiles from emails CSV
	 * @param String $emails
	 * @param Array $update
	 * @param bool|String $report_email
	 * @param bool|String $postback_url
	 * @return array
	 */
	public function process_update_job_from_emails( $emails, array $update = [], $report_email = false, $postback_url = false, array $options = [] ) {
		return $this->process_update_job( 'emails', $emails, $update, $report_email, $postback_url, [], $options );
	}

	/**
	 * Save existing user
	 * @param String $id
	 * @param array $options
	 * @return array
	 */
	public function save_user( $id, array $options = [] ) {
		$data = $options;
		$data['id'] = $id;
		return $this->api_post( 'user', $data );
	}

	/**
	 * Get user by Sailthru ID
	 * @param String $id
	 * @return array
	 */
	public function get_user_by_sid( $id ) {
		return $this->apt_get( 'user', [ 'id' => $id ] );
	}

	/**
	 * Get user by specified key
	 * @param String $id
	 * @param String $key
	 * @param array $fields
	 * @return array
	 */
	public function get_user_by_key( $id, $key, array $fields = [] ) {
		$data = [
			'id' => $id,
			'key' => $key,
			'fields' => $fields,
		];
		return $this->apt_get( 'user', $data );
	}

	/**
	 *
	 * Set Horizon cookie
	 *
	 * @param string $email horizon user email
	 * @param string $domain
	 * @param integer $duration
	 * @param boolean $secure
	 * @return boolean
	 */
	public function set_horizon_cookie( $email, $domain = null, $duration = null, $secure = false ) {
		$data = $this->get_user_by_key( $email, 'email', [ 'keys' => 1 ] );
		if ( ! isset( $data['keys']['cookie'] ) ) {
			return false;
		}
		if ( ! $domain ) {
			if ( function_exists( 'sanitize_text_field' ) && function_exists( 'wp_unslash' ) ) {
				$domain_parts = isset( $_SERVER['HTTP_HOST'] ) ? explode( '.', sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : array(); // input var okay
			} else {
				$domain_parts = explode( '.', $_SERVER['HTTP_HOST'] ); // @codingStandardsIgnoreLine
			}
			$domain = $domain_parts[ count( $domain_parts ) - 2 ] . '.' . $domain_parts[ count( $domain_parts ) - 1 ];
		}
		if ( null === $duration ) {
			$expire = time() + 31556926;
		} else if ( $duration ) {
			$expire = time() + $duration;
		} else {
			$expire = 0;
		}
		return setcookie( 'sailthru_hid', $data['keys']['cookie'], $expire, '/', $domain, $secure ); // @codingStandardsIgnoreLine
	}

	/**
	 * Get an HTML preview of a template.
	 * @param $template
	 * @param $email
	 * @return array
	 * @link http://docs.sailthru.com/api/preview
	 */
	public function preview_template_with_html( $template, $email ) {
		$data = [];
		$data['template'] = $template;
		$data['email'] = $email;

		$result = $this->api_post( 'preview', $data );
		return $result;
	}

	/**
	 * Get an HTML preview of a blast.
	 * @param $blast_id
	 * @param $email
	 * @return array
	 * @link http://docs.sailthru.com/api/preview
	 */
	public function preview_blast_with_html( $blast_id, $email ) {
		$data = [];
		$data['blast_id'] = $blast_id;
		$data['email'] = $email;

		$result = $this->api_post( 'preview', $data );
		return $result;
	}

	/**
	 * Get an HTML preview of a recurring blast.
	 * @param $blast_repeat_id
	 * @param $email
	 * @return array
	 * @link http://docs.sailthru.com/api/preview
	 */
	public function preview_recurring_blast_with_html( $blast_repeat_id, $email ) {
		$data = [];
		$data['blast_repeat_id'] = $blast_repeat_id;
		$data['email'] = $email;

		$result = $this->api_post( 'preview', $data );
	}

	/**
	 * Get an HTML preview of content_html.
	 * @param $content_html
	 * @param $email
	 * @return array
	 * @link http://docs.sailthru.com/api/preview
	 */
	public function preview_content_with_html( $content_html, $email ) {
		$data = [];
		$data['content_html'] = $content_html;
		$data['email'] = $email;

		$result = $this->api_post( 'preview', $data );
		return $result;
	}

	/**
	 * Get an email preview of a template.
	 * @param $template
	 * @param $send_email
	 * @return array
	 * @link http://docs.sailthru.com/api/preview
	 */
	public function preview_template_with_email( $template, $send_email ) {
		$data = [];
		$data['template'] = $template;
		$data['send_email'] = $send_email;

		$result = $this->api_post( 'preview', $data );
		return $result;
	}

	/**
	 * Get an email preview of a blast.
	 * @param $blast_id
	 * @param $send_email
	 * @return array
	 * @link http://docs.sailthru.com/api/preview
	 */
	public function preview_blast_with_email( $blast_id, $send_email ) {
		$data = [];
		$data['blast_id'] = $blast_id;
		$data['send_email'] = $send_email;

		$result = $this->api_post( 'preview', $data );
		return $result;
	}

	/**
	 * Get an email preview of a recurring blast.
	 * @param $blast_repeat_id
	 * @param $send_email
	 * @return array
	 * @link http://docs.sailthru.com/api/preview
	 */
	public function preview_recurring_blast_with_email( $blast_repeat_id, $send_email ) {
		$data = [];
		$data['blast_repeat_id'] = $blast_repeat_id;
		$data['send_email'] = $send_email;

		$result = $this->api_post( 'preview', $data );
		return $result;
	}

	/**
	 * Get an email preview of content_html.
	 * @param $content_html
	 * @param $send_email
	 * @return array
	 * @link http://docs.sailthru.com/api/preview
	 */
	public function preview_content_with_email( $content_html, $send_email ) {
		$data = [];
		$data['content_html'] = $content_html;
		$data['send_email'] = $send_email;

		$result = $this->api_post( 'preview', $data );
		return $result;
	}

	/**
	 * Get Triggers
	 * @return array
	 * @link http://docs.sailthru.com/api/trigger
	 */
	public function get_triggers() {
		$result = $this->apt_get( 'trigger' );
		return $result;
	}

	/**
	 * Get information on a trigger
	 * @param string $template
	 * @param string $trigger_id
	 * @return array
	 * @link http://docs.sailthru.com/api/trigger
	 */
	public function get_trigger_by_template( $template, $trigger_id = null ) {
		$data = [];
		$data['template'] = $template;
		if ( ! is_null( $trigger_id ) ) {
			$data['trigger_id'] = $trigger_id;
		}

		$result = $this->apt_get( 'trigger', $data );
		return $result;
	}

	/**
	 * Get information on a trigger
	 * @param string $event
	 * @return array
	 * @link http://docs.sailthru.com/api/trigger
	 */
	public function get_trigger_by_event( $event ) {
		$data = [];
		$data['event'] = $event;

		$result = $this->apt_get( 'trigger', $data );
		return $result;
	}

	/**
	 * Get information on a trigger
	 * @param string $trigger_id
	 * @return array
	 * @link http://docs.sailthru.com/api/trigger
	 */
	public function get_trigger_by_id( $trigger_id ) {
		$data = [];
		$data['trigger_id'] = $trigger_id;

		$result = $this->apt_get( 'trigger', $data );
		return $result;
	}

	/**
	 * Create a trigger for templates
	 * @param string $template
	 * @param integer $time
	 * @param string $time_unit
	 * @param string $event
	 * @param string $zephyr
	 * @return array
	 * @link http://docs.sailthru.com/api/trigger
	 */
	public function post_trigger( $template, $time, $time_unit, $event, $zephyr ) {
		$data = [];
		$data['template'] = $template;
		$data['time'] = $time;
		$data['time_unit'] = $time_unit;
		$data['event'] = $event;
		$data['zephyr'] = $zephyr;

		$result = $this->api_post( 'trigger', $data );
		return $result;
	}

	/**
	 * Create a trigger for events
	 * @param integer $time
	 * @param string $time_unit
	 * @param string $event
	 * @param string $zephyr
	 * @return array
	 * @link http://docs.sailthru.com/api/trigger
	 */
	public function post_event_trigger( $event, $time, $time_unit, $zephyr ) {
		$data = [];
		$data['time'] = $time;
		$data['time_unit'] = $time_unit;
		$data['event'] = $event;
		$data['zephyr'] = $zephyr;

		$result = $this->api_post( 'trigger', $data );
		return $result;
	}

	/**
	 * Notify Sailthru of an event
	 * @param string $id
	 * @param string $event
	 * @param array $options
	 * @return array
	 * @link http://docs.sailthru.com/api/event
	 */
	public function post_event( $id, $event, $options = [] ) {
		$data = $options;
		$data['id'] = $id;
		$data['event'] = $event;

		$result = $this->api_post( 'event', $data );
		return $result;
	}

	/**
	 * Perform an HTTP request using WordPress calls
	 *
	 * @param string $action
	 * @param array $data
	 * @param string $method
	 * @param array $options
	 * @return string
	 * @throws Sailthru_Client_Exception
	 */
	protected function http_request_wordpress( $action, array $data, $method = 'POST', $options = [] ) {
		if ( true === $this->file_upload ) {
			throw new Sailthru_Client_Exception( 'Cannot upload file using wp_remote_post()' );
		}

		$url = $this->api_uri . '/' . $action;
		$arguments = array( 'method' => $method );

		if ( 'POST' === $method ) {
			if ( isset( $options['timeout'] ) ) {
				$arguments['timeout'] = $options['timeout'];
			}
			$arguments['body'] = http_build_query( $data, '', '&' );

			$response = wp_remote_post( $url, $arguments );

		} else {
			$url .= '?' . http_build_query( $data, '', '&' );

			if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
				$response = vip_safe_wp_remote_get( $url, $arguments );
			} else {
				$response = wp_remote_get( $url, $arguments ); // @codingStandardsIgnoreLine
			}
		}

		if ( is_wp_error( $response ) ) {
			throw new Sailthru_Client_Exception( "Bad response received from $url" );
		}

		$headers = wp_remote_retrieve_headers( $response );
		$body = wp_remote_retrieve_body( $response );
		$this->last_response_info = $response['http_response'];
		$this->last_rate_limit_info[ $action ][ $method ] = self::parse_rate_limit_headers( $headers );

		return $body;
	}

	/**
	 * Perform an HTTP request using the curl extension
	 *
	 * @param string $action
	 * @param array $data
	 * @param string $method
	 * @param array $options
	 * @return string
	 * @throws Sailthru_Client_Exception
	 */
	protected function http_request_curl( $action, array $data, $method = 'POST', $options = [] ) {
		$url = $this->api_uri . '/' . $action;

		// @codingStandardsIgnoreStart
		$ch = curl_init();
		$options = array_merge( $this->options, $options );
		if ( 'POST' === $method ) {
			curl_setopt( $ch, CURLOPT_POST, true );
			if ( true === $this->file_upload ) {
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
				$this->file_upload = false;
			} else {
				curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $data, '', '&' ) );
			}
		} else {
			$url .= '?' . http_build_query( $data, '', '&' );
			if ( 'GET' !== $method ) {
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
			}
		}
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_HEADER, true );
		curl_setopt( $ch, CURLOPT_ENCODING, 'gzip' );
		curl_setopt( $ch, CURLOPT_TIMEOUT_MS, $options['timeout'] );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT_MS, $options['connect_timeout'] );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

		curl_setopt( $ch, CURLOPT_HTTPHEADER, $this->http_headers );
		$response = curl_exec( $ch );
		$this->last_response_info = curl_getinfo( $ch );
		curl_close( $ch );
		// @codingStandardsIgnoreEnd

		if ( ! $response ) {
			throw new Sailthru_Client_Exception( "Bad response received from $url" );
		}

		// parse headers and body
		$parts = explode( "\r\n\r\nHTTP/", $response );
		$parts = (count( $parts ) > 1 ? 'HTTP/' : '') . array_pop( $parts ); // deal with HTTP/1.1 100 Continue before other headers
		list($headers, $body) = explode( "\r\n\r\n", $parts, 2 );
		$this->last_rate_limit_info[ $action ][ $method ] = self::parse_rate_limit_headers( $headers );

		return $body;
	}

	/**
	 * Adapted from: http://netevil.org/blog/2006/nov/http-post-from-php-without-curl
	 *
	 * @param string $action
	 * @param array $data
	 * @param string $method
	 * @param array $options
	 * @return string
	 * @throws Sailthru_Client_Exception
	 */
	protected function http_request_without_curl( $action, $data, $method = 'POST', $options = [] ) {
		if ( true === $this->file_upload ) {
			$this->file_upload = false;
			throw new Sailthru_Client_Exception( 'cURL extension is required for the request with file upload' );
		}

		$url = $this->api_uri . '/' . $action;
		$params = [ 'http' => [ 'method' => $method, 'ignore_errors' => true ] ];
		if ( 'POST' === $method ) {
			$params['http']['content'] = is_array( $data ) ? http_build_query( $data, '', '&' ) : $data;
		} else {
			$url .= '?' . http_build_query( $data, '', '&' );
		}
		$params['http']['header'] = "User-Agent: {$this->user_agent_string}\nContent-Type: application/x-www-form-urlencoded";
		$ctx = stream_context_create( $params );
		$fp = fopen( $url, 'rb', false, $ctx );
		if ( ! $fp ) {
			throw new Sailthru_Client_Exception( "Unable to open stream: $url" );
		}
		$response = stream_get_contents( $fp );
		if ( false === $response ) {
			throw new Sailthru_Client_Exception( "No response received from stream: $url" );
		}
		return $response;
	}

	/**
	 * Perform an HTTP request, checking for curl extension support
	 *
	 * @param $action
	 * @param array $data
	 * @param string $method
	 * @param array $options
	 * @return string
	 * @throws Sailthru_Client_Exception
	 */
	protected function http_request( $action, $data, $method = 'POST', $options = [] ) {
		$response = $this->{$this->http_request_type}($action, $data, $method, $options);
		$json = json_decode( $response, true );
		if ( null === $json ) {
			throw new Sailthru_Client_Exception( "Response: {$response} is not a valid JSON" );
		}
		return $json;
	}

	/**
	 * Perform an API POST (or other) request, using the shared-secret auth hash.
	 * if binary_data_param is set, its appends '@' so that cURL can make binary POST request
	 *
	 * @param string $action
	 * @param array $data
	 * @param array $binary_data_param
	 * @param array $options
	 * @return array
	 */
	public function api_post( $action, $data, array $binary_data_param = [], $options = [] ) {
		$binary_data = [];
		if ( ! empty( $binary_data_param ) ) {
			foreach ( $binary_data_param as $param ) {
				if ( isset( $data[ $param ] ) && file_exists( $data[ $param ] ) ) {
					$binary_data[ $param ] = version_compare( PHP_VERSION, '5.5.0' ) >= 0 && class_exists( 'CURLFile' )
						? new CURLFile( $data[ $param ] )
						: "@{$data[$param]}";
					unset( $data[ $param ] );
					$this->file_upload = true;
				}
			}
		}
		$payload = $this->prepare_json_payload( $data, $binary_data );
		return $this->http_request( $action, $payload, 'POST', $options );
	}

	/**
	 * Perform an API GET request, using the shared-secret auth hash.
	 *
	 * @param string $action
	 * @param array $data
	 * @return array
	 */
	public function apt_get( $action, $data = [], $method = 'GET', $options = [] ) {
		return $this->http_request( $action, $this->prepare_json_payload( $data ), $method, $options );
	}

	/**
	 * Perform an API DELETE request, using the shared-secret auth hash.
	 *
	 * @param string $action
	 * @param array $data
	 * @return array
	 */
	public function api_delete( $action, $data, $options = [] ) {
		return $this->apt_get( $action, $data, 'DELETE', $options );
	}

	/**
	 * get information from last server response when used with cURL
	 * returns associative array as per http://us.php.net/curl_getinfo
	 * @return array or null
	 */
	public function getlast_response_info() {
		return $this->last_response_info;
	}

	/**
	 * Prepare JSON payload
	 */
	protected function prepare_json_payload( array $data, array $binary_data = [] ) {
		$payload = [
			'api_key' => $this->api_key,
			'format' => 'json',
			'json' => function_exists( 'wp_json_encode' ) ? wp_json_encode( $data ) : json_encode( $data ), // @codingStandardsIgnoreLine
		];
		$payload['sig'] = Sailthru_Util::get_signature_hash( $payload, $this->secret );
		if ( ! empty( $binary_data ) ) {
			$payload = array_merge( $payload, $binary_data );
		}
		return $payload;
	}

	/**
	 * get the rate limit information for the very last call with given action and method
	 * @param string $action
	 * @param string $method GET, POST or DELETE
	 * @return array or null
	 */
	public function getlast_rate_limit_info( $action, $method ) {
		$rate_limit_info = $this->last_rate_limit_info;
		$method = strtoupper( $method );
		return (isset( $rate_limit_info[ $action ] ) && isset( $rate_limit_info[ $action ][ $method ] )) ?
			$rate_limit_info[ $action ][ $method ] : null;
	}

	/**
	 * parse rate limit headers from http response
	 * @param string $headers
	 * @return array|null
	 */
	private function parse_rate_limit_headers( $headers ) {
		if ( null === $headers ) {
			return null;
		}

		$rate_limit_headers = [];

		// Using curl
		if ( is_string( $headers ) ) {
			$header_lines = explode( "\n", $headers );
		} elseif ( is_array( $headers ) ) {
			$header_lines = $headers;
		}

		foreach ( $header_lines as $hl ) {
			if ( strpos( $hl, 'X-Rate-Limit-Limit' ) !== false && ! isset( $rate_limit_headers['limit'] ) ) {
				list($header_name, $header_value) = explode( ':', $hl, 2 );
				$rate_limit_headers['limit'] = intval( $header_value );
			} else if ( strpos( $hl, 'X-Rate-Limit-Remaining' ) !== false && ! isset( $rate_limit_headers['remaining'] ) ) {
				list($header_name, $header_value) = explode( ':', $hl, 2 );
				$rate_limit_headers['remaining'] = intval( $header_value );
			} else if ( strpos( $hl, 'X-Rate-Limit-Reset' ) !== false && ! isset( $rate_limit_headers['reset'] ) ) {
				list($header_name, $header_value) = explode( ':', $hl, 2 );
				$rate_limit_headers['reset'] = intval( $header_value );
			}

			if ( count( $rate_limit_headers ) === 3 ) {
				return $rate_limit_headers;
			}
		}

		return null;
	}
}
