<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitf30f468ae8520b2a9ec04f9ed0d29b54
{
    public static $files = array (
        'f1bb3deaa479a0b9e6405ed72fe0b36f' => __DIR__ . '/../..' . '/src/DeltaplanApi.php',
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInitf30f468ae8520b2a9ec04f9ed0d29b54::$classMap;

        }, null, ClassLoader::class);
    }
}
