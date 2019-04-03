<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\unit\Ingenerator\Warden\Core\RateLimit;


use Ingenerator\Warden\Core\RateLimit\LeakyBucketStorage;
use Ingenerator\Warden\Core\RateLimit\StorageBackedLeakyBucket;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class StorageBackedLeakyBucketTest extends TestCase
{

    protected $config = [
        'bucket_types' => [
            'warden.email.reset' => [
                'bucket_size'       => 1,
                'leak_time_seconds' => 10
            ],
        ],
        'bucket_lock'  => [
            'timeout_ms'    => 500,
            'retry_wait_ms' => 10,
            'lock_ttl_secs' => 5,
        ]
    ];

    /**
     * @var \test\unit\Ingenerator\Warden\Core\RateLimit\BucketStorageStub
     */
    protected $storage;

    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf(StorageBackedLeakyBucket::class, $this->newSubject());
    }

    /**
     * @expectedException \Ingenerator\Warden\Core\RateLimit\UndefinedRequestTypeException
     */
    public function test_it_throws_if_no_config_for_request_type()
    {
        $this->config['bucket_types'] = [
            'wrong.here' => [
                'bucket_size'       => 5,
                'leak_time_seconds' => 100
            ]
        ];
        $this->newSubject()->attemptRequest('foo.bar', 'anyone');
    }

    /**
     * @expectedException \Ingenerator\Warden\Core\RateLimit\LockWaitTimeoutException
     */
    public function test_it_throws_if_unable_to_obtain_lock_after_timeout()
    {
        $this->config['bucket_lock'] = [
            'timeout_ms'    => 1,
            'retry_wait_ms' => 0.1,
            'lock_ttl_secs' => 5,
        ];
        $this->storage               = BucketStorageStub::neverGrantLock();
        $this->newSubject()->attemptRequest('warden.email.reset', 'anybody');
    }

    /**
     * @testWith [3]
     *           [1]
     */
    public function test_it_retries_taking_lock_within_timeout_and_clears_after_operation($attempt)
    {
        $this->config['bucket_lock'] = [
            'timeout_ms'    => 1,
            'retry_wait_ms' => 0.1,
            'lock_ttl_secs' => 5,
        ];
        $this->storage               = BucketStorageStub::grantLockOnAttempt($attempt);
        $this->newSubject()->attemptRequest('warden.email.reset', 'anybody');
        $this->storage->assertLocksReleased();
    }

    public function test_it_limits_once_bucket_is_full()
    {
        $this->config['bucket_types']['any'] = ['bucket_size' => 3, 'leak_time_seconds' => 10];
        $this->assertSame(
            [FALSE, FALSE, FALSE, TRUE],
            $this->checkIfLimitedTimes(4, 'any', 'anyone')
        );
    }

    public function test_it_allows_another_go_once_bucket_has_leaked()
    {
        $this->config['bucket_types']['any'] = ['bucket_size' => 3, 'leak_time_seconds' => 0.1];

        $results = $this->checkIfLimitedTimes(4, 'any', 'anyone');
        \usleep(100050);
        $results = $this->checkIfLimitedTimes(2, 'any', 'anyone', $results);
        \usleep(200050);
        $results = $this->checkIfLimitedTimes(3, 'any', 'anyone', $results);

        $this->assertSame(
            [
                // First 4 are ok
                FALSE,
                FALSE,
                FALSE,
                TRUE,
                // 1 drop has leaked so allow one
                FALSE,
                TRUE,
                // 2 more drops have leaked so allow two
                FALSE,
                FALSE,
                TRUE
            ],
            $results
        );
    }

    public function test_it_resets_once_bucket_has_leaked_to_empty()
    {
        $this->config['bucket_types']['any'] = ['bucket_size' => 2, 'leak_time_seconds' => 0.05];
        $results                             = $this->checkIfLimitedTimes(3, 'any', 'anything');
        \usleep(4 * 0.05 * 1000 * 1000);
        $results = $this->checkIfLimitedTimes(3, 'any', 'anything', $results);
        $this->assertSame(
            [
                // Bucket filled
                FALSE,
                FALSE,
                TRUE,
                // Now it drains so we only get one more bucketfull on return
                FALSE,
                FALSE,
                TRUE
            ],
            $results
        );
    }

    public function test_it_calculates_correct_time_to_next_availability()
    {
        $this->config['bucket_types']['any'] = ['bucket_size' => 2, 'leak_time_seconds' => 30];
        $this->newSubject()->attemptRequest('any', 'anything');
        $this->newSubject()->attemptRequest('any', 'anything');
        $result = $this->newSubject()->attemptRequest('any', 'anything');
        $this->assertTrue($result->isRateLimited());
        $this->assertEquals(
            new \DateTimeImmutable('+30 seconds'),
            $result->getNextAvailableTime(),
            'Should be basically right time',
            1
        );
    }

    public function test_it_stores_bucket_for_time_till_empty()
    {
        $this->config['bucket_types']['any'] = ['bucket_size' => 3, 'leak_time_seconds' => 60];
        $this->newSubject()->attemptRequest('any', 'anything');
        $this->storage->assertBucketStorageTTL(200);
    }

    protected function newSubject()
    {
        return new StorageBackedLeakyBucket(
            $this->storage,
            $this->config
        );
    }

    protected function setUp()
    {
        parent::setUp();
        $this->storage = new BucketStorageStub;
    }

    /**
     * @param int    $count
     * @param string $request_type
     * @param string $requester_id
     * @param array  $prev_results
     *
     * @return array
     */
    protected function checkIfLimitedTimes(
        $count,
        $request_type,
        $requester_id,
        array $prev_results = []
    ) {
        for ($i = 0; $i < $count; $i++) {
            $prev_results[] = $this->newSubject()
                ->attemptRequest($request_type, $requester_id)
                ->isRateLimited();
        }
        return $prev_results;
    }

}

