<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Core\RateLimit;


class ApcuBucketStorage implements LeakyBucketStorage
{

    /**
     * @param string $bucket_id
     * @param int    $ttl
     *
     * @return bool
     */
    public function createLock($bucket_id, $ttl)
    {
        return apcu_add($this->prefixKey($bucket_id.':lock'), TRUE, $ttl);
    }

    /**
     * @param string $bucket_id
     *
     * @return void
     */
    public function releaseLock($bucket_id)
    {
        apcu_delete($this->prefixKey($bucket_id.':lock'));
    }

    /**
     * @param string $bucket_id
     *
     * @return array
     */
    public function fetchBucket($bucket_id, array $default)
    {
        $bucket = apcu_fetch($this->prefixKey($bucket_id), $success);
        if ($success) {
            return $bucket;
        } else {
            return $default;
        }
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
        apcu_store($this->prefixKey($bucket_id), $content, $ttl);
    }

    protected function prefixKey($key)
    {
        return 'lb:'.$key;
    }

}
