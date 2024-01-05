SVG image field
===============

CONTENTS OF THIS FILE
---------------------

  * Introduction
  * Requirements
  * Installation
  * Configuration
  * Author
  * Similar projects and how they are different

INTRODUCTION
------------

Standard image field in Drupal 8 doesn't support SVG images. If you really want
to display SVG images on your website then you need another solution. This
module adds a new field, widget and formatter, which allows svg file extension
to be uploaded.
Module based on core image and file modules and contrib svg_formatter.
In formatter settings you can set default image size and enable
alt and title attributes.

REQUIREMENTS
------------

Image

INSTALLATION
------------

1. Install module as usual via Drupal UI, Drush or Composer.
2. Go to "Extend" and enable the SVG image field module.

CONFIGURATION
----------------

Basic Field Configuration

1. Add "Svg Image" field to your content type or taxonomy vocabulary.
2. Go to the 'Manage display' => formatter settings and set image dimensions
 if you want and enable or disable attributes.

Media Entity Configuration (Requires Drupal >=8.7)

To skip the steps below, you can install the SVG Image Field Media Bundle to
import the configuration and have it set up for you, including the media bundle
and Acquia Site Studio components (if the requirements are installed).

1. Enable Media and Media Library modules from Drupal core.
2. Go to /admin/structure/media and click the "Add Media Type" button.
3. For the Media Type Name, specify "SVG".
4. For the Media source, select SVG from the dropdown.
5. In the field mappings map Name to Name (the only option in the dropdown).
6. Click save. Then proceed to 'Manage form display' and 'Manage display' to
   configure the Default and Media Library view modes at:

   * /admin/structure/media/manage/svg/form-display
   * /admin/structure/media/manage/svg/display

   You'll most likely want to just enable the "SVG" field, and disable the
   rest of the fields. Optionally, you may wish to choose the "inline SVG"
   option for the default display.
7. Add a Media reference (Entity reference) field to your node type(s) and
   configure it to allow the SVG bundle type.

   Optionally, you may enable other bundle types as well such as the standard
   Image media type if you want to allow both images and SVGs in the same slot.
8. Go create a node, and click the "Add media" link. Notice that the Media
   Library loads with a vertical tab for each kind of media type bundle you
   enabled in step 7 on your node configuration screen.
9. If using Acquia Site Studio, go to Site Studio > Sync packages > Import
   packages in the admin menu, and import the svg-components.package.yml file
   which can be found within the config/dx8 directory, inside the SVG Image
   Field Media Bundle submodule.

MAINTAINERS
-----------

* Martin Anderson-Clutz (mandclu) - https://www.drupal.org/u/mandclu
* James Wilson (jwilson3) - https://www.drupal.org/u/jwilson3
* Anton (shmel210) - https://www.drupal.org/u/shmel210

SIMILAR PROJECTS AND HOW THEY ARE DIFFERENT
-------------------------------------------
Limitations of module svg_formatter
- There is no way to set custom alt on image because it uses file field.
 File field does not support alt on db level.
- User must add svg extension at file field  settings and
select field formatter its not intuitive for user.
- If user uploads non svg file it will break output.
- if user uploads png and selects inline output at formatter settings it will
 break output
- It not have preview image on file upload.
- There is less ways what we can do with this all without breaking
existiing installations

Module svg_image_field does not have this weakness.
You simply click add field, set field type to "Svg Image" and its done.
As for me there is much less ways to shoot yourself in the leg
with svg_image_field:)
