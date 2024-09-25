<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\Account;
use App\Model\Notification;
use App\Model\Status;
use App\Service\NotificationService;
use App\Service\StatusesService;
use App\Service\WellKnownService;
use App\Util\Log;
use Carbon\Carbon;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\ConsoleOutput;
use function Hyperf\Support\make;

#[Command]
#[Crontab(rule: "13 */2 * * *", name: "update-notifications", callback: "executeCrontab", memo: "")]
class UpdateNotificationBotCommand extends HyperfCommand
{
    #[Inject]
    protected StatusesService $statusesService;

    #[Inject]
    protected NotificationService $notificationService;

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('bot:update_notifications');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Update notifications bot');
    }

    public function executeCrontab()
    {
        $this->output = new ConsoleOutput();
        $this->handle();
    }

    public function handle()
    {
        $this->createStatus();
    }

    public function createStatus()
    {
        $account = Account::where('acct', 'update_notifications')->first();
        if (empty($account)) {
            $account = $this->createAccount();
        }

        $client = make(ClientFactory::class)->create([
            'timeout' => 15
        ]);

        $notify = Notification::where('account_id', $account->id)
            ->where('notify_type', Notification::NOTIFY_TYPE_SYSTEM)
            ->where('status_id', '>', 0)
            ->latest()
            ->first();
        $version = '';
        if ($notify) {
            $status = Status::findOrFail($notify->status_id);
            $version = $status->spoiler_text;
        }
        try {
            $r = $client->post('https://instance.good.news/version-check', ['form_params' => ['version' => $version ?: WellKnownService::NODE_VERSION]]);
            $data = json_decode($r->getBody()->getContents(), true);
            if (empty($data['update'])) {
                return;
            }
            $content = $data['version']['description'] ?? '';
            $data = $this->statusesService->create($account->id, $content, [
                'published_at' => Carbon::now()->toDateTimeString(),
                'spoiler_text' => $data['version']['version'] ?? null,
                'scope' => Status::SCOPE_PRIVATE,
                'url' => $data['version']['url'] ?? null
            ]);

            $this->notificationService->versionUpgrade($account->id, $data['id']);
        } catch (\Exception $e) {
            Log::error('Checking for version exception:'.$e->getMessage());
            return;
        }
    }

    public function createAccount()
    {
        $pkiConfig = [
            'digest_alg'       => 'sha512',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $pki = openssl_pkey_new($pkiConfig);
        openssl_pkey_export($pki, $pkiPrivate);
        $pkiPublic = openssl_pkey_get_details($pki);
        $username = 'update_notifications';
        return Account::create([
            'acct'              => $username,
            'username'          => $username,
            'domain'            => null,
            'display_name'      => 'Update Notifications',
            'note'              => 'Unofficial goodnews update notifications.',
            'profile_image'     => null,
            'avatar'            => null,
            'avatar_remote_url' => null,
            'is_activate'       => true,
            'private_key'       => $pkiPrivate,
            'public_key'        => $pkiPublic['key'],
            'actor_type'        => Account::ACTOR_TYPE_SERVICE,
            'fields'            => [
                ['name' => 'goodnews RELEASES ON GITHUB', "type" => "PropertyValue", "value" => "https://github.com/goodnews-project/goodnews-backend"],
                ['name' => 'Contact Us', "type" => "PropertyValue", "value" => "https://good.news/about"],
            ]
        ]);
    }
}
