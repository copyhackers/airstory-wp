# Contributing to Airstory

Thank you for your interest in contributing the ongoing development of the Airstory WordPress plugin!


## Contributing code

The dependencies for the plugin are loaded via [Composer](https://getcomposer.org) and [npm](https://docs.npmjs.com/getting-started/what-is-npm), so it's necessary to have both of those tools installed locally.

Begin by cloning the GitHub repo locally and installing the dependencies:

```bash
# Clone the repository, ideally into a wp-content/plugins directory:
$ git clone https://github.com/liquidweb/airstory-wp.git airstory && cd airstory

# Install local dependencies
$ composer install && npm install
```

The Airstory plugin is built for PHP versions 5.3 and above (required for using PHP namespaces), and requires the `dom`, `mcrypt`, and `openssl` PHP extensions.


### Project structure

The main plugin file, `airstory.php`, consists of a series of include files, each living in its own namespace under the base `Airstory` namespace. New functionality should be introduced following this same scheme.


### Branching

Pull requests should be based off the `develop` branch, which represents the current development state of the plugin. The only thing ever merged into `master` should be new release branches, at the time a release is tagged.

To create a new feature branch:

```bash
# Start on develop, making sure it's up-to-date
$ git checkout develop && git pull

# Create a new branch for your feature
$ git checkout -b feature/my-cool-new-feature
```

When submitting a new pull request, your `feature/my-cool-new-feature` should be compared against `develop`.


### Coding standards

This project uses [the WordPress-Extra and WordPress-Docs rulesets for PHP_CodeSniffer](https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards), as declared in `phpcs.xml`. PHP_CodeSniffer will be run automatically against any modified files on a pre-commit Git hook, thanks to [WP Enforcer](https://github.com/stevegrunwell/wp-enforcer).


#### Localization

The Airstory plugin aims to be 100% localization-ready, so any user-facing strings [must use appropriate localization functions](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/).

At any time, you may regenerate the plugin's `languages/airstory.pot` file by running `grunt i18n`.


### Running unit tests

The Airstory plugin aims to have a high level of unit test coverage, using [WP_Mock](https://github.com/10up/wp_mock) and its dependencies, [Mockery](http://docs.mockery.io/en/latest/), [Patchwork](https://github.com/antecedent/patchwork), and [PHPUnit](https://phpunit.de/). When submitting changes, please be sure to add or update unit tests accordingly.

[![Test Coverage](https://codeclimate.com/github/liquidweb/airstory-wp/badges/coverage.svg)](https://codeclimate.com/github/liquidweb/airstory-wp/coverage)

You may run unit tests at any time by running:

```bash
# From the root of the plugin directory
$ ./vendor/bin/phpunit

# Alternately:
$ npm test
```


## Contributors

The Airstory plugin was built as a joint effort between [Airstory](http://airstory.co/) and [Liquid Web](https://www.liquidweb.com).

[See a list of all contributors to the plugin](https://github.com/liquidweb/airstory-wp/graphs/contributors).
