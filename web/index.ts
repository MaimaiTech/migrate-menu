/**
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://github.com/mineadmin
 */
import type { Plugin } from '#/global'

const pluginConfig: Plugin.PluginConfig = {
  install() {
    console.log('Menu Migration Plugin has started')
  },
  config: {
    enable: true,
    info: {
      name: 'maimaitech/menu-migrate',
      version: '1.0.0',
      author: 'MaimaiTech',
      description: 'Menu configuration migration tool for importing and exporting menu structures',
    },
  },
  views: []
}

export default pluginConfig
