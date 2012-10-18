<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
	define('PHPUnit_MAIN_METHOD', 'AllTests::main');
}

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once 'func/Testing/TarSeleniumRemoteSuite.php';

class TarCentosTests
{
	public static function main()
	{
		PHPUnit_TextUI_TestRunner::run(self::suite());
	}

	public static function suite()
	{
		$suite = new TarSeleniumRemoteSuite('PHPUnit');

		// Selenium tests
		$suite->addTestFiles(glob("func/Site/*Test.php"));
		$suite->addTestFiles(glob("func/Trackers/*Test.php"));
		$suite->addTestFiles(glob("func/Tasks/*Test.php"));
		$suite->addTestFiles(glob("func/Docs/*Test.php"));
		$suite->addTestFiles(glob("func/Forums/*Test.php"));
		$suite->addTestFiles(glob("func/News/*Test.php"));
		$suite->addTestFiles(glob("func/PluginsBlocks/*Test.php"));
//		$suite->addTestFiles(glob("func/PluginsMediawiki/*Test.php"));
//		$suite->addTestFiles(glob("func/PluginsMoinMoin/*Test.php"));
		$suite->addTestFiles(glob("func/PluginsOnlineHelp/*Test.php"));
//		$suite->addTestFiles(glob("func/PluginsScmGit/*Test.php"));
//		$suite->addTestFiles(glob("func/PluginsSvnTracker/*Test.php"));
		$suite->addTestFiles(glob("func/RBAC/*Test.php"));
		$suite->addTestFiles(glob("func/Surveys/*Test.php"));
		$suite->addTestFiles(glob("func/Search/*Test.php"));

		return $suite;
	}
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
	AllTests::main();
}
?>
