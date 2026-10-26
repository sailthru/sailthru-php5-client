sailthru-php5-client
====================

[![Code check](https://github.com/sailthru/sailthru-php5-client/actions/workflows/code-check.yml/badge.svg)](https://github.com/sailthru/sailthru-php5-client/actions/workflows/code-check.yml)

For installation instructions, documentation, and examples please visit:
[http://getstarted.sailthru.com/new-for-developers-overview/api-client-library/php5](http://getstarted.sailthru.com/new-for-developers-overview/api-client-library/php5)

A simple client library to remotely access the `Sailthru REST API` as per [http://getstarted.sailthru.com/developers/api](http://getstarted.sailthru.com/developers/api)

By default, it will make request in `JSON` format.

## Optional parameters for connection/read timeout settings

Increase timeout from 10 (default) to 30 seconds.

    $client = new Sailthru_Client($this->api_key, $this->secret, $this->api_url, array('timeout' => 30000, 'connect_timeout' => 30000));

## API Rate Limiting

Here is an example how to check rate limiting and throttle API calls based on that. For more information about Rate Limiting, see [Sailthru Documentation](https://getstarted.sailthru.com/new-for-developers-overview/api/api-technical-details/#Rate_Limiting)


```php
// get last rate limit info
$rate_limit_info = $sailthru_client->getLastRateLimitInfo("user", "POST");

// getRateLimitInfo returns null if given endpoint/method wasn't triggered previously
if ($rate_limit_info) {
    $limit = $rate_limit_info['limit'];
    $remaining = $rate_limit_info['remaining'];
    $reset_timestamp = $rate_limit_info['reset'];

    // throttle api calls based on last rate limit info
    if ($remaining <= 0) {
        $seconds_till_reset = $reset_timestamp - time();

        // sleep or perform other business logic before next user api call
        sleep($seconds_till_reset);
    }
}
```

## Tests

Use PHP 8.2+ and Composer 2:

```shell
composer install
composer verify
```

`verify` runs unit tests and installs a Composer archive into a temporary consumer
for a local request with dummy credentials. Use `composer test` or
`composer test:package` to run checks separately; `composer test:package -- dist`
retains the checked archive. These commands do not publish.

CI discovers every stable PHP release line from 8.2 through the latest on PRs,
pushes to `master`, weekly, and manual runs. The historical repository name does not describe
this test coverage.
