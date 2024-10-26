<?php namespace com\openai\rest;

/**
 * Distributes requests using the rate limits returned in the response headers
 * as weights for selecting the target.
 *
 * @test com.openai.unittest.ByRemainingRequestsTest
 */
class ByRemainingRequests implements Distribution {

  /** Distributes API calls */
  public function distribute(array $endpoints): ApiEndpoint {
    $max= 0;
    $most= null;
    $candidates= [];
    foreach ($endpoints as $i => $endpoint) {
      if (null === $endpoint->rateLimit->remaining) {
        $candidates[]= $endpoint;
      } else if ($endpoint->rateLimit->remaining > $max) {
        $most= $endpoint;
        $max= $endpoint->rateLimit->remaining;
      }
    }

    // Select between the one with the most remaining requests, including any
    // unlimited ones, and fall back to a random endpoint.
    if ($most) {
      $candidates[]= $most;
    } else if (empty($candidates)) {
      $candidates= $endpoints;
    }

    return $candidates[rand(0, sizeof($candidates) - 1)];
  }
}