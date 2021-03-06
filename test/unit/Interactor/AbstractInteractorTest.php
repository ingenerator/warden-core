<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\unit\Ingenerator\Warden\Core\Interactor;


use Ingenerator\Warden\Core\Interactor\AbstractResponse;
use PHPUnit\Framework\TestCase;

abstract class AbstractInteractorTest extends TestCase
{
    
    protected function assertFailsWithCode($code, AbstractResponse $result)
    {
        $this->assertSame($code, $result->getFailureCode());
        $this->assertTrue($result->isFailureCode($code));
    }

    protected function assertSuccessful(AbstractResponse $response)
    {
        $this->assertTrue($response->wasSuccessful(), 'Expect success, got '.$response->getFailureCode());
    }

}
