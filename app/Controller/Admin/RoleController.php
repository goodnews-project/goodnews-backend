<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Model\Admin\Permission;
use App\Model\Admin\PermissionGroup;
use App\Model\Admin\Role;
use App\Model\Admin\RolePermission;
use App\Model\Admin\UserRole;
use App\Model\User;
use App\Request\Admin\RoleRequest;
use Hyperf\DbConnection\Db;
use Hyperf\Swagger\Annotation as OA;

#[OA\HyperfServer('http')]
class RoleController extends AbstractController
{
    #[OA\Get('/_api/admin/roles',summary:'获取角色列表',tags:['admin', '管理角色'])]
    #[OA\Response(
        response: 200,
        description: ''
    )]
    public function index()
    {
        return Role::withCount([
            'users',
            'permissions'
        ])->get();
    }
    #[OA\Get('/_api/admin/roles/create',summary:'获取所有权限(用于新建角色)',tags:['admin', '管理角色'])]
    public function create()
    {
        $permissions = Permission::with('permissionGroup')->get();
        return $permissions->groupBy('permissionGroup.name');
    }

    #[OA\Put(path:'/_api/admin/roles/{id}',summary:'编辑角色',tags:['admin','管理角色'])]
    #[OA\Parameter(name: 'role.name', description: '角色名称{role:{"name":"123"}}', in : 'body', required: true)]
    #[OA\Parameter(name: 'permission.ids', description: '权限ID数组 {permission:{"ids":[1,2,3]}}', in : 'body', required: true)] 
    public function update($id,RoleRequest $roleRequest)
    {
        $payload = $roleRequest->validated();
        $role = Role::findOrFail($id);
        Db::transaction(function()use($id,$payload,$role){
            $role->update($payload['role']);
            RolePermission::where('role_id',$id)->delete();
            $this->permissionIdsToRole($payload['permission']['ids'],$id);
        });
    }

    #[OA\Get(path:'/_api/admin/roles/{id}/edit',summary:"编辑角色信息",tags:['admin','管理角色'])]
    public function edit($id)
    {
        $permissions = Permission::with('permissionGroup')->get();
        
        $permissionIds = RolePermission::where('role_id',$id)->pluck('permission_id')->toArray();
        $permissions = $permissions->map(function ($permission) use($permissionIds){
            $permission['is_check'] = in_array($permission->id,$permissionIds);
            return $permission;
        });

        return $permissions->groupBy('permissionGroup.name');
    }

    #[OA\Post(path:'/_api/admin/roles',summary:'新增角色',tags:['admin','管理角色'])]
    #[OA\Parameter(name: 'role.name', description: '角色名称{role:{"name":"123"}}', in : 'body', required: true)]
    #[OA\Parameter(name: 'permission.ids', description: '权限ID数组 {permission:{"ids":[1,2,3]}}', in : 'body', required: true)] 
    public function store(RoleRequest $roleRequest)
    {
        $payload = $roleRequest->validated();
        Db::transaction(function()use($payload){
            $role = Role::create($payload['role']); 
            $this->permissionIdsToRole($payload['permission']['ids'],$role->id);
        });
    }
    
    private function permissionIdsToRole($permissionIds,$roleId){
        if(empty($permissionIds)){
            return 0;
        }
        return RolePermission::insert(
            array_map(fn($id) => [
                'role_id'       => $roleId,
                'permission_id' => $id
            ],$permissionIds)
        );
    }

}
