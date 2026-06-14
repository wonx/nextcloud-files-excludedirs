<?php

/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Files_ExcludeDirs\AppInfo;
require_once __DIR__ . '/../../vendor/autoload.php';

use OCA\Files_ExcludeDirs\Wrapper\Exclude;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;

class Application extends App implements IBootstrap {
    public const APP_ID = 'files_excludedirs';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {}

    public function boot(IBootContext $context): void {
        // We connect to 'preSetup' using Nextcloud's native Hook manager.
        // This ensures our wrapper is re-registered immediately after Nextcloud
        // wipes and resets the storage loader during setup!
        \OC_Hook::connect('OC_Filesystem', 'preSetup', $this, 'setupWrapper');
    }

    /**
     * Callback for 'preSetup' hook
     */
    public function setupWrapper($params = []): void {
        $server = $this->getContainer()->getServer();
        $config = $server->get(\OCP\IConfig::class);

        \OC\Files\Filesystem::addStorageWrapper('files_excludedirs', function ($mountPoint, $storage) use ($config) {
            $exclude = json_decode(
                $config->getAppValue('files_excludedirs', 'exclude', '[".snapshot"]')
            );
            return new Exclude(['storage' => $storage, 'exclude' => $exclude]);
        });
    }
}