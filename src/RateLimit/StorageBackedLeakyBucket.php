<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\RateLimit;


class StorageBackedLeakyBucket implements LeakyBucket
{

    /**
     * @var LeakyBucketStorage
     */
    protected $storage;

    /**
     * @var array
     */
    protected $bucket_types;

    /**
     * @var array
     */
    protected $lock_config;

    /**
     * Configuration is like:
     * [
     *   'bucket_types' => [
     *      '_type_name' => [
     *          // A bucket with space for 5 drops, one leaks every second
     *          //  - so you can burst 5 requests then do one a second forever
     *          // Bucket sizes must be integers, leak time is float
     *          'bucket_size' => 5,
     *          'leak_time_seconds' => 1
     *      ],
     *      '_type_name' => [
     *          // A bucket with space for 1 drop, one leaks every 200ms -
     *          //  - so this is basically a hard 1 per 200ms limit
     *          'bucket_size' => 5,
     *          'leak_time_seconds' => 0.2
     *      ],
     *   ],
     *   'bucket_lock' => [
     *      // How long to wait for a lock before throwing : nb locks are specific to a single bucket
     *      'timeout_ms' => 500,
     *      // How often to retry for a lock
     *      'retry_wait_ms' => 0.1,
     *      // TTL on the lock itself (long enough you've definitely used it, short enough it gets
     *      // collected if the process dies while it holds it)
     *      'lock_ttl_secs' => 5,
     *   ]
     * ]
     *
     *
     * @param LeakyBucketStorage $storage
     * @param array              $config
     */
    public function __construct(LeakyBucketStorage $storage, array $config)
    {
        $this->storage      = $storage;
        $this->bucket_types = $config['bucket_types'];
        $this->lock_config  = $config['bucket_lock'];
    }

    /**
     * @param string $request_type
     * @param string $requester_id
     *
     * @return LeakyBucketStatus
     */
    public function attemptRequest($request_type, $requester_id)
    {
        $config    = $this->getBucketConfig($request_type);
        $bucket_id = $this->getBucketId($request_type, $requester_id);
        $this->lockBucket($bucket_id);

        try {
            return $this->doBucketCalculations($bucket_id, $config);
        } finally {
            $this->storage->releaseLock($bucket_id);
        }
    }

    /**
     * @param string $request_type
     *
     * @return array
     */
    protected function getBucketConfig($request_type)
    {
        if ( ! isset($this->bucket_types[$request_type])) {
            throw new UndefinedRequestTypeException($request_type);
        }

        return $this->bucket_types[$request_type];
    }

    protected function getBucketId($request_type, $requester_id)
    {
        return $request_type.':'.$requester_id;
    }

    /**
     * @param string $bucket_id
     */
    protected function lockBucket($bucket_id)
    {
        $timeout_at = \microtime(TRUE) + ($this->lock_config['timeout_ms'] / 1000);
        do {
            if ($this->storage->createLock($bucket_id, $this->lock_config['lock_ttl_secs'])) {
                return;
            }
            \usleep($this->lock_config['retry_wait_ms'] * 1000);
        } while (\microtime(TRUE) < $timeout_at);

        throw new LockWaitTimeoutException($bucket_id, $this->lock_config['timeout_ms']);
    }

    /**
     * @param $bucket_id
     * @param $config
     *
     * @return \Ingenerator\Warden\Core\RateLimit\LeakyBucketStatus
     * @throws \Exception
     */
    protected function doBucketCalculations($bucket_id, $config)
    {
        $time_now = \microtime(TRUE);
        $bucket   = $this->storage->fetchBucket($bucket_id, ['t' => $time_now, 'd' => 0]);

        // Calculate leakage since the last attempt - this may be fractional
        // But never underflow zero as that would allow a user to build up an allowance, and
        // then burst, which we don't want.
        $leakage       = ($time_now - $bucket['t']) * (1 / $config['leak_time_seconds']);
        $current_drops = $bucket['d'] - $leakage;
        if ($current_drops < 0) {
            $current_drops = 0;
        }

        // Now add one for the current request
        $current_drops++;

        // Is there at least one full drop of space in the bucket?
        if (\ceil($current_drops) > $config['bucket_size']) {
            $result = LeakyBucketStatus::rateLimited(
                $this->calculateNextDropAvailableAt($current_drops, $config['leak_time_seconds'])
            );
        } else {
            $result = LeakyBucketStatus::allowed();
        }

        // Never overflow the bucket for storage - failed requests don't fill the bucket
        if ($current_drops > $config['bucket_size']) {
            $current_drops = $config['bucket_size'];
        }
        $this->storage->storeBucket(
            $bucket_id,
            ['t' => $time_now, 'd' => $current_drops],
            // Set a TTL long enough to last until the bucket is guaranteed to have leaked to empty
            ($config['leak_time_seconds'] * $config['bucket_size']) + 20
        );

        return $result;
    }

    /**
     * @param float $current_drops
     * @param float $leak_time_seconds
     *
     * @return \DateTimeImmutable
     * @throws \Exception
     */
    protected function calculateNextDropAvailableAt($current_drops, $leak_time_seconds)
    {
        $leakage_required = $current_drops - \floor($current_drops);
        $time_to_leak     = $leakage_required * $leak_time_seconds;
        $now              = new \DateTimeImmutable;

        return $now->add(new \DateInterval('PT'.\ceil($time_to_leak).'S'));
    }
}
