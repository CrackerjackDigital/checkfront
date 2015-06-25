<?php
/**
 * Custom loader for checkfront SDK and module classes which are in excluded directories.
 */
class CheckfrontLoader extends Object {
    private static $version;

    // path to the Checkfront provided sdk library files. Will load files from subdirectories too
    // (only files ending in '.php' are loaded). Files found here are loaded upfront as forward declarations for
    // other files which may rely on them.
    // NB: by default we use the composer installed version, to use the supplied version(s) use 'sdk/{version}/lib/'
    private static $sdk_path = '/vendor/checkfront/checkfront/lib/';

    // path to our implementation of their API, version should match the version in composer and Implementation, will
    // check subdirectories for classes too (only files ending in '.php' are loaded). Files are loaded by call to
    // spl_autoloader, not all upfront.
    private static $implementation_path = 'code/api/{version}/';

    // we may want to restrict loaded classes by a prefix
    private static $expect_class_prefix = 'Checkfront';

    // we may want to strip off a class prefix in implementation classes, can use tokens, go from longer to shorter
    private static $remove_class_name_prefix = array(
        'CheckfrontAPI',
        'Checkfront'
    );

    // we may want to strip off a class suffix, can use tokens, go from longer to shorter
    private static $remove_class_name_suffix = array(
        'Response',
        '_{_version}'
    );

    private $implPath;

    private $sdkPath;

    /**
     * Loads all *.php files found at the sdk path module/sdk/{version}/lib and adds this.loadClass to
     * spl autoloader chain.
     *
     * @param string|null $version - either use this version (e.g. '3.0')
     * or take it from config.version if passed falsish value.
     *
     * @sideeffect if passed a version will update this.config to that version.
     */
    public function __construct($version = null) {
        if ($version) {
            // provided a version, update config so we can use in the class loader later
            Config::inst()->update(__CLASS__, 'version', $version);
        } else {
            // no version, try and take from config instead.
            $version = $this->config()->get('version');
        }
        $pathTemp = $this->config()->get('sdk_path');

        // if sdk path is 'absolute' then load from site root, otherwise load relative to checkfront module directory
        $this->sdkPath = Controller::join_links(
            Director::baseFolder(),
            substr($pathTemp, 0, 1) === '/' ? '' : CheckfrontModule::module_path(),
            $this->detokenise($pathTemp, $version)
        );
/*
        // recurse through the sdk path and look for file matching the mangled class name to require.
        $itr = new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sdkPath),
                RecursiveIteratorIterator::SELF_FIRST
            ),
            '*.php'
        );
        foreach ($itr as $file) {
            require_once($file);
        }
*/
        // now save the implementation path for later
        $this->implPath = Controller::join_links(
            Director::baseFolder(),
            CheckfrontModule::module_path(),
            $this->detokenise($this->config()->get('implementation_path'), $version)
        );

        spl_autoload_register(array(
            $this,
            'loadClass'
        ));
    }


    /**
     * Will try and load module class from the api/{version}/ directory or subdirectory.
     * Strips and prefix or suffix defined in config form the provided $className first.
     *
     * @param $className
     */
    public function loadClass($className) {
        // only load files which start with this.
        $expectClassPrefix = $this->config()->get('expect_class_prefix');

        if (!$expectClassPrefix || (substr($className, 0, strlen($expectClassPrefix)) == $expectClassPrefix)) {
            $version = $this->config()->get('version');

            // start with filename = classname
            $fileName = $className;

            // detokenise and clean up possible class prefixes
            $stripPrefix = $this->detokenise(
                $this->config()->get('remove_class_name_prefix'),
                $version
            );
            // strip prefixes if they exists in className
            foreach ($stripPrefix as $prefix) {
                if (substr($fileName, 0, strlen($prefix)) === $prefix) {
                    $fileName = substr($fileName, strlen($prefix));
                }
            }

            // detokenise and clean up possible class suffixes
            $stripSuffix = $this->detokenise(
                $this->config()->get('remove_class_name_suffix'),
                $version
            );
            // strip suffix if it exists in className
            foreach ($stripSuffix as $suffix) {
                if (substr($fileName, -strlen($suffix)) === $suffix) {
                    $fileName = substr($fileName, 0, -strlen($suffix));
                }
            }
            // scan sdk path first then implementation path for $fileName.php
            foreach (array($this->sdkPath, $this->implPath) as $path) {

                // recurse through the implementation path and look for file matching the mangled class name to require.
                $itr = new RegexIterator(
                    new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($path),
                        RecursiveIteratorIterator::SELF_FIRST
                    ),
                    "/$fileName\\.php/"
                );
                // should really only be one
                foreach ($itr as $foundFile) {
                    require_once($foundFile);
                    return;
                }
            }
        }
    }

    /**
     * Replace {tokens} in passed string with mangled other parameters.
     *
     * NB: Very imperative atm.
     *
     * @param array|string $stringsWithTokens
     * @param $version
     * @return mixed
     */
    private function detokenise($stringsWithTokens, $version) {
        $underscoreVersion = str_replace('.', '_', $version);
        return str_replace(array(
                '{version}',
                '_{_version}'
            ),
            array(
                $version,
                $underscoreVersion
            ),
            $stringsWithTokens
        );
    }
}