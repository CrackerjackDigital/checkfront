<?php
/**
 * Custom loader for checkfront SDK and module classes which are in excluded directories.
 */
class CheckfrontLoader extends Object {
    private static $version;

    // path to the Checkfront provided sdk library files, this is to the composer installed version
    private static $sdk_path = '/vendor/checkfront/checkfront/lib';
    // use the built-in version instead, swap with above line
//     private static $sdk_path = 'sdk/{version}/lib';

    // path to our implementation of their API, version should match the version in composer
    private static $implementation_path = 'code/api/{version}/';

    // we may want to restrict loaded classes by a prefix
    private static $expect_class_prefix = 'Checkfront';

    // we may want to strip off a class prefix, can use tokens, go from longer to shorter
    private static $remove_class_name_prefix = array(
        'CheckfrontAPI',
        'Checkfront'
    );

    // we may want to strip off a class suffix, can use tokens, go from longer to shorter
    private static $remove_class_name_suffix = array(
        'Response',
        '_{_version}'
    );

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

        $this->implPath = Controller::join_links(
            Director::baseFolder(),
            CheckfrontModule::module_path(),
            $this->detokenise($this->config()->get('implementation_path'), $version)
        );

        $sdkPath = Controller::join_links(
            Director::baseFolder(),
            CheckfrontModule::module_path(),
            $this->detokenise($this->config()->get('sdk_path'), $version)
        );

        foreach (glob($sdkPath . '*.php') as $file) {
            require_once($file);
        }

        spl_autoload_register([
            $this,
            'loadClass'
        ]);
    }


    /**
     * Will try and load module class from the api/{version}/ directory or subdirectory.
     * Strips and prefix or suffix defined in config form the provided $className first.
     *
     * @param $className
     */
    public function loadClass($className) {
        $expectClassPrefix = $this->config()->get('expect_class_prefix');

        if (!$expectClassPrefix || (substr($className, 0, strlen($expectClassPrefix)) == $expectClassPrefix)) {
            $version = $this->config()->get('version');

            $stripPrefix = $this->detokenise(
                $this->config()->get('remove_class_name_prefix'),
                $version
            );
            $stripSuffix = $this->detokenise(
                $this->config()->get('remove_class_name_suffix'),
                $version
            );
            $fileName = $className;

            // strip prefixes if they exists in className
            foreach ($stripPrefix as $prefix) {
                if (substr($fileName, 0, strlen($prefix)) === $prefix) {
                    $fileName = substr($fileName, strlen($prefix));
                }
            }
            // strip suffix if it exists in className
            foreach ($stripSuffix as $suffix) {
                if (substr($fileName, -strlen($suffix)) === $suffix) {
                    $fileName = substr($fileName, 0, -strlen($suffix));
                }
            }
            $fileName .= '.php';
            $filePath = $this->implPath;

            $filePathName = $filePath . $fileName;

            if (file_exists($filePathName)) {
                require_once($filePathName);
                return;
            }
            // not in SDK base so try sub-directories matching on the name.
            foreach (glob("$filePath*/*.php") as $foundFile) {
                if (basename($foundFile) == $fileName) {
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
        return str_replace([
                '{version}',
                '_{_version}'
            ],
            [
                $version,
                $underscoreVersion
            ],
            $stringsWithTokens
        );
    }
}