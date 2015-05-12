<?php
/**
 * Container.php
 *
 * @copyright	More in license.md
 * @license		http://www.ipublikuj.eu
 * @author		Adam Kadlec http://www.ipublikuj.eu
 * @package		iPublikuj:FormsBlocks!
 * @subpackage	common
 * @since		5.0
 *
 * @date		28.04.15
 */

namespace IPub\FormsBlocks;

use Nette;
use Nette\Forms;

class BlockContainer extends Forms\Container
{
	/**
	 * @return string
	 *
	 * @throws Nette\InvalidArgumentException
	 */
	public function getType()
	{
		if ($component = $this->getComponent('blockType', FALSE)) {
			return $component->getValue();
		}

		throw new Nette\InvalidArgumentException("Container with name '$this->name' has not defined block type.");
	}
}