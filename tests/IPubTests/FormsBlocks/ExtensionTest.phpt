<?php
/**
 * Test: IPub\FormsBlocks\ExtensionTest
 * @testCase
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:FormsBlocks!
 * @subpackage	Tests
 * @since		5.0
 *
 * @date		16.05.15
 */

namespace IPubTests\FormsBlocks;

use Nette;

use Tester;
use Tester\Assert;

use IPub;
use IPub\FormsBlocks;

require_once __DIR__ . '/../bootstrap.php';

class ExtensionTest extends Tester\TestCase
{
	protected function setUp()
	{
		parent::setUp();

		Tester\Environment::$checkAssertions = FALSE;
	}

	/**
	 * @return \SystemContainer|\Nette\DI\Container
	 */
	protected function createContainer()
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);

		FormsBlocks\DI\FormsBlocksExtension::register($config);

		return $config->createContainer();
	}

	public function testExtensionMethodIsRegistered()
	{
		$this->createContainer(); // initialize

		$form = new Nette\Forms\Form();
		$form->addBlocks('blocks');
	}
}

\run(new ExtensionTest());