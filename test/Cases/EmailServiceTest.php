<?php 
namespace HyperfTest\Cases;

use HyperfTest\HttpTestCase;
use App\Service\Dao\UserDao;
use App\Service\MailService;
use Hyperf\Testing\TestCase;

/**
 * @internal
 * @coversNothing
 */
class EmailServiceTest extends HttpTestCase 
{
    public function testSendReg()
    {
        MailService::sendReg("afrstfnfy@10mail.org","asfaasfa");
    }
}