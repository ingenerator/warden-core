<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\mock\Ingenerator\Warden\Core\RateLimit;

use Ingenerator\Warden\Core\RateLimit\LeakyBucket;
use Ingenerator\Warden\Core\RateLimit\LeakyBucketStatus;
use PHPUnit\Framework\Assert;

class LeakyBucketStub implements LeakyBucket
{

    protected $bucket_counts = [];

    protected $bucket_status = [];

    /**
     * @var LeakyBucketStatus
     */
    protected $status;

    public static function alwaysResponds(LeakyBucketStatus $status)
    {
        $i         = new static;
        $i->status = $status;
        return $i;
    }

    public static function withBuckets(array $limits)
    {
        $i                = new static;
        $i->bucket_status = $limits;

        return $i;
    }

    /**
     * @param string $request_type
     * @param string $requester_id
     *
     * @return LeakyBucketStatus
     */
    public function attemptRequest($request_type, $requester_id)
    {
        if ( ! isset($this->bucket_counts[$request_type][$requester_id])) {
            $this->bucket_counts[$request_type][$requester_id] = 0;
        }

        $this->bucket_counts[$request_type][$requester_id]++;

        if (isset($this->bucket_status[$request_type][$requester_id])) {
            if ($this->bucket_status[$request_type][$requester_id] instanceof \DateTimeImmutable) {
                return LeakyBucketStatus::rateLimited(
                    $this->bucket_status[$request_type][$requester_id]
                );
            } else {
                return LeakyBucketStatus::allowed();
            }
        }
        return $this->status;
    }

    public function assertOnlyRequestedOnce($request_type, $requester_id)
    {
        Assert::assertEquals(
            [$request_type => [$requester_id => 1]],
            $this->bucket_counts
        );
    }

    public function assertOnlyRequestedOneOfType($request_type, $requester_id)
    {
        Assert::assertEquals([$requester_id => 1], $this->bucket_counts[$request_type]);
    }

}
