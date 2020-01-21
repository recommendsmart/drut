CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Group SAML (gsaml) module allows you to manage group permissions based
on a selected user attribute. The module makes use of the following
configurations: an array of the user attributes, an array of group roles and
an array of terms. It then creates a group for each term. The combination of
groups with roles creates a matrix which is filled with the strings from the
user entity.

Therefore it is possible to manage user access to content and media by
taxonomy term.


REQUIREMENTS
------------

This module requires the following modules:

 * Group (https://www.drupal.org/project/group)
 * Group Media (https://www.drupal.org/project/groupmedia)


RECOMMENDED MODULES
-------------------

 * simpleSAMLphp Authentication (https://www.drupal.org/project/simplesamlphp_auth):
   When enabled, makes it possible for Drupal to communicate with SAML or Shibboleth
   identity providers (IdP) for authenticating users.

 * SAML Extras (https://www.drupal.org/project/saml_extras):
   When enabled, maps user fields with simpleSAMLphp attributes during
   user authentication.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.


CONFIGURATION
-------------

 * Configure the user permissions in Administration » Groups » SAML:

   - Select the vocabulary

   - Select the group type

   - Select the user field

   - Select the group associated with the vocabulary

   - Select the node fields related to vocabulary

   - Select the node fields related to vocabulary

   - Select the media fields related to vocabulary

   - Fill the group by roles matrix

     In each box of the matrix you should add the string which provide access
     to that combination of group/role.

   - Save the configuration

   - Return to the configration page and click 'Associate group with term'

     In case you already have created content/media/users click:
      Associate content
      Associate Members
      Save Nodes
      Save Media


MAINTAINERS
-----------

Current maintainers:
 * Debora Antunes (dgaspara) - https://www.drupal.org/u/dgaspara
 * Nuno Ramos (-nrzr-) - https://www.drupal.org/u/nrzr
 * João Marques (joaomarques736) - https://www.drupal.org/u/joaomarques736
 * Ricardo Tenreiro (ricardotenreiro) - https://www.drupal.org/u/ricardotenreiro

This project has been sponsored by:
 * everis
    Multinational consulting firm providing business and strategy solutions,
    application development, maintenance, and outsourcing services. Being part of
    the NTT Data group enables everis to offer a wider range of solutions and
    services through increased capacity as well as technological, geographical,
    and financial resources.

