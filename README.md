# updated-shopify_app-skeleton

OAuth HMAC validation will work now on Shopify by just changing the shopify.php file and oauth.php file.
This is for the public app in php.

1. First update your shopify.php file with my given file. For updating the shopify.php file you should go to the following directory

      /your_proj/vendor/phpish/shopify/shopify.php

    now your first step will done.
    
2. Secondly you just have update the oauth.php file form your project directory. It's path is following

      /your_proj/oauth.php
      
3. At third if you are getting some error that missing_URI or whitespace in uri on installetion of public app for shopify then you
   have to commit you conf.php file and just add there a single line for that.
   
      define('REDIRECT_URI', 'http://yourdomain.com/your_project/oauth.php');
