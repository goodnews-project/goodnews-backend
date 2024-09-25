<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\Account;
use App\Model\Hashtag;
use App\Model\Status;
use App\Model\StatusHashtag;
use Carbon\Carbon;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Hyperf\Crontab\Annotation\Crontab;
use Symfony\Component\Console\Output\ConsoleOutput;
use function Hyperf\Support\env;

#[Command]
#[Crontab(rule: "17 0 * * *", name: "generate-sitemap", callback: "executeCrontab", memo: "")]
class GenerateSitemapCommand extends HyperfCommand
{

    protected array $urls = [];

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('gen:sitemap');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Generate sitemap');
    }

    public function executeCrontab()
    {
        $this->output = new ConsoleOutput();
        $this->handle();
    }

    public function handle()
    {
        // create sitemap for user
        Account::where('status_count', '>', 0)
            ->latest('status_count')
            ->limit(1000)
            ->get()
            ->each(function (Account $item) {
                $this->addUrl(getApHostUrl().'/user/'.$item->acct, $item->created_at->format('Y-m-d'));
            });

        // create sitemap for status
        Status::where('fave_count', '>', 0)
            ->latest('fave_count')
            ->limit(2000)
            ->get()
            ->each(function (Status $item) {
                $this->addUrl(getApHostUrl().'/User/'.$item->account->acct.'/status/'.$item->id, $item->created_at->format('Y-m-d'));
            });

        // create sitemap for hashtag
        StatusHashtag::selectRaw('hashtag_id, count(1) as n')
            ->groupBy(['hashtag_id'])
            ->latest('n')
            ->having('n', '>', 0)
            ->limit(1000)
            ->get()
            ->each(function (StatusHashtag $item) {
                $hashtag = Hashtag::find($item->hashtag_id);
                if (!$hashtag) {
                    return;
                }
                $this->addUrl(getApHostUrl().'/explore/hashtag/'.urlencode($hashtag->name), Carbon::now()->format('Y-m-d'));
            });

        $this->createSitemap();
    }

    public function createSitemap()
    {
        $urls = implode("\n", $this->urls());
        $sitemapContent = <<<SITEMAP
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
$urls
</urlset>
SITEMAP;
        $output = BASE_PATH.'/public/sitemap.xml';
        $r = file_put_contents($output, $sitemapContent);
        $this->info('create sitemap to path:'.$output.',filesize:'.$r);
    }

    public function addUrl($loc, $lastmod, $changefreq = 'daily', $priority = '0.5')
    {
        $this->urls[] = <<<URL
<url>
    <loc>$loc</loc>
    <lastmod>$lastmod</lastmod>
    <changefreq>$changefreq</changefreq>
    <priority>$priority</priority>
</url>
URL;

    }

    public function urls(): array
    {
        return $this->urls;
    }
}
