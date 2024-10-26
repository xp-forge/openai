<?php namespace com\openai\rest;

interface Distribution {

  /** Distributes API calls */
  public function distribute(array $endpoints): ApiEndpoint;
}