class BucketStorageStub implements LeakyBucketStorage
{
    protected $allow_lock = TRUE;

    protected $locks = [];

    protected $buckets = [];

    protected $last_ttl_stored;

    public static function neverGrantLock()
    {
        $i             = new static;
        $i->allow_lock = FALSE;
        return $i;
    }

    public static function grantLockOnAttempt($attempts)
    {
        $i             = new static;
        $i->allow_lock = $attempts;
        return $i;
    }

    /**
     * @param string $key
     * @param int    $ttl
     *
     * @return bool
     */
    public function createLock($key, $ttl)
    {
        if (\is_int($this->allow_lock)) {
            $this->allow_lock--;
            $allow = ($this->allow_lock <= 0);
        } else {
            $allow = $this->allow_lock;
        }

        if ($allow) {
            $this->locks[$key] = isset($this->locks[$key]) ? $this->locks[$key]++ : 1;
        }

        return $allow;
    }

    /**
     * @param string $bucket_id
     *
     * @return void
     */
    public function releaseLock($bucket_id)
    {
        Assert::assertEquals(1, $this->locks[$bucket_id]);
        unset($this->locks[$bucket_id]);
    }

    public function assertLocksReleased()
    {
        Assert::assertEmpty($this->locks, 'Should have released lock');
    }

    /**
     * @param string $bucket_id
     *
     * @return array
     */
    public function fetchBucket($bucket_id, array $default)
    {
        if (isset($this->buckets[$bucket_id])) {
            return $this->buckets[$bucket_id];
        }

        return $default;
    }

    /**
     * @param string $bucket_id
     * @param array  $content
     * @param int    $ttl
     *
     * @return void
     */
    public function storeBucket($bucket_id, array $content, $ttl)
    {
        $this->buckets[$bucket_id] = $content;
        $this->last_ttl_stored     = $ttl;
    }

    public function assertBucketStorageTTL($expect)
    {
        Assert::assertSame($this->last_ttl_stored, $expect);
    }

}
