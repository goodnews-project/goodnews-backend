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
            'id' => 'required',
        ]);
		if ($validator->fails()){
			$errors = json_encode($validator->errors()->getMessages());
			throw new InboxException("validate body error: {$errors}");
        }	
	}



	#[ExecTimeLogger("inbox", 'inbox')]
	public function process()
	{
        if ($this->isRejectApType($this->payload['type'])) {
            return;
        }

		$username = $this->username;
		$headers = $this->request->getHeaders();
		$account = Account::where('username', $username)->whereNull('domain')->first();
        if (!$account) {
            return;
        }

		$r = $this->verifySignature($headers, $account);
        if (!$r) {
            return;
        }

        if (Helper::getSensitive($this->payload['object'], $this->payload['id'] ?? $this->payload['url']) && !SettingService::receive_remote_sensitive()) {
            return;
        }

        (new Inbox($headers, $account, $this->payload))->handle();
	}

    #[ExecTimeLogger("inbox", 'inbox')]
    public function processShareInbox()
    {
        if ($this->isRejectApType($this->payload['type'])) {
            return;
        }
        $headers = $this->request->getHeaders();

        $r = $this->verifySignature($headers);
        if (!$r) {
            return;
        }

        (new Inbox($headers, null, $this->payload))->handle();
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
        $host = parse_url($keyId, PHP_URL_HOST);

        // todo 先不屏蔽，在后台手动操作
//        if (\Hyperf\Support\make(DeliveryFailureTracker::class, ['urlOrHost' => $host])->isUnavailable()) {
//            throw new InboxException('host['.$host.'] is unavailable' );
//        }

        $relay = Relay::where('inbox_url', 'like', '%'.$host.'%')->first();
        if ($relay) {
            if ($relay->state == Relay::STATE_IDLE) {
                return false;
            }

            if ($relay->mode == Relay::MODE_WRITE_ONLY) {
                return false;
            }

            if ($this->payload['type'] != ActivityPubActivityInterface::TYPE_ACCEPT) {
                $relay->state = Relay::STATE_ACCEPTED;
                $relay->save();
            }
        }

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
