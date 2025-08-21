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

namespace Plugin\MaimaiTech\MigrateMenu\Request;

use App\Http\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\Validation\Request\FormRequest;

class MenuImportRequest extends FormRequest
{
    use NoAuthorizeTrait;

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:json',
            'overwrite' => 'sometimes|boolean',
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => '菜单文件',
            'overwrite' => '是否覆盖',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => '请选择要导入的菜单文件',
            'file.file' => '上传的必须是文件',
            'file.mimes' => '只支持JSON格式的文件',
            'overwrite.boolean' => '覆盖选项必须是布尔值',
        ];
    }
}
