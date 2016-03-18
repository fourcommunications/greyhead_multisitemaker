# greyhead_multisitemaker

## About
-

Multisite Maker is a quick 'n dirty way for you to let non-technical Drupal
users self-serve by setting up their own throwaway Drupal instances.

To enable, create a symlink from the www directory to the multisitemaker
directory, e.g.:

cd www
ln -s ../greyhead_multisitemaker

Copy the configuration-sample.php file to configuration.php and provide the
credentials for a MySQL user who has permission to create databases and users.

Lastly you need to make sure that subdomains of the domain at which your Drupal
site is hosted have a wildcard DNS entry pointing back to this Drupal webroot.

For example, if drupal.example.com points to the www directory, then so should
monkey.drupal.example.com, banana.drupal.example.com, etc.

Then, when you navigate to your.drupal.install/multisitemaker, you should see
the form to create a new Drupal multisite installation.

---

This code was originally developed to encourage non-technical people at a 
company I was contracting with to create a Drupal 7 site as easily as they 
would spin up a Wordpress site. 

As with everything I do, this is a work in progress, only works for my very 
narrow use-case, and will probably break the internet or something, so I 
wouldn't touch this code with a barge pole if I were you. 

Which I'm not, of course. I'm me. And now I'm confused...

Got that? Good. I'm off to the pub...

/Alex
