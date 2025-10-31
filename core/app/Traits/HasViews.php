<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Traits;

use ReallySimplePlugins\RSS\Core\Bootstrap\App;

trait HasViews
{
    /**
     * Method for returning the desired view as a string
     * @throws \LogicException
     */
    public function view(string $path, array $variables = [], string $extension = 'php'): string
    {
        $container = App::getInstance();
        $basePath = $container->config->getString('env.plugin.view_path');
        $filePath = realpath($basePath . $path . '.' . $extension);

        // Someone is doing something dirty
        if (($filePath === false) || (strpos($filePath, $basePath) !== 0)) {
            throw new \LogicException('Given path is not valid: ' . esc_html($filePath));
        }

        if (empty($filePath) || (file_exists($filePath) === false) || (is_readable($filePath) === false)) {
            return '';
        }

        extract($variables);

        ob_start();
        require $filePath;
        return ob_get_clean();
    }

    /**
     * Method for outputting the desired view.
     *
     * @internal we can ignore the phpcs error because we validate in
     * {@see view} that the executed path is in our plugin. And we
     * escape all variables in our views, so we have full control.
     */
    public function render(string $path, array $variables = [], string $extension = 'php'): void
    {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->view($path, $variables, $extension);
    }

}