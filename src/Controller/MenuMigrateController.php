<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace Plugin\MaimaiTech\MigrateMenu\Controller;

use App\Http\Admin\Controller\AbstractController;
use App\Http\Admin\Middleware\PermissionMiddleware;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use App\Http\CurrentUser;
use Hyperf\HttpMessage\Upload\UploadedFile;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\HyperfServer;
use Hyperf\Swagger\Annotation\JsonContent;
use Hyperf\Swagger\Annotation\Post;
use Hyperf\Swagger\Annotation\RequestBody;
use Mine\Access\Attribute\Permission;
use Mine\Swagger\Attributes\PageResponse;
use Mine\Swagger\Attributes\ResultResponse;
use Plugin\MaimaiTech\MigrateMenu\Request\MenuImportRequest;
use Plugin\MaimaiTech\MigrateMenu\Service\MenuMigrateService;
use Psr\Http\Message\ResponseInterface;

#[HyperfServer(name: 'http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class MenuMigrateController extends AbstractController
{
    public function __construct(
        private readonly MenuMigrateService $service,
        private readonly CurrentUser $user
    ) {}

    #[Get(
        path: '/admin/menu-migrate/list',
        operationId: 'menuMigrateList',
        summary: '获取菜单列表',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['菜单迁移']
    )]
    #[Permission(code: 'system:menu:migrate')]
    #[ResultResponse(instance: new Result())]
    public function getMenuList(): Result
    {
        return $this->success(data: $this->service->getMenuList());
    }

    #[Get(
        path: '/admin/menu-migrate/export',
        operationId: 'menuMigrateExport',
        summary: '导出菜单配置',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['菜单迁移']
    )]
    #[Permission(code: 'system:menu:migrate')]
    public function export(RequestInterface $request): ResponseInterface
    {
        $params = $request->all();
        return $this->service->exportMenu($params);
    }

    #[Post(
        path: '/admin/menu-migrate/import',
        operationId: 'menuMigrateImport',
        summary: '导入菜单配置',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['菜单迁移']
    )]
    #[RequestBody(
        content: new JsonContent(ref: MenuImportRequest::class, title: '导入菜单配置')
    )]
    #[PageResponse(instance: new Result())]
    #[Permission(code: 'system:menu:migrate')]
    public function import(MenuImportRequest $request): Result
    {
        $file = $request->file('file');
        if (! $file instanceof UploadedFile) {
            return $this->error('请上传有效的JSON文件');
        }

        $overwrite = (bool) $request->input('overwrite', false);
        $result = $this->service->importMenu($file, $overwrite, $this->user->id());

        return $this->success($result, '菜单导入完成');
    }
}
