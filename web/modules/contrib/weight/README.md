# Weight

Weight module provides a weight field that can be added to any fieldable entity.
The weight field can be used to provide customized sorting.

For a full description of the module, visit :
[Project Page](https://www.drupal.org/project/weight).

Submit bug reports and feature suggestions, or track changes visit :
[Issue Queue](https://www.drupal.org/project/issues/weight).


## Table of contents

- Requirements
- Installation
- Configuration
- How to use (example)
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

`Range:` The range is set when adding a weight field to an entity. It defines
that fields available weight range.

For example, a range of 20 will allow
you to select a weight between -20 and 20.

## How to use (example)

Now the weight field is available to be used in any entity. So just as example,
you can add the "weight" field in a content type:

- Access: "Structure" » "Content types" » <CONTENT-TYPE> » "Manage fields"
- Go to "Add field" and select "General" » "Weight"
- Put a label and save.
- Now you can select a "Range" value that will be range of weights available to
  select. For example, a range of 20 will allow you to select a weight between
  -20 and 20.
- Save.
- Now you can add a node using this content type:
- Go to "Content" » "Add content" » <CONTENT-TYPE>
- Now you can see the "weight" and the range to select


## Maintainers

- Kevin Quillen - [kevinquillen](https://www.drupal.org/u/kevinquillen)
- Nancy Wichmann - [NancyDru](https://www.drupal.org/u/nancydru)
- davisben - [davisben](https://www.drupal.org/u/davisben)
- Andrew Berry - [deviantintegral](https://www.drupal.org/u/deviantintegral)
- Harry Slaughter - [Harry Slaughter](https://www.drupal.org/u/harry-slaughter)
- Jeff Robbins - [jjeff](https://www.drupal.org/u/jjeff)
- Neslee Canil Pinto - [Neslee Canil Pinto](https://www.drupal.org/u/neslee-canil-pinto)
- Shreya Shetty - [Shreya Shetty](https://www.drupal.org/u/shreya-shetty)
- ziomizar - [ziomizar](https://www.drupal.org/u/ziomizar)
