<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Controller;

use App\Service\WellKnownService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\RequestMapping;

class WellKnownController extends AbstractController
{
    #[Inject]
    private WellKnownService $knownService;

    public function webfinger()
    {
        $resource = $this->request->input('resource');
        if (empty($resource)) {
            return $this->response->raw('')->withStatus(400);
        }

        $data = $this->knownService->webfinger($resource);
        return $this->response->json($data)->withHeader('Content-Type', 'application/jrd+json;charset=utf-8');
    }

    public function nodeinfoRel()
    {
        return $this->knownService->nodeinfoRel();
    }

    public function nodeinfo2()
    {
        return $this->knownService->nodeinfo2();
    }

    public function hostMeta()
    {
        return $this->response->raw($this->knownService->hostMeta())->withHeader('Content-Type', 'application/xrd+xml');
    }
}
