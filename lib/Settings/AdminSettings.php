<?php
namespace OCA\Files_ExcludeDirs\Settings;

use OCP\Settings\ISettings;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Util;

class AdminSettings implements ISettings {
    public function getForm() {
        // This tells Nextcloud to load our JavaScript file (js/settings.js)
        \OCP\Util::addScript('files_excludedirs', 'settings');
        
        // Use 'blank' so Nextcloud sends ONLY the raw HTML snippet without the webpage wrapper!
        return new TemplateResponse('files_excludedirs', 'settings-admin', [], 'blank');
    }

    public function getSection() {
        return 'files_excludedirs'; // This maps it to the new sidebar entry!
    }

    public function getPriority() {
        return 50; // Order in the menu
    }
}