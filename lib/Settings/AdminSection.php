<?php
namespace OCA\Files_ExcludeDirs\Settings;

use OCP\Settings\IIconSection;
use OCP\IURLGenerator;

class AdminSection implements IIconSection {
    private IURLGenerator $urlGenerator;

    public function __construct(IURLGenerator $urlGenerator) {
        $this->urlGenerator = $urlGenerator;
    }

    public function getID(): string {
        return 'files_excludedirs';
    }

    public function getName(): string {
        return 'Exclude Directories'; // The text that will appear in the sidebar
    }

    public function getPriority(): int {
        return 90; // Position in the list
    }

    public function getIcon(): string {
        // Uses a default Nextcloud settings icon for the sidebar
        return $this->urlGenerator->imagePath('core', 'actions/settings-dark.svg');
    }
}