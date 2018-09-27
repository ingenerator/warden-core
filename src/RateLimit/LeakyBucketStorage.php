<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\RateLimit;


interface LeakyBucketStorage
{

    /**
     * @param string $key
     * @param int    $ttl
     *
     * @return bool
     */
    public function createLock($key, $ttl);

    /**
     * @param string $bucket_id
     *
     * @return void
     */
    public function releaseLock($bucket_id);

    /**
     * @param string $bucket_id
     *
     * @return array
     */
    public function fetchBucket($bucket_id, array $default);

    /**
     * @param string $bucket_id
     * @param array  $content
     * @param int    $ttl
     *
     * @return void
     */
    public function storeBucket($bucket_id, array $content, $ttl);
}
