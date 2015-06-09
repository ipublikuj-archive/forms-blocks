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
use Nette\Application;
use Nette\Forms;
use Nette\Forms\Controls;
use Nette\Utils;

/**
 * @method \Nette\Application\UI\Form getForm()
 *
 * @property Forms\Container $parent
 * @property-read \ArrayIterator $components
 * @property-read \ArrayIterator $containers
 */
class Container extends Nette\Forms\Container
{
	/**
	 * @var callable[]
	 */
	protected $blocks;

	/**
	 * @var boolean
	 */
	private $submittedBy = FALSE;

	/**
	 * @var array
	 */
	private $created = [];

	/**
	 * @var \Nette\Http\IRequest
	 */
	private $httpRequest;

	/**
	 * @var array
	 */
	private $httpPost;

	/**
	 * @var string
	 */
	private $selectedBlock;

	/**
	 *
	 */
	public function __construct()
	{
		parent::__construct();

		$this->monitor('Nette\Application\UI\Presenter');
	}

	/**
	 * Magical component factory
	 *
	 * @param \Nette\ComponentModel\IContainer
	 */
	protected function attached($obj)
	{
		parent::attached($obj);

		if (!$obj instanceof Application\UI\Presenter) {
			return;
		}

		$this->loadHttpData();
	}

	/**
	 * @param string $name
	 * @param callable $factory
	 * @param string $containerClass
	 *
	 * @return $this
	 */
	public function addBlock($name, $factory, $containerClass = 'Nette\Forms\Container')
	{
		try {
			$this->blocks[$name] = [
				'factory' => Utils\Callback::closure($factory),
				'containerClass' => $containerClass
			];

		} catch (Nette\InvalidArgumentException $e) {
			$type = is_object($factory) ? 'instanceof ' . get_class($factory) : gettype($factory);

			throw new Nette\InvalidArgumentException(
				'Block requires callable factory, ' . $type . ' given.', 0, $e
			);
		}

		return $this;
	}

	/**
	 * @param string $name
	 *
	 * @return $this
	 */
	public function setActiveBlock($name)
	{
		if (isset($this->blocks[$name]) && is_callable($this->blocks[$name]['factory'])) {
			$this->selectedBlock = $name;
		}

		return $this;
	}

	/**
	 * @param boolean $recursive
	 *
	 * @return \ArrayIterator|Forms\Container[]
	 */
	public function getContainers($recursive = FALSE)
	{
		return $this->getComponents($recursive, 'Nette\Forms\Container');
	}

	/**
	 * @param boolean $recursive
	 *
	 * @return \ArrayIterator|Forms\Controls\SubmitButton[]
	 */
	public function getButtons($recursive = FALSE)
	{
		return $this->getComponents($recursive, 'Nette\Forms\ISubmitterControl');
	}

	/**
	 * Magical component factory
	 *
	 * @param string $name
	 *
	 * @return Forms\Container
	 */
	protected function createComponent($name)
	{
		// Add block type select element
		if ($name == 'createBlock') {
			if (!isset($this->components[$name])) {
				$this->addSelect($name, 'createBlock')
					->setItems(array_reduce((is_array($this->blocks) ? array_keys($this->blocks) : []), function ($result, $row) {
						$result[$row] = $row;

						return $result;
					}, []));
			}

			return $this->components[$name];

		// Add block container
		} else {
			// Check if factory was set
			if (isset($this->blocks[$this->selectedBlock]) && is_callable($this->blocks[$this->selectedBlock]['factory'])) {
				$container = $this->createContainer($name, $this->blocks[$this->selectedBlock]['containerClass']);
				$container->currentGroup = $this->currentGroup;

				$this->addComponent($container, $name, $this->getFirstControlName());

				// Check if info about block is set or not
				if ($blockType = $container->getComponent('blockType', FALSE)) {
					$container->removeComponent($blockType);
				}

				// Set info about block type
				$container->addHidden('blockType', $this->selectedBlock)
					->setValue($this->selectedBlock);

				Utils\Callback::invoke($this->blocks[$this->selectedBlock]['factory'], $container);

				// Clear selected flag after using
				$this->selectedBlock = NULL;

				return $this->created[$container->name] = $container;
			}
		}

		return NULL;
	}

	/**
	 * @return string
	 */
	private function getFirstControlName()
	{
		$controls = iterator_to_array($this->getComponents(FALSE, 'Nette\Forms\IControl'));

		$firstControl = reset($controls);

		return $firstControl ? $firstControl->name : NULL;
	}

	/**
	 * @param string $name
	 * @param string $containerClass
	 *
	 * @return Forms\Container
	 */
	protected function createContainer($name, $containerClass = 'Nette\Forms\Container')
	{
		return new $containerClass();
	}

