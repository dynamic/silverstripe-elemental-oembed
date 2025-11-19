# Silverstripe Elemental Media Block

A content block to embed media from other websites via oEmbed

[![CI](https://github.com/dynamic/silverstripe-elemental-oembed/actions/workflows/ci.yml/badge.svg)](https://github.com/dynamic/silverstripe-elemental-oembed/actions/workflows/ci.yml)
[![Sponsors](https://img.shields.io/badge/Sponsor-Dynamic-ff69b4?logo=github-sponsors&logoColor=white)](https://github.com/sponsors/dynamic)

[![Latest Stable Version](https://poser.pugx.org/dynamic/silverstripe-elemental-oembed/v/stable)](https://packagist.org/packages/dynamic/silverstripe-elemental-oembed)
[![Total Downloads](https://poser.pugx.org/dynamic/silverstripe-elemental-oembed/downloads)](https://packagist.org/packages/dynamic/silverstripe-elemental-oembed)
[![Latest Unstable Version](https://poser.pugx.org/dynamic/silverstripe-elemental-oembed/v/unstable)](https://packagist.org/packages/dynamic/silverstripe-elemental-oembed)
[![License](https://poser.pugx.org/dynamic/silverstripe-elemental-oembed/license)](https://packagist.org/packages/dynamic/silverstripe-elemental-oembed)

## Requirements

* SilverStripe ^6.0
* PHP ^8.3
* dnadesign/silverstripe-elemental: ^6.0
* fromholdio/silverstripe-embedfield: ^5.1

## Installation

`composer require dynamic/silverstripe-elemental-oembed`

## Usage

Elemental oEmbed Block will add the following Element to your site:

* oEmbed (A block to embed media from other websites via oEmbed)

## Screen Shots

#### Front End sample of a oEmbed Element
The default templates are based off [Bootstrap 4](https://getbootstrap.com/) classes/styling

![Front End sample of a oEmbed Element](./readme-images/oembed-block-sample.jpg)

#### CMS - oEmbed Main Tab
![CMS - oEmbed Main Tab](./readme-images/oembed-block-cms.jpg)

## Getting more elements

See [Elemental modules by Dynamic](https://github.com/dynamic/silverstripe-elemental-blocks#getting-more-elements)

## Configuration

See [SilverStripe Elemental Configuration](https://github.com/dnadesign/silverstripe-elemental#configuration)

## Upgrading from version 5

SilverStripe Elemental OEmbed 6.0 is compatible with SilverStripe 6. Key changes:

- Updated to SilverStripe CMS 6
- Requires PHP 8.3 or higher
- Updated to Elemental 6
- Replaced linkable with fromholdio/silverstripe-embedfield ^5.1
- Updated BuildTask signature for SS6 compatibility
- No breaking changes to the API or templates
## Maintainers

*  [Dynamic](http://www.dynamicagency.com) (<dev@dynamicagency.com>)

## Bugtracker

Bugs are tracked in the issues section of this repository. Before submitting an issue please read over existing issues to ensure yours is unique.

If the issue does look like a new bug:

- Create a new issue
- Describe the steps required to reproduce your issue, and the expected outcome. Unit tests, screenshots and screencasts can help here.
- Describe your environment as detailed as possible: SilverStripe version, Browser, PHP version, Operating System, any installed SilverStripe modules.

Please report security issues to the module maintainers directly. Please don't file security issues in the bugtracker.

## Development and contribution

If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.