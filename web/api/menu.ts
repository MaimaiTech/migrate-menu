/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { ResponseStruct } from '#/global'

export interface MenuVo {
  id?: number
  parent_id?: number
  name?: string
  path?: string
  meta?: Record<string, any>
  component?: string
  redirect?: string
  status?: number
  sort?: number
  remark?: string
  btnPermission?: MenuVo[]
  children?: MenuVo[]
  [key: string]: any
}

export interface MenuImportResult {
  total: number
  success: number
  failed: number
  skipped: number
  errors: Array<{
    menu: string
    error: string
  }>
}

/**
 * 获取菜单列表
 */
export function getMenuList(): Promise<ResponseStruct<MenuVo[]>> {
  return useHttp().get('/admin/menu-migrate/list')
}

/**
 * 导入菜单配置
 * @param file 上传的JSON文件
 * @param overwrite 是否覆盖现有菜单
 */
export function importMenu(file: File, overwrite: boolean = false): Promise<ResponseStruct<MenuImportResult>> {
  const formData = new FormData()
  formData.append('file', file)
  formData.append('overwrite', overwrite ? '1' : '0')

  return useHttp().post('/admin/menu-migrate/import', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  })
}

/**
 * 格式化菜单数据用于导出
 * @param menus 菜单数据
 */
export function formatMenusForExport(menus: MenuVo[]): any[] {
  return menus.map(menu => ({
    name: menu.name,
    path: menu.path,
    component: menu.component,
    redirect: menu.redirect,
    status: menu.status,
    sort: menu.sort,
    remark: menu.remark,
    meta: menu.meta || {},
    children: menu.children && menu.children.length > 0 
      ? formatMenusForExport(menu.children) 
      : undefined
  })).filter(menu => menu.name) // 过滤掉无效菜单
}

/**
 * 导出菜单配置到JSON文件
 * @param menus 菜单数据
 * @param filename 文件名（可选）
 */
export function exportMenusToJson(menus: MenuVo[], filename?: string): void {
  try {
    const exportData = {
      version: '1.0.0',
      export_time: new Date().toISOString().replace('T', ' ').slice(0, 19),
      export_by: 'MineAdmin Menu Migration Plugin',
      total_count: menus.length,
      menus: formatMenusForExport(menus)
    }
    
    // 生成JSON字符串
    const jsonString = JSON.stringify(exportData, null, 2)
    
    // 创建Blob并下载
    const blob = new Blob([jsonString], { type: 'application/json;charset=utf-8' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    
    // 生成默认文件名
    const defaultFilename = filename || `menu_config_${new Date().toISOString().slice(0, 10).replace(/-/g, '')}_${new Date().toTimeString().slice(0, 8).replace(/:/g, '')}.json`
    link.download = defaultFilename
    
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error) {
    throw new Error(`Export failed: ${error instanceof Error ? error.message : String(error)}`)
  }
}