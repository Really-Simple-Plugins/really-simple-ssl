<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Managers;

use ReallySimplePlugins\RSS\Core\Features\AbstractLoader;
use ReallySimplePlugins\RSS\Core\Interfaces\FeatureInterface;

/**
 * This manager dynamically fetches the features of the plugin. It differs from
 * other manager classes due to this nature. By preventing any class usage of
 * features we prevent composer from loading the feature file entirely until
 * first use. This prevents overhead from loading features that are no longer
 * needed. We prevent loading feature files by utilizing the
 * {@see AbstractLoader} class at {@see FeatureManager:92}
 */
final class FeatureManager extends AbstractManager
{
    private const PRO_FEATURE_HANDLE = 'Pro:';

    /**
     * @inheritDoc
     */
    public function isRegistrable(object $class): bool
    {
        return $class instanceof FeatureInterface;
    }

    /**
     * @inheritDoc
     */
    public function registerClass(object $class): void
    {
        $class->register();
    }

    /**
     * @inheritDoc
     */
    public function afterRegister(): void
    {
        do_action('rss_core_features_loaded');
    }

    /**
     * Register and load all features from the src/features directory. This
     * method automatically loads all classes from the features directory and
     * injects the dependency classes into the Controller class if they exist.
     * @uses do_action rss_core_features_loaded
     */
    public function registerFeatures(): void
    {
        $featureClasses = $this->getFeatureClasses();
        $this->register($featureClasses);
    }

    /**
     * Dynamically build and then return an array of feature classes that are
     * saved in the features path of the plugin.
     */
    public function getFeatureClasses(): array
    {
        $features = $this->getFeatures();
        $featureClasses = [];

        foreach ($features as $featureName) {

            $needsPro = strpos($featureName, self::PRO_FEATURE_HANDLE) !== false;
            if ($needsPro && !$this->app->config->getBoolean('env.plugin.pro')) {
                continue; // Pro not installed, don't register pro features
            }

            if ($needsPro) {
                $featureName = substr($featureName, strlen(self::PRO_FEATURE_HANDLE));
            }

            // Check if the feature directory exists
            $featuresPath = $this->getFeaturePath($featureName, $needsPro);
            if (!is_dir($featuresPath)) {
                continue;
            }

            // Get the feature namespace
            $prefix = $this->getFeatureNamespace($featureName, $needsPro) . $featureName;

            // Get the {FeatureName}Loader class for the feature
            if (class_exists($prefix . 'Loader') === false) {
                continue;
            }

            $loader = $this->app->make($prefix . 'Loader', false, false);
            if (!$loader->isEnabled() || !$loader->inScope()) {
                continue;
            }

            // The controller is the backbone of a feature
            $featureClasses[] = $prefix . 'Controller';
        };

        return $featureClasses;
    }

    /**
     * Get all feature directory names. Includes "Pro" features prefixed
     * with {@see PRO_FEATURE_HANDLE}.
     */
    private function getFeatures(): array
    {
        $featuresPath = $this->app->config->getString('env.plugin.feature_path');
        $features = [];

        foreach (new \DirectoryIterator($featuresPath) as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isDir()) {
                continue;
            }

            $proEnabled = $this->app->config->getBoolean('env.plugin.pro');
            $skipPro = ($proEnabled === false && $fileInfo->getFilename() === 'Pro');
            if ($skipPro) {
                continue;
            }

            if ($fileInfo->getFilename() === 'Pro') {
                foreach (new \DirectoryIterator($fileInfo->getPathname()) as $proInfo) {
                    if ($proInfo->isDot() || !$proInfo->isDir()) {
                        continue;
                    }
                    $features[] = self::PRO_FEATURE_HANDLE . $proInfo->getFilename();
                }
                continue;
            }

            $features[] = $fileInfo->getFilename();
        }

        return $features;
    }

    /**
     * Get the feature path based on the feature name and if it needs the Pro
     * version.
     */
    private function getFeaturePath(string $featureName, bool $needsPro): string
    {
        return $this->app->config->getString('env.plugin.feature_path') . ($needsPro ? 'Pro/' : '') . $featureName . '/';
    }

    /**
     * Get the feature namespace.
     */
    private function getFeatureNamespace(string $featureName, bool $needsPro = false): string
    {
        return 'ReallySimplePlugins\RSS\Core\Features\\' . ($needsPro ? 'Pro\\' : '') . $featureName . '\\';
    }

}