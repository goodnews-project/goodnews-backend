<?php

namespace App\Service\Activitypub;

use App\Aspect\Annotation\ExecTimeLogger;
use App\Entity\Contracts\ActivityPubActivityInterface;
use App\Exception\InboxException;
use App\Model\Account;
use App\Model\Relay;
use App\Service\DeliveryFailureTracker;
use App\Service\SettingService;
use App\Util\Log;
use Carbon\Carbon;
use App\Util\ActivityPub\{
	Helper,
	HttpSignature,
	Inbox
};
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use function Hyperf\Support\make;

class ProcessInboxValidator
{

	protected $payload = [];

	#[Inject]
    protected ValidatorFactoryInterface  $validationFactory;

	public function __construct(protected string|null $username,protected RequestInterface $request)
	{
		$this->preparData();
	}

	protected function preparData()
	{
		//validate header 
		$headers = $this->request->getHeaders();
		$validator = $this->validationFactory->make($headers,[
            'signature' => 'required',
            'date'      => 'required',
        ]);
		if ($validator->fails()){
			$errors = json_encode($validator->errors()->getMessages());
			throw new InboxException("validate header error: {$errors}");
        }

		$this->payload = json_decode($this->request->getBody()->getContents(),true);
		$validator = $this->validationFactory->make($this->payload,[
            '@context' => 'nullable',
            'id' => 'required|url',
            'type' => 'required',
            'actor' => 'required',
            'to' => 'required',
            'object' => 'required',
            'cc' => 'nullable',
        ]);
		if ($validator->fails()){
			$errors = json_encode($validator->errors()->getMessages());
			throw new InboxException("validate body error: {$errors}");
        }	
	}

    public function getValidated()
    {
        return $this->payload;
    }

	#[ExecTimeLogger("inbox", 'inbox')]
	public function verify()
	{
        if ($this->isRejectApType($this->payload['type'])) {
            return false;
        }

		$username = $this->username;
		$headers = $this->request->getHeaders();
		$account = Account::where('username', $username)->whereNull('domain')->first();
        if (!$account) {
            throw new InboxException('account not exists, username:'.$username);
        }

		$r = $this->verifySignature($headers, $account);
        if ($r === false) {
            throw new InboxException('inbox verify signature fail, username:'.$username);
        }
        return true;
	}

    #[ExecTimeLogger("inbox", 'inbox')]
    public function shareInboxVerify()
    {
        if ($this->isRejectApType($this->payload['type'])) {
            return false;
        }
        $headers = $this->request->getHeaders();

        $r = $this->verifySignature($headers);
        if ($r === false) {
            throw new InboxException('share inbox verify signature fail');
        }
        return true;
    }

    private function isRejectApType($type): bool
    {
        $apTypes = \Hyperf\Support\env('ACTIVITYPUB_REJECT_TYPE', '');
        if (empty($apTypes)) {
            return false;
        }
        $apTypeArr = explode(',', $apTypes);
        return in_array($type, $apTypeArr);
    }


	#[ExecTimeLogger("inbox", 'inbox')]
	protected function verifySignature($headers, $account = null)
	{
		$signature = is_array($headers['signature']) ? $headers['signature'][0] : $headers['signature'];
		$date = is_array($headers['date']) ? $headers['date'][0] : $headers['date'];
		
		if(!Carbon::now()->parse($date)->gt(Carbon::now()->subDays(1)) ||
		   !Carbon::now()->parse($date)->lt(Carbon::now()->addDays(1))
	   ) {
			throw new InboxException("date expires, date:{$date}" );
		}

		$signatureData = HttpSignature::parseSignatureHeader($signature);

		if(!isset($signatureData['keyId'], $signatureData['signature'], $signatureData['headers']) || isset($signatureData['error'])) {
			throw new InboxException("signatureData exception" );	
		}

		$keyId = Helper::validateUrl($signatureData['keyId']);
		$actor = Account::where('public_key_uri', $keyId)->firstOr(function (){
			$actorUrl = Helper::pluckval($this->payload['actor']);
			return Helper::accountFirstOrNew($actorUrl);
		});

		if(!$actor) {
			throw new InboxException('deleted or not find actor by public_key_uri(keyId:'.$keyId.')' );
		}

		$pkey = openssl_pkey_get_public($actor->public_key);
		if(!$pkey) {
			throw new InboxException('not get public pkey' );
		}
		$inboxPath = $account ? "/users/{$account->username}/inbox" : '/inbox';
		[$verified, $headers] = HttpSignature::verify($pkey, $signatureData, $headers, $inboxPath);
        return $verified == 1;
	}
}