	/**
	 * @return boolean
	 */
	public function isSubmittedBy()
	{
		if ($this->submittedBy) {
			return TRUE;
		}

		foreach ($this->getButtons(TRUE) as $button) {
			if ($button->isSubmittedBy()) {
				return $this->submittedBy = TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Create new container
	 *
	 * @param string|int $name
	 *
	 * @return Forms\Container
	 *
	 * @throws \Nette\InvalidArgumentException
	 */
	public function createOne($name = NULL)
	{
		if ($name === NULL) {
			$names = array_keys(iterator_to_array($this->getContainers()));
			$name = $names ? max($names) + 1 : 0;
		}

		// Container is overriden, therefore every request for getComponent($name, FALSE) would return container
		if (isset($this->created[$name])) {
			throw new Nette\InvalidArgumentException("Container with name '$name' already exists.");
		}

		return $this[$name];
	}

	/**
	 * @param array|\Traversable $values
	 * @param bool $erase
	 * @param bool $onlyDisabled
	 *
	 * @return Forms\Container|Container
	 */
	public function setValues($values, $erase = FALSE, $onlyDisabled = FALSE)
	{
		if (!$this->form->isAnchored() || !$this->form->isSubmitted()) {
			foreach ($values as $name => $value) {
				if ((is_array($value) || $value instanceof \Traversable) && isset($value['blockType'])) {
					$this->selectedBlock = $value['blockType'];

					if (!$this->getComponent($name, FALSE)) {
						$this->createOne($name);
					}
				}
			}
		}

		return parent::setValues($values, $erase, $onlyDisabled);
	}

	/**
	 * @param bool $asArray
	 *
	 * @return array|Utils\ArrayHash
	 */
	public function getValues($asArray = FALSE)
	{
		$values = $asArray ? [] : new Utils\ArrayHash;

		foreach ($this->containers as $name => $control) {
			if ($control instanceof Forms\IControl && !$control->isOmitted()) {
				$values[$name] = $control->getValue();

			} elseif ($control instanceof Forms\Container) {
				$values[$name] = $control->getValues($asArray);
			}
		}
		return $values;
	}

	/**
	 * Loads data received from POST
	 *
	 * @internal
	 */
	protected function loadHttpData()
	{
		if (!$this->getForm()->isSubmitted()) {
			return;
		}

		foreach ((array) $this->getHttpData() as $name => $value) {
			if ((is_array($value) || $value instanceof \Traversable) && isset($value['blockType'])) {
				$this->selectedBlock = $value['blockType'];

				if (!$this->getComponent($name, FALSE)) {
					$this->createOne($name);
				}
			}
		}
	}

	/**
	 * @param string $name
	 *
	 * @return array|null
	 */
	protected function getContainerValues($name)
	{
		$post = $this->getHttpData();
		return isset($post[$name]) ? $post[$name] : NULL;
	}

	/**
	 * @return mixed|NULL
	 */
	private function getHttpData()
	{
		if ($this->httpPost === NULL) {
			$path = explode(self::NAME_SEPARATOR, $this->lookupPath('Nette\Forms\Form'));
			$this->httpPost = Nette\Utils\Arrays::get($this->getForm()->getHttpData(), $path, NULL);
		}

		return $this->httpPost;
	}

	/**
	 * @internal
	 *
	 * @param \Nette\Application\Request $request
	 *
	 * @return Container
	 */
	public function setRequest(Nette\Application\Request $request)
	{
		$this->httpRequest = $request;
		return $this;
	}

	/**
	 * @return \Nette\Application\Request
	 */
	private function getRequest()
	{
		if ($this->httpRequest !== NULL) {
			return $this->httpRequest;
		}

		return $this->httpRequest = $this->getForm()->getPresenter()->getRequest();
	}

	/**
	 * @param Forms\Container $container
	 * @param boolean $cleanUpGroups
	 *
	 * @return void
	 *
	 * @throws \Nette\InvalidArgumentException
	 */
	public function remove(Nette\Forms\Container $container, $cleanUpGroups = FALSE)
	{
		if ($container->parent !== $this) {
			throw new Nette\InvalidArgumentException('Given component ' . $container->name . ' is not children of ' . $this->name . '.');
		}

		// to check if form was submitted by this one
		foreach ($container->getComponents(TRUE, 'Nette\Forms\ISubmitterControl') as $button) {
			/** @var Forms\Controls\SubmitButton $button */
			if ($button->isSubmittedBy()) {
				$this->submittedBy = TRUE;
				break;
			}
		}

		/** @var Forms\Controls\BaseControl[] $components */
		$components = $container->getComponents(TRUE);
		$this->removeComponent($container);

		// reflection is required to hack form groups
		$groupRefl = Nette\Reflection\ClassType::from('Nette\Forms\ControlGroup');
		$controlsProperty = $groupRefl->getProperty('controls');
		$controlsProperty->setAccessible(TRUE);

		// walk groups and clean then from removed components
		$affected = [];
		foreach ($this->getForm()->getGroups() as $group) {
			/** @var \SplObjectStorage $groupControls */
			$groupControls = $controlsProperty->getValue($group);

			foreach ($components as $control) {
				if ($groupControls->contains($control)) {
					$groupControls->detach($control);

					if (!in_array($group, $affected, TRUE)) {
						$affected[] = $group;
					}
				}
			}
		}

		// remove affected & empty groups
		if ($cleanUpGroups && $affected) {
			foreach ($this->getForm()->getComponents(FALSE, 'Nette\Forms\Container') as $container) {
				if ($index = array_search($container->currentGroup, $affected, TRUE)) {
					unset($affected[$index]);
				}
			}

			/** @var Forms\ControlGroup[] $affected */
			foreach ($affected as $group) {
				if (!$group->getControls() && in_array($group, $this->getForm()->getGroups(), TRUE)) {
					$this->getForm()->removeGroup($group);
				}
			}
		}
	}

	/**
	 * Counts filled values, filtered by given names
	 *
	 * @param array $components
	 * @param array $subComponents
	 *
	 * @return int
	 */
	public function countFilledWithout(array $components = [], array $subComponents = [])
	{
		$httpData = array_diff_key((array)$this->getHttpData(), array_flip($components));

		if (!$httpData) {
			return 0;
		}

		$rows = [];
		$subComponents = array_flip($subComponents);
		foreach ($httpData as $item) {
			if (is_array($item)) {
				if (array_key_exists('blockType', $item)) {
					unset($item['blockType']);
				}

				$filter = function ($value) use (&$filter) {
					if (is_array($value)) {
						return count(array_filter($value, $filter)) > 0;
					}
					return strlen($value);
				};
				$rows[] = array_filter(array_diff_key($item, $subComponents), $filter) ?: FALSE;
			}
		}

		return count(array_filter($rows));
	}

	/**
	 * @param array $exceptChildren
	 *
	 * @return bool
	 */
	public function isAllFilled(array $exceptChildren = [])
	{
		$components = [];

		foreach ($this->getComponents(FALSE, 'Nette\Forms\IControl') as $control) {
			/** @var Forms\Controls\BaseControl $control */
			$components[] = $control->getName();
		}

		foreach ($this->getContainers() as $container) {
			foreach ($container->getComponents(TRUE, 'Nette\Forms\ISubmitterControl') as $button) {
				/** @var Forms\Controls\SubmitButton $button */
				$exceptChildren[] = $button->getName();
			}
		}

		$filled = $this->countFilledWithout($components, array_unique($exceptChildren));

		return $filled === iterator_count($this->getContainers());
	}

	/**
	 * @param $name
	 *
	 * @return Forms\Container
	 */
	public function addContainer($name)
	{
		return $this[$name] = new Forms\Container();
	}

	/**
	 * @param \Nette\ComponentModel\IComponent $component
	 * @param $name
	 * @param null $insertBefore
	 *
	 * @return \Nette\ComponentModel\Container|Forms\Container
	 */
	public function addComponent(Nette\ComponentModel\IComponent $component, $name, $insertBefore = NULL)
	{
		$group = $this->currentGroup;

		$this->currentGroup = NULL;

		parent::addComponent($component, $name, $insertBefore);

		$this->currentGroup = $group;

		return $this;
	}

	/**
	 * @var bool|string
	 */
	private static $registered = FALSE;

	/**
	 * @param string $methodName
	 *
	 * @return void
	 */
	public static function register($methodName = 'addBlocks')
	{
		if (self::$registered) {
			Forms\Container::extensionMethod(self::$registered, function () {
				throw new Nette\MemberAccessException;
			});
		}

		Forms\Container::extensionMethod($methodName, function (Forms\Container $_this, $name) {
			$control = new Container;
			$control->currentGroup = $_this->currentGroup;
			return $_this[$name] = $control;
		});

		if (self::$registered) {
			return;
		}

		Controls\SubmitButton::extensionMethod('addRemoveBlockOnClick', function (Controls\SubmitButton $_this, $callback = NULL) {
			$_this->setValidationScope(FALSE);
			$_this->onClick[] = function (Controls\SubmitButton $button) use ($callback) {
				$blocks = $button->lookup(__NAMESPACE__ . '\Container');

				/** @var Container $blocks */
				if (is_callable($callback)) {
					Utils\Callback::invoke($callback, $blocks, $button->parent);
				}

				if ($form = $button->getForm(FALSE)) {
					$form->onSuccess = [];
				}

				$blocks->remove($button->parent);
			};

			return $_this;
		});

		Controls\SubmitButton::extensionMethod('addCreateBlockOnClick', function (Controls\SubmitButton $_this, $allowEmpty = FALSE, $callback = NULL) {
			$_this->onClick[] = function (Controls\SubmitButton $button) use ($allowEmpty, $callback) {
				$blocks = $button->lookup(__NAMESPACE__ . '\Container');

				$type = $blocks->getComponent('createBlock')->getValue();

				/** @var Container $blocks */
				if (!is_bool($allowEmpty)) {
					$callback = Utils\Callback::closure($allowEmpty);
					$allowEmpty = FALSE;
				}

				if ($allowEmpty === TRUE || $blocks->isAllFilled() === TRUE) {
					$blocks->selectedBlock = $type;

					$newContainer = $blocks->createOne();

					if (is_callable($callback)) {
						Utils\Callback::invoke($callback, $blocks, $newContainer);
					}
				}

				$button->getForm()->onSuccess = [];
			};

			return $_this;
		});

		self::$registered = $methodName;
	}
}