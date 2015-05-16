Quickstart
==========

Nette forms blocks container aka `addBlocks`.

Installation
------------

The best way to install ipub/forms-blocks is using  [Composer](http://getcomposer.org/):

```sh
$ composer require ipub/forms-blocks:@dev
```

Now you have to enable the extension using your neon config

```neon
extensions:
	formsBlocks: IPub\FormsBlocks\DI\FormsBlocksExtension
```

Or place the forms blocks class to folder, where RobotLoader can find it and add following line to `app/boostrap.php` or to `BasePresenter::startup()`.

```php
IPub\FormsBlocks\Container::register();
```

Attaching to form
-----------------

If you want to create multiple different blocks in your form you can easily define them

```php
use Nette\Forms\Container;

$blocks = $form->addBlocks('blocks'); // Create container

$blocks->addBlock('someBlock', function (Container $container) {
	$container->addText('name');
});

$blocks->addBlock('otherBlock', function (Container $container) {
	$container->addText('name');
});
```

Handling
--------

Handling is trivial, you just walk the values from user in cycle.

```php
use Nette\Application\UI\Form;

public function FormSubmitted(Form $form)
{
	foreach ($form['blocks']->values as $block) { // values from blocks container
		dump($block['name']);
		dump($block['blockType']);
	}
}
```

The field *blockType* is created automatically by the container and contain used block name. 

Editation of items
------------------

You can use names of nested containers as identifiers. From the nature of form containers, you can work with them like this:

```php
public function actionEditPage()
{
	$form = $this['myForm'];

	// if form was not submitted
	if (!$form->isSubmitted()) {
		// expects instance of model class in presenter
		$blocks = $this->model->findAll();

		foreach ($blocks as $block) {
			// fill the container with default values
			$form['blocks'][$block->id]->setValues($block);
		}
	}
}
```

And modify the handling

```php
public function FormSubmitted(Form $form)
{
	foreach ($form['blocks']->values as $blockId => $block) {
		// now we have accessible ID of the user and associated values from the container
	}
}
```

Adding and removing of containers
---------------------------------

```php
protected function createComponentMyForm()
{
	$form = new Nette\Application\UI\Form;

	$removeEvent = callback($this, 'MyFormRemoveElementClicked');

	// Create container
	$blocks = $form->addBlocks('blocks'); // Create container

	// Register block
	$blocks->addBlock('someBlock', function (Container $container) {
		$container->addText('name');

		$container->addSubmit('remove', 'Remove')
			->setValidationScope(FALSE) # disables validation
			->onClick[] = $removeEvent;
	});

	$blocks->addSubmit('add', 'Add new block')
		->setValidationScope(FALSE)
		->onClick[] = callback($this, 'MyFormAddElementClicked');

	// ...
}
```

Handlig of add button is easy. Next example is useful, when you expect that your users like to prepare more containers before they fill and submit them.

```php
use Nette\Forms\Controls\SubmitButton;

public function MyFormAddElementClicked(SubmitButton $button)
{
	$button->parent->createOne();
}
```

When you want to allow adding only one container each time, so there will be no more than one unfilled at time, you would have to check for values manually, or with helper function.

```php
public function MyFormAddElementClicked(SubmitButton $button)
{
	$blocks = $button->parent;

	// count how many containers were filled
	if ($blocks->isAllFilled()) {
		// add one container to forms blocks
		$button->parent->createOne();
	}
}
```

Method `FormsBlocks::isAllFilled()` checks, if the form controls are not empty. It's argument says which ones not to check.

When the user clicks to delete, the following event will be invoked

```php
public function MyFormRemoveElementClicked(SubmitButton $button)
{
	// first parent is container
	// second parent is it's forms blocks
	$blocks = $button->parent->parent;
	$blocks->remove($button->parent, TRUE);
}
```

If I'd want to for example delete block also from database and I have container names as identifiers, then I can read the value like this:

```php
public function MyFormRemoveElementClicked(SubmitButton $button)
{
	$id = $button->parent->name;
}
```

Manual rendering
----------------

When you add a submit button to blocks container, you certainly don't want to try it render as container, so for skipping them, there is a method `getContainers()`, that will return only existing [containers](doc:/en/forms#toc-addcontainer).

```html
{form myForm}
{foreach $form['blocks']->containers as $block}

	{$block['name']->control} {$block['name']->label}

{/foreach}
{/form}
```

Or with form macros

```html
{form myForm}
{foreach $form['blocks']->containers as $id => $block}

	{input blocks-$id-name} {label blocks-$id-name /}

{/foreach}
{/form}
```

Container also create select field for selecting which block should be created when you click on add button

```html
{$form['blocks']['createBlock']->control} {$form['blocks']['createBlock']->label}
```

Or with form macros

```html
{input blocks-createBlock} {label blocks-createBlock /}
```