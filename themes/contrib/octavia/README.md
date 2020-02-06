# Octavia

Octavia is a subtheme of base theme Bulma for Drupal. 
It has Bulma CSS framework, Sass, and Font Awesome built in.

## Getting Started

### Browser Support
Autoprefixer & Babel is set to support:

* IE >= 9
* Last 3 versions of modern browsers.

These can be updated at any time within the `package.json`.

### Run the following commands from the theme directory
If you haven't yet, install nvm:
https://github.com/creationix/nvm

#### Use the right version of node with:
`nvm use`

_This command will look at your `.nvmrc` file and use the version node.js specified in it. This ensures all developers use the same version of node for consistency._

#### If that version of node isn't installed, install it with:
`nvm install`

#### Install npm dependencies with
`npm install`

_This command looks at `package.json` and installs all the npm dependencies specified in it.  Some of the dependencies include gulp, autoprefixer, gulp-sass and others._

#### Gulp tasks

Provided by default are seven npm scripts that point to Gulp tasks. We run gulp through npm scripts so the build tools can change without the user ever knowing.

1. Run the default build task (gulp in this instance) and everything in it.
  This is the equivalent to running `gulp` on the command line with Gulp installed globally.
  ```
  npm run build
  ```

2. Compile Sass and JS.
  ```
  npm run compile
  ```

3. Watch files and run tasks when they change.
  ```
  npm run watch
  ```

4. Compress png and svg assets.
  ```
  npm run compress
  ```

5. Build the KSS Style guide.
  ```
  npm run styleguide
  ```

6. Lint Sass and JS files.
  ```
  npm run lint
  ```

7. Delete compiled Sass, JS and style guide files from the /dist directory.
  ```
  npm run clean
  ```

<!-- writeme -->
Octavia
=======

A base theme for the Drutopia distribution based on Bulma.

 * https://gitlab.com/drutopia/octavia
 * Issues: https://gitlab.com/drutopia/octavia/issues
 * Source code: https://gitlab.com/drutopia/octavia/tree/8.x-1.x
 * Keywords: theme, flexbox, bulma, templates, styles, drutopia
 * Package name: drupal/octavia


### Requirements

 * drupal/bulma ^1.0-alpha2


### License

GPL-2.0+

<!-- endwriteme -->
