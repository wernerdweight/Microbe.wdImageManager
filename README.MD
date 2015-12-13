Werner Dweight's ImageManager Bundle
====================================

This utility adds support for image resizing and cropping to Microbe.

Installation
------------

1) Download using composer

```js
	{
		"require": {
			"wernerdweight/microbe-image-manager": "~1.0"
		}
	}
```

2) Enable the bundle

Enable the bundle in your kernel:

```php
	<?php
	/// app/appKernel.php

	public function registerBundles()
	{
		$bundles = array(
			/// ...
			new WernerDweight\ImageManagerBundle\WDImageManagerBundle(),
		);
	}
```

3) Setup config

```yml
# app/config/config.yml
wd_image_manager:
    upload_root: absolute/path/to/web/directory # typically %kernel.root_dir%/../web
    upload_path: relative/path/for/storing/images # e.g. uploads/images
    secret: thisIsNotSecret # you can use app secret (%secret%) or any other custom secret (needed for encryption)
    versions:
        version_name_x:	# this key will be used as name of directory to where this version of images will be saved
            width: 200	# desired image width in pixels (if you ommit width or height image will keep its original dimensions)
            height: 200 # desired image height in pixels (if you ommit width or height image will keep its original dimensions)
            crop: true	# set this to true if you want the resulting image to have EXACTLY the dimensions specified (default false)
        encrypted_version_name:
            encrypted: true	# if encrypted is set to true original image will be saved encrypted (intended for image download restrictions)
        ...
            
```

Usage
-----

Code to be used inside a controller:
```php
		/// get manager
		$im = $this->get('wd_image_manager');
		
		/// upload your image file(s), create database records etc.
		...

		/// process image (one by one)
		/// 1st parameter ($file) is instance of Symfony\Component\HttpFoundation\File\UploadedFile (mandatory)
		/// 2nd parameter is the filename of newly created file (mandatory)
		/// 3rd parameter is a path under upload_path from config (optional)
		$processedImageInfo = $im->processImage($file,'destination-filename','/optional/subpath');

```

License
-------
This bundle is under the MIT license. See the complete license in the root directiory of the bundle.
