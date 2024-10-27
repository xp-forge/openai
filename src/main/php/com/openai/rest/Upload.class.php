<?php namespace com\openai\rest;

use io\streams\{InputStream, OutputStream};
use webservices\rest\{RestResponse, RestUpload, UnexpectedStatus};

class Upload {
  private $upload, $rateLimit;

  /** Creates a new API instance from a given REST resource */
  public function __construct(RestUpload $upload, RateLimit $rateLimit) {
    $this->upload= $upload;
    $this->rateLimit= $rateLimit;
  }

  /**
   * Transfer a given stream
   *
   * @param  string $name
   * @param  io.streams.InputStream $in
   * @param  string $filename
   * @param  ?string $mime Uses `util.MimeType` if omitted
   * @return self
   */
  public function transfer($name, InputStream $in, $filename, $mime= null): self {
    $this->upload->transfer($name, $in, $filename, $mime);
    return $this;
  }

  /**
   * Return a stream for writing
   *
   * @param  string $name
   * @param  string $filename
   * @param  ?string $mime Uses `util.MimeType` if omitted
   * @return io.streams.OutputStream
   */
  public function stream($name, $filename, $mime= null): OutputStream {
    return $this->upload->stream($name, $filename, $mime);
  }

  /**
   * Finish uploading and return response
   *
   * @return webservices.rest.RestResponse
   * @throws webservices.rest.UnexpectedStatus
   */
  public function finish(): RestResponse {
    $r= $this->upload->finish();
    $this->rateLimit->update($r->header('x-ratelimit-remaining-requests'));
    if (200 === $r->status()) return $r;

    throw new UnexpectedStatus($r);
  }
}