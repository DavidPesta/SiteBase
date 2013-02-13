SiteBase
========

A simple framework of boilerplate code for quickly starting new PHP web projects.

<b>Initial Release:</b> May 26, 2012<br>
<b>Last Updated:</b> February 12, 2013


## Documentation

### Introduction

Here is what SiteBase gives you "out of the box" over starting with plain vanilla PHP:

- Project folder organization

- Separation of concerns for page-specific micro-MVC architectures

- Flexible controllers that can either serve an MVC page or an AJAX request

- Nested view layouts and page content

- A bootstrap to initialize configurations that serve both pages and AJAX requests

- An installation independent config file

- A "current" folder project update strategy

- A clean consistent webroot folder

- Keeps PHP code out of webroot for source code security

- Automatic error logging

- SEO-friendly URLs

- Consolidates CSS and JS files into single files to speed up page load times

- Easily auto-include new CSS and JS files by just adding them to the project

- Prevents CSS and JS changes from being cached via unique MD5 filename signatures

- Auto-compiles CoffeeScript and LESS files


### Setup

1. Create a /websites folder for all websites

2. Create a /websites/example.com folder for the example website

3. Place the config.php file into /websites/example.com/config.php

4. Place the contents of "current" into a /websites/example.com/YYYY-MM-DD folder where YYYY-MM-DD is the current date

5. Create a symbolic link called "current" that points to the /websites/example.com/YYYY-MM-DD folder
   - ln -sfn YYYY-MM-DD current

6. Configure /websites/example.com/current/webroot as the document root of the website

7. Use the following as your rewrite rule:

---

    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^([\/\-?#A-Za-z0-9]+)$ router.php

---


### Updating

1. Create a new /websites/example.com/YYYY-MM-DD folder

2. Put all of your new scripts into that folder

3. Change the "current" symbolic link to the new folder
   - ln -sfn YYYY-MM-DD current


### Usage

- All scripts and images get organized inside of /websites/example.com/current/source

- Place images, .css, .js, .less, and .coffee files anywhere within the source folder and they will be automatically handled and placed in webroot
  - Their relative locations within webroot will be analogous to their relative locations within source
  - Site templates and CSS frameworks like Twitter Bootstrap, etc should all go into the source folder and it should work instantly

- Controllers are ordinary .php files
  - To use a controller for an AJAX request, just echo the response
  - To use a controller for a page, utilize the Page class in /websites/example.com/current/library/Page.php

- View files just contain HTML and PHP assisted markup and reside inside of phtml files

- Inside of a controller, utilize the Page class to coordinate the rendering of the view files for a page
  - Include the Page library: include LIBRARY . "/Page.php";
  - Create a new page object: $page = new Page();
  - Register view files with $page->addViewFiles and pass to it view names as keys and absolute view locations as values
  - Pass variables from the controller to the view like this: $page->var1 = "one";
  - Finally, the controller shall render the view: $page->show();

- Inside of view files (usually the layout), invoke the site's CSS file like this: /&lt;?= $this->_cssMd5 ?>.css

- Inside of view files (usually the layout), invoke the site's JS file like this: /&lt;?= $this->_jsMd5 ?>.js

- Inside of view files, you can invoke the webroot location analogous to the current view file like this: &lt;?= $this->folder() ?>

- Inside of view files, invoke the rendering of other registered view files like this: $this->content( "otherRegisteredView" )
  - You can have any number of nested view files with multiple nested layouts, etc

- Inside of view files, you can invoke variables passed to it from the controller: &lt;?= $var1 ?&gt; or &lt;? if( $var1 == true ): ?&gt;

- If PRODUCTION is "false" inside of the config.php file, then webroot will be cleared out of everything (except router.php) and replaced every page load
  - Every time you make a change to a .css, .js, .less, or .coffee file, the MD5 signature of the .js and .css file in the webroot gets changed

- The config.php file can hold installation specific configurations for anything you wish, allowing you to maintain different settings for production, demo, test, etc
  - This config.php file will not be affected by any project development updates if following the "Updating" protocol above

- The bootstrap.php is where you want to put configurations and setup that IS supposed to be applied to every install
  - Very useful constants that define locations all throughout the project reside here: ROOT, LIBRARY, SOURCE, WEBROOT, DATA, and ERRORS

- The /websites/example.com/current/library folder is where you want to keep all reusable library code

- Data that is needed for various system operations that isn't a good fit for the database can be organized into the /websites/example.com/current/data folder

- If the system encounters any errors, the /websites/example.com/current/errors folder will start getting populated with files

- There may be times when the order in which the JS or CSS files is important, which is when you put something like the following inside of the bootstrap:

---

    include LIBRARY . "/Page.php";
    Page::init([
      'cssOrder' => [
        '/test-page/styles/style.less',
        '/test-page/styles/style.css'
      ],
      'jsOrder' => [
        '/test-page/scripts/script.js',
        '/test-page/scripts/script.coffee'
      ]
    ]);

---

- You can put as many or as few of the files in there as you like. You don't have to put all (or any) of them in there, and the ones that you do put in there will be placed first before anything else.


### Best Practices

- Place favicon.ico inside of /source

- If a data file is needed by a library or some other system function, it is best to have the library check to make sure the data is present rather than just assume it exists
  - That way, the data folder does not need to exist in the product, but gets created when needed
  - if( ! is_dir( DATA . "/Page" ) ) mkdir( DATA . "/Page", 0777, true );

- If a data file is needed by a library, create a folder inside of the data folder with the name of the library, then put the data files for the use of that library in there


## License

The MIT License

Copyright (c) 2012-2013 David Pesta

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
'Software'), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
