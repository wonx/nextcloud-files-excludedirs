<?php
/**
 * @copyright 2016 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Alan J. Pippin <alan@pippins.net>
 * @author Guy Elsmore-Paddock <guy@inveniem.com>
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

namespace OCA\Files_ExcludeDirs\Wrapper;

use Icewind\Streams\IteratorDirectory;
use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Files\InvalidDirectoryException;
use Webmozart\Glob\Glob;

/**
 * A wrapper around the Nextcloud filesystem that filters out unwanted folders.
 */
class Exclude extends Wrapper {
        /**
         * @var string[] Directories to exclude
         */
        private $exclude;

        /**
         * @param array $parameters
         */
        public function __construct($parameters) {
                parent::__construct($parameters);

                $this->exclude = $parameters['exclude'];
        }

        /**
         * {@inheritdoc}
         */
        public function verifyPath(string $path, string $fileName): void {
                if ($this->excludedPath($path) ||
                                $this->excludedPath(implode(DIRECTORY_SEPARATOR, [$path, $fileName]))) {
                        throw new InvalidDirectoryException();
                }

                parent::verifyPath($path, $fileName);
        }

        /**
         * Check if a particular path matches the pattern for a directory to ignore.
         *
         * @param string $path
         *   The absolute or relative path to check.
         *
         * @return bool
         *   true if the path should be excluded/skipped, or false if it should be
         *   processed.
         */
        private function excludedPath(string $path): bool {
                if ($path === '') {
                        return false;
                }

                foreach ($this->exclude as $rule) {
                        // glob requires all paths to be absolute so we put /'s in front of them
                        if (strpos($rule, '/') !== false) {
                                $rule = '/' . rtrim($rule, '/');

                                if (Glob::match('/' . $path, $rule)) {
                                        return true;
                                }
                        } else {
                                $parts = explode('/', $path);
                                $rule = '/' . $rule;

                                foreach ($parts as $part) {
                                        if (Glob::match('/' . $part, $rule)) {
                                                return true;
                                        }
                                }
                        }
                }

                return false;
        }

        /**
         * {@inheritdoc}
         */
        public function file_exists(string $path): bool {
                if ($this->excludedPath($path)) {
                        return false;
                }

                return parent::file_exists($path);
        }

        /**
         * {@inheritdoc}
         */
        public function opendir(string $path) {
                $directoryIterator = $this->iterateDirectory($path);

                if ($directoryIterator) {
                        $filteredDirectory =
                                new \CallbackFilterIterator(
                                        $directoryIterator,
                                        function ($name) use ($path) {
                                                return !$this->excludedPath($path . '/' . $name);
                                        }
                                );

                        $filteredDirectory->rewind();

                        return IteratorDirectory::wrap($filteredDirectory);
                }

                return false;
        }

        private function iterateDirectory(string $path) {
                if ($this->excludedPath($path)) {
                        return false;
                }

                $handle = $this->storage->opendir($path);

                while ($file = readdir($handle)) {
                        if ($file !== '.' && $file !== '..') {
                                yield $file;
                        }
                }

                return false;
        }

        /**
         * {@inheritdoc}
         */
        public function getMetaData(string $path): ?array {
                if ($this->excludedPath($path)) {
                        return null;
                }

                return $this->getWrapperStorage()->getMetaData($path);
        }

        /**
         * {@inheritdoc}
         */
        public function stat(string $path): array|false {
                if ($this->excludedPath($path)) {
                        return false;
                }
                return parent::stat($path);
        }

        /**
         * {@inheritdoc}
         */
        public function is_dir(string $path): bool {
                if ($this->excludedPath($path)) {
                        return false;
                }
                return parent::is_dir($path);
        }

        /**
         * {@inheritdoc}
         */
        public function is_file(string $path): bool {
                if ($this->excludedPath($path)) {
                        return false;
                }
                return parent::is_file($path);
        }

        /**
         * {@inheritdoc}
         */
        public function getDirectoryContent(string $directory): \Traversable {
                if ($this->excludedPath($directory)) {
                        return; // Generator returns empty Traversable
                }

                $content = parent::getDirectoryContent($directory);
                
                foreach ($content as $item) {
                        $name = '';
                        if (is_array($item)) {
                                $name = $item['name'] ?? '';
                        } elseif (is_object($item) && method_exists($item, 'getName')) {
                                $name = $item->getName();
                        } else {
                                $name = $item->name ?? '';
                        }

                        if ($name !== '') {
                                $fullPath = ltrim($directory . '/' . $name, '/');
                                if ($this->excludedPath($fullPath)) {
                                        continue;
                                }
                        }
                        yield $item;
                }
        }
}