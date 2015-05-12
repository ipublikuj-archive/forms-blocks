# Forms

[![Build Status](https://img.shields.io/travis/iPublikuj/forms-blocks.svg?style=flat-square)](https://travis-ci.org/iPublikuj/forms-blocks)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/iPublikuj/forms-blocks.svg?style=flat-square)](https://scrutinizer-ci.com/g/iPublikuj/forms-blocks/?branch=master)
[![Latest Stable Version](https://img.shields.io/packagist/v/ipub/forms-blocks.svg?style=flat-square)](https://packagist.org/packages/ipub/forms-blocks)
[![Composer Downloads](https://img.shields.io/packagist/dt/ipub/forms-blocks.svg?style=flat-square)](https://packagist.org/packages/ipub/forms-blocks)

[Nette Framework](http://nette.org/) forms control for creating different replicated blocks.

## Installation

The best way to install ipub/forms-blocks is using  [Composer](http://getcomposer.org/):

```json
{
	"require": {
		"ipub/forms-blocks": "dev-master"
	}
}
```

or

```sh
$ composer require ipub/forms-blocks:@dev
```

After that you have to register extension in config.neon.

```neon
extensions:
	formsBlocks: IPub\FormsBlocks\DI\FormsBlocksExtension
```

## Documentation

Learn how to multiple dynamics blocks in your form in [documentation](https://github.com/iPublikuj/forms-blocks/blob/master/docs/en/index.md).

***
Homepage [http://www.ipublikuj.eu](http://www.ipublikuj.eu) and repository [http://github.com/iPublikuj/forms-blocks](http://github.com/iPublikuj/forms-blocks).