<?php
/**
 * FormsBlocksExtension.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:FormsBlocks!
 * @subpackage	DI
 * @since		5.0
 *
 * @date		28.04.15
 */

namespace IPub\FormsBlocks\DI;

use Nette;
use Nette\DI;
use Nette\PhpGenerator as Code;

class FormsBlocksExtension extends DI\CompilerExtension
{
	/**
	 * @param Code\ClassType $class
	 */
	public function afterCompile(Code\ClassType $class)
	{
		parent::afterCompile($class);

		$init = $class->methods['initialize'];
		$init->addBody('IPub\FormsBlocks\Container::register();');
	}

	/**
	 * @param Nette\Configurator $config
	 * @param string $extensionName
	 */
	public static function register(Nette\Configurator $config, $extensionName = 'formsBlocks')
	{
		$config->onCompile[] = function (Nette\Configurator $config, Nette\DI\Compiler $compiler) use ($extensionName) {
			$compiler->addExtension($extensionName, new FormsBlocksExtension());
		};
	}
}
