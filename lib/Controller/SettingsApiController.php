<?php
namespace OCA\Files_ExcludeDirs\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IConfig;
use OCA\Files_ExcludeDirs\Service\CleanupService;

class SettingsApiController extends Controller {
    private IConfig $config;
    private CleanupService $cleanupService;

    public function __construct(string $AppName, IRequest $request, IConfig $config, CleanupService $cleanupService) {
        parent::__construct($AppName, $request);
        $this->config = $config;
        $this->cleanupService = $cleanupService;
    }

    /**
     * @NoCSRFRequired
     * @AdminRequired
     */
    public function getSettings(): DataResponse {
        $patterns = json_decode($this->config->getAppValue('files_excludedirs', 'exclude', '[".snapshot"]'), true);
        return new DataResponse(['patterns' => $patterns]);
    }

    /**
     * @AdminRequired
     */
    public function saveSettings(array $patterns): DataResponse {
        // Ensure no empty patterns are saved
        $cleanPatterns = array_values(array_filter(array_map('trim', $patterns)));
        $this->config->setAppValue('files_excludedirs', 'exclude', json_encode($cleanPatterns));
        return new DataResponse(['status' => 'success', 'patterns' => $cleanPatterns]);
    }

    /**
     * @AdminRequired
     */
    public function previewCleanup(array $patterns): DataResponse {
        $results = $this->cleanupService->preview($patterns);
        return new DataResponse($results);
    }

    /**
     * @AdminRequired
     */
    public function runCleanup(array $patterns): DataResponse {
        $deleted = $this->cleanupService->cleanup($patterns);
        return new DataResponse(['status' => 'success', 'deleted' => $deleted]);
    }
}