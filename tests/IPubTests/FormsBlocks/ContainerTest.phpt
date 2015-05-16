<?php
/**
 * Test: IPub\FormsBlocks\ContainerTest
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
use Nette\Application;
use Nette\Forms;

use Tester;
use Tester\Assert;

use IPub;
use IPub\FormsBlocks;

require_once __DIR__ . '/../bootstrap.php';

class ContainerTest extends Tester\TestCase
{
	public function testReplicating()
	{
		$blocks = new FormsBlocks\Container('blocks');

		$blocks->addBlock('testBlock', function (Nette\Forms\Container $container) {
			$container->addText('name', "Name");
		});

		Assert::true($blocks[0]['name'] instanceof Forms\Controls\TextInput);
		Assert::true($blocks[2]['name'] instanceof Forms\Controls\TextInput);
		Assert::true($blocks[1000]['name'] instanceof Forms\Controls\TextInput);
	}

	public function testRendering_attachAfterDefinition()
	{
		$form = new BaseForm();

		$blocks = $form->addBlocks('blocks');

		$blocks->addBlock('testBlock', function (Nette\Forms\Container $block) {
			$block->addText('name');
		}, 1);

		$blocks->addSubmit('add');

		$this->connectForm($form);

		// container and submit button
		Assert::same(2, iterator_count($blocks->getComponents()));

		// simulate rendering additional key
		Assert::true($blocks[2]['name'] instanceof Forms\Controls\TextInput);

		// 2 containers and submit button
		Assert::same(3, iterator_count($blocks->getComponents()));

		Assert::same(['blocks' => [
			0 => ['name' => ''],
			2 => ['name' => ''],
		]], $form->getValues(TRUE));
	}

	public function testRendering_attachBeforeDefinition()
	{
		$form = new BaseForm();

		$this->connectForm($form);

		$blocks = $form->addBlocks('blocks');

		$blocks->addBlock('testBlock', function (Nette\Forms\Container $block) {
			$block->addText('name');
		}, 1);

		$blocks->addSubmit('add');

		// container and submit button
		Assert::same(2, iterator_count($blocks->getComponents()));

		// simulate rendering additional key
		Assert::true($blocks[2]['name'] instanceof Forms\Controls\TextInput);

		// 2 containers and submit button
		Assert::same(3, iterator_count($blocks->getComponents()));

		Assert::same(['blocks' => [
			0 => ['name' => ''],
			2 => ['name' => ''],
		]], $form->getValues(TRUE));
	}

	public function testSubmit_attachAfterDefinition()
	{
		$form = new BaseForm();

		$blocks = $form->addBlocks('blocks');

		$blocks->addBlock('testBlock', function (Nette\Forms\Container $block) {
			$block->addText('name');
		}, 1);

		$blocks->addSubmit('add');

		$this->connectForm($form, array(
			'blocks' => array(
				0 => array('name' => 'Block one', 'blockType' => 'testBlock'),
				2 => array('name' => 'Second block', 'blockType' => 'testBlock'),
				3 => array('name' => 'And last block', 'blockType' => 'testBlock'),
			),
			'do' => 'form-submit'
		));

		// container and submit button
		Assert::same(4, iterator_count($blocks->getComponents()));

		Assert::same(['blocks' => [
			0 => ['name' => 'Block one'],
			2 => ['name' => 'Second block'],
			3 => ['name' => 'And last block'],
		]], $form->getValues(TRUE));
	}

	public function testSubmit_attachBeforeDefinition()
	{
		$form = new BaseForm();

		$this->connectForm($form, [
			'blocks' => [
				0 => ['name' => 'Block one'],
				2 => ['name' => 'Second block'],
				3 => ['name' => 'And last block'],
			],
			'do' => 'form-submit'
		]);

		$blocks = $form->addBlocks('blocks');

		$blocks->addBlock('testBlock', function (Nette\Forms\Container $block) {
			$block->addText('name');
		}, 1);

		$blocks->addSubmit('add');

		// container and submit button
		Assert::same(4, iterator_count($blocks->getComponents()));

		Assert::same(['blocks' => [
			0 => ['name' => 'Block one'],
			2 => ['name' => 'Second block'],
			3 => ['name' => 'And last block'],
		]], $form->getValues(TRUE));
	}

	public function testSubmit_nestedReplicator_notFilled()
	{
		$form = new BaseForm();

		$this->connectForm($form, [
			'blocks' => [
				0 => [
					'subBlocks' => [
						0 => ['name' => '']
					]
				]
			],
			'do' => 'form-submit',
		]);

		$blocks = $form->addBlocks('blocks');

		$blocks->addBlock('testBlock', function (Nette\Forms\Container $block) {
			$block->addBlocks('subBlocks');

			$block->addBlock('subBlock', function (Nette\Forms\Container $subBlock) {
				$subBlock->addText('name');
				$subBlock->addText('note');
			});
		});

		$blocks->addSubmit('add')->addCreateOnClick();

		Assert::false($blocks->isAllFilled());
	}

	public function testSubmit_nestedReplicator_filled()
	{
		$form = new BaseForm();

		$this->connectForm($form, [
			'blocks' => [
				0 => [
					'subBlocks' => [
						0 => ['name' => 'foo']
					]
				]
			],
			'do' => 'form-submit',
		]);

		$blocks = $form->addBlocks('blocks');

		$blocks->addBlock('testBlock', function (Nette\Forms\Container $block) {
			$block->addBlocks('subBlocks');

			$block->addBlock('subBlock', function (Nette\Forms\Container $subBlock) {
				$subBlock->addText('name');
				$subBlock->addText('note');
			});
		});

		$blocks->addSubmit('add')->addCreateOnClick();

		Assert::true($blocks->isAllFilled());
	}

	/**
	 * @param Application\UI\Form $form
	 * @param array $post
	 *
	 * @return MockPresenter
	 */
	protected function connectForm(Application\UI\Form $form, array $post = [])
	{
		$container = $this->createContainer();

		/** @var MockPresenter $presenter */
		$presenter = $container->createInstance('IPubTests\FormsBlocks\MockPresenter', ['form' => $form]);

		$container->callInjects($presenter);

		$presenter->run(new Application\Request('Mock', $post ? 'POST' : 'GET', ['action' => 'default'], $post));

		$presenter['form']; // connect form

		return $presenter;
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
}

class BaseForm extends Application\UI\Form
{
	/**
	 * @param string $name
	 *
	 * @return FormsBlocks\Container
	 */
	public function addBlocks($name)
	{
		$control = new FormsBlocks\Container();
		$control->currentGroup = $this->currentGroup;

		return $this[$name] = $control;
	}

}

class MockPresenter extends Application\UI\Presenter
{
	/**
	 * @var Application\UI\Form
	 */
	private $form;

	public function __construct(Application\UI\Form $form)
	{
		$this->form = $form;
	}

	protected function beforeRender()
	{
		$this->terminate();
	}

	protected function createComponentForm()
	{
		return $this->form;
	}
}

\run(new ContainerTest());
