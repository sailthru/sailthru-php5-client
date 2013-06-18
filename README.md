sailthru-php5-client
====================

For installation instructions, documentation, and examples please visit:
[http://getstarted.sailthru.com/new-for-developers-overview/api-client-library/php5](http://getstarted.sailthru.com/new-for-developers-overview/api-client-library/php5)

A simple client library to remotely access the `Sailthru REST API` as per [http://getstarted.sailthru.com/developers/api](http://getstarted.sailthru.com/developers/api)

By default, it will make request in `JSON` format.

## Optional parameters for connection/read timeout settings

Increase timeout from 10 (default) to 30 seconds.

    $client = new Sailthru_Client($this->api_key, $this->secret, $this->api_url, array('timeout' => 30000, 'connect_timeout' => 30000));

--This fork allows spidering as an optional field in pushContent, something that was missing from the original project.
