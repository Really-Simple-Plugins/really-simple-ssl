<?php
namespace ReallySimplePlugins\RSS\Core\Providers;

use ReallySimplePlugins\RSS\Core\Support\Helpers\Storage;

class ConfigServiceProvider extends Provider
{
    protected array $provides = [
        'config',
    ];

    /**
     * Provides config in the container.
     * @example All: $this->app->config->get()
     * @example Name: $this->app->config->getString('env.plugin.name')
     */
    public function provideConfig(): Storage
    {
        return $this->storageFromPath(dirname(__FILE__, 3).'/config', true);
    }

    /**
     * Return a config file as a {@see Storage} instance. If path is a
     * directory, it will merge all the files in the directory.
     *
     * @param bool $prefixWithFileName Can be used to prefix the keys with the
     * filename when loading a directory. Can be useful to bundle the config
     * data of a file under the filename which makes it easier to retrieve a
     * single fields config.
     *
     * @throws \InvalidArgumentException
     */
    private function storageFromPath(string $path, bool $prefixWithFileName = false): Storage
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("Unloadable configuration file " . esc_html($path) . " provided.");
        }

        $data = [];

        if (is_dir($path)) {

            // This makes sure that if the dir contains another dir, it will
            // load the files in that dir as well.
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
                    continue;
                }

                $fileData = require $file;

                if ($prefixWithFileName) {
                    $fileName = pathinfo($file, PATHINFO_FILENAME);
                    $fileData = [
                        $fileName => $fileData
                    ];
                }

                $data = array_merge($data, $fileData);
            }
        } else {
            $data = require $path;
        }

        return new Storage($data);
    }

}