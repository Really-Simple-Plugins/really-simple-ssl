<?php
/**
 * This file manages to autoload of the classes in the pro folder.
 *
 * @package     REALLY_SIMPLE_SSL
 */
spl_autoload_register(
    static function ($the_class) {
        // project-specific namespace prefix.
        $prefix = 'RSSSL\\';

        // base directory for the namespace prefix.
        $base_dir = rsssl_path;

        // does the class use the namespace prefix?
        $len = strlen($prefix);
        if (0 !== strncmp($prefix, $the_class, $len)) {
            return;
        }
        // get the relative class name.
        $relative_class = substr($the_class, $len);
        $relative_class = strtolower($relative_class);
        // converting backslashes to slashes, underscores to hyphens.
        $relative_class = str_replace(array('\\', '_', 'dynamictables'), array(
            '/',
            '-',
            'dynamic-tables'
        ), $relative_class); // New Line: handle the case of 'dynamic tables' to 'dynamic-tables' This is placeholder fix for now.

        $file = $base_dir . $relative_class; // old way to form filename.
       // $file = preg_replace('{/([^/]+)$}', '/class-$1.php', $file); // new way to form filename.

        if (strpos($relative_class, 'trait') !== false) {
            $file = preg_replace('{/([^/]+)$}', '/trait-$1.php', $file);
        } elseif (strpos($relative_class, 'interface') !== false) {
            $file = preg_replace('{/([^/]+)$}', '/interface-$1.php', $file);
        } else {
            $file = preg_replace('{/([^/]+)$}', '/class-$1.php', $file);
        }

//        if(str_contains(strtolower($the_class), 'trait')) {
//            var_dump(file_exists($file));
//            var_dump($file);
//            die('now');
//        }
        if (class_exists($the_class)) {
            return;
        }

        // if the file exists, require it.
        if (file_exists($file)) {
            require_once $file;
        }
    }
);
