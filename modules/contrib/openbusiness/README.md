INTRODUCTION
------------
* OpenBusiness is an installation profile for Drupal 8 and offers an highly customizable, responsive and lightweight experience. Based on Bootstrap 3, compatible with Color module, built with Paragraphs module for an easier way to create content and translate it so that it reaches as many people as possible, the main purpose of OpenBusiness is to serve as a base for any presentation website you might want to create.


REQUIREMENTS
------------
* The latest version of Drupal.


INSTALATION
-----------
In order to install the OpenBusiness profile, please follow the below steps:
1. Download a 8.x version of Drupal.
2. Download 'OpenBusiness' profile.
  * Via Download tag.gz / zip
    - You need to move 'openbussines_profile' folder located in 'openbusiness' to 'profiles' from your Drupal root directory.
  * Via Composer
    - Composer will download the profile folder into ``` root/modules/contrib/openbusiness ```. You need to move 'openbusiness_profile' folder to 'profiles' from your Drupal root directory.
  * Via Git
    1. Delete 'profiles' folder from your Drupal root.
    2. At your Drupal root level insert 'git clone' command with 'profiles' parameter at the end of the command.
       Example: ``` git clone --branch x git@git.drupal.org:project/openbusiness.git profiles ```
       Check if the path is ``` root/profiles/openbusiness_profile ```.
3. Run 'composer update' in the 'openbusiness_profile' folder.
4. Select 'OpenBusiness' profile at "Select an installation profile" step.
5. Complete the remaining steps.
From here onwards, you are ready for customizing your experience.


CONFIGURATION
-------------
* Managing the layout: OpenBusiness is built mainly upon blocks.

* Structure

  <dl><dt>Content Types</dt>
    <dd> - Article </dd>
    <dd> - Basic Page (used for About section)</dd>
    <dd> - Portofolio (used for Portfolio section)</dd>
    <dd> - Testimonials (used for feedback from previous clients).</dd>

    <dt> Article is used for Blog Section and it contais: </dt>
      <dd>• Fields: Body, Image, Tags taxonomy used “tags”.</dd>
      <dd>• View: Blog has 3 views: Blog is a block view used on front page. Page is a page view used to display a list with all blogs. Block Contextual is a block view for contextual filter and is placed on the footer of blog nodes.</dd>
      <dd>• Requirements creating content: Add alias: /article/* </dd>

    <dt> Basic-page is used for About Section and another simple page (e.g. Style guide) and it contais: </dt>
      <dd>• Fields: Body. </dd>
      <dd>• View: About - is a block view used on the front page with a restriction of 100 words and after that will appear a button to expand all body on the same page. You can change the number of words from: Views -> About -> Fields[Body] and there you have all the settings.</dd>
      <dd>• To create or change the content for About section you need to use “Basic page” CT and to be “promoted to front page”. </dd>

    <dt> Portofolio is used for Portfolio section and another simple page and it contais: </dt>
      <dd>• Fields: Body, Image with a minimum resolution (905x550). </dd>
      <dd>• View: Portofolio Block. </dd>
      <dd>• Requirements creating content: Add alias: /portofolio/* </dd>

    <dt> Testimonials is used for Testimonials section and another simple page and it contains: </dt>
      <dd>• Fields: Body, Image, Role. </dd>
      <dd>• View: Testimonials Block. </dd>
  </dl>

  <dl><dt>Block types:</dt>
    <dd>• Basic block - used to create the Footer Copyright block;</dd>
    <dd>• Hero Image - used to create the Hero Image block and this has 2 image fields for Portrait (max. 768px) and Landscape (min. 768px) screen resolution.</dd>
  </dl>

  <dl><dt>Menus:</dt>
    <dd>• Main navigation is used in 3 regions (Navigation collapsible / Hero Image / Footer);</dd>
    <dd>• Social Links is placed in footer;</dt>
      <dl><dt>Specs for Social Links:</dt>
        <dd>- .twig template was rewrite to hide text and display icons.</dd>
        <dd>- icons can be found in the profile folder -> themes -> openbusiness_theme -> images -> svg -> social-footer.</dd>
        <dd>- to add more icons or change it you have to add icons with the same name you've set in Drupal Menu Interface (e.g. [Menu link title: facebook] -> “facebook.svg”).</dd>
      </dl>
    <dd>• Terms & Privacy is placed in the footer region.</dd>
    <dd>• All menus can be edited from Drupal Interface.</dd>
  </dl>

  <dl><dt>Anchor: To create anchors follow the below steps:</dt>
    <dd>1. You have to create a link and on the "Link" field you have to set "#example" (hashtag is necessary).</dd>
    <dd>2. Go to "Views", edit the view and on "Title" field you need to put the same "Link" but without hashtag.</dd>
    <dd>3. You can change the name of the section from "Header -> Text area".</dd>
    <dd>If you reuse an anchor just set the same name on the “Link” field from “Menus” with the title of the wanted view.</dd>
  </dl>


* Changing the color scheme: You will be able to change the color for the following elements: Body Background (Gradient background compound by Secondary and Background colors), Logo, Headings, Menus elements, Buttons background, Footer, Text and Hover. Some of the elements are not color-change compatible such as: Buttons text, Footer text.

* Paragraphs: OpenBusiness uses “Paragraphs” contributed modules for an easier way to create content. Blockquote, Paragraph with basic text, Carousel, Image list and Paragraph with image are stylized elements you can use.

* Components: OpenBusiness uses Bootstrap components and these were stylized in accordance with design. You will find components: Tooltips, Buttons, Dropdown, Alerts, Tags, Pager, Modal. You can use these in node content or in view / block elements.

<dl><dt> Cards - OpenBusiness presents 3 types of view cards called:</dt>
  <dd>* Option 1: card-type-circle</dd>
  <dd>* Option 2: card-type-square</dd>
  <dd>* Option 3: card-type-radius</dd>
  <dd>The cards can be used or placed in any view, of any type, with any content type if the following instructions are followed for each cards.</dd>
</dl>

<dl><dt>Option 1 - Card-Type-Circle</dt>
<dd>To use this pre-stylized view card, it must have a content type with following fields: Tags (Taxonomy term), Image, Title. To create the view go in Drupal Dashboard -> Structure -> View -> Add View. View name: e.g. - Card-Type-Circle (can be any) -> Show Content of type (Content type described above - e.g. OpenBusiness - Article) -> rest fill with your preferences. The global styling is applied for page or block type view with minimum and maximum 4 columns in a row. Check Create a page or check Create a block. In Page Display Settings label choose Display format HTML List of Fields; Items to display: 0 - ALL or minimum 4. Uncheck Use a pager - to implement this you need to use another method described above. To create a block, in Block Display Settings label choose the same values -> Save View. In view settings page open from Format Label -> Format -> Settings and fill</dd>
  <dd>• Row class: card-type-circle-item col-lg-3 col-md-4 col-sm-4</dd>
  <dd>• List class: card-type-circle-list</dd>
  <dd>Apply</dd>
  <dd>In Fields Label -> ADD and search: Tags, Image, Title, Authored by, Authored on, Custom Text.</dd>
  <dd>The fields order it must be: 1.TAGS  2.IMAGE  3.TITLE  4.AUTHORED BY  5.AUTHORED ON  6. CUSTOM TEXT (you can rearrange from dropdown near the add field). Click on each one and configure:</dd>
  <dd>• TAGS - Check "Link label to the reference entity", check "Customize field HTML" and fill CSS class with: "card-tag".</dd>
  <dd>• IMAGE - Select image style - "Card-Type-Circle" ; Link image to: Content. Check customize field HTML and fii CSS class with: "card-cirlce-image".</dd>
  <dd>• TITLE - Check "Link to the Content" , Check Customize field HTML, select H3 HTML Element and fill CSS Class with: "card-circle-title".</dd>
  <dd>• AUTHORED BY - Check "Exclude from display".</dd>
  <dd>• AUTHORED ON - Check "Exculde from display" ; Date format: Custom ; Custom date format fill with: "M. d, g:i a".</dd>
  <dd>• CUSTOM TEXT - Text: "div class="card-circle-auth-by">by {{ uid }}"div class="card-circle-auth-on" {{ created }} /div". Check "Customize field HTML and fill CSS Class with: 'card-circle-info'.</dd>
</dl>


<dl><dt>Option 2 - Card-Type-Square</dt>
<dd> To use this pre-stylized view card, it must have a content type with following fields: Image, Title. To create the view go in Drupal Dashboard -> Structure -> View -> Add View. View name: e.g. - Card-Type-Radius (can be any) -> Show Content of type (Content type described above - e.g. OpenBusiness -  Article, Testimonials, Portofolio) -> rest fill with your preferences. The global styling is applied for page or block type view with minimum and maximum 3 columns in a row. Check Create a page or check Create a block. In Page Display Settings label choose Display format HTML List of Fields; Items to display: 0 - ALL or minimum 3. Uncheck Use a pager - to implement this you need to use another method described above. To create a block, in Block Display Settings label choose the same values -> Save View. In view settings page open from Format Label -> Format -> Settings and fill</dd>
  <dd>• Row class: card-type-square-item col-lg-4 col-sm-4</dd>
  <dd>• List class:   card-type-square-list</dd>
  <dd>Apply</dd>
  <dd>In Fields Label -> ADD and search: Image, Title</dd>
  <dd>The fields order it must be: 1.IMAGE  2.TITLE (you can rearrange from dropdown near the add field). Click on each one and configure:</dd>
  <dd>• IMAGE - Select image style - "Card-Type-Square" ; Link image to: Content. Check customize field HTML and fii CSS class with: "card-square-image".</dd>
  <dd>• TITLE - Check Customize field HTML, select H3 HTML Element and fill CSS Class: "card-square-title".</dd>


<dl><dt>Option 3 - Card-Type-Radius</dt>
<dd>To use this pre-stylized view card, it must have a content type with following fields: Title, Image, Text - formatted (e.g. Role), Body. To create the view go in Drupal Dashboard -> Structure -> View -> Add View. View name: e.g. - Card-Type-Radius (can be any) -> Show Content of type (Content type described above - e.g. OpenBusiness - Testimonials) -> rest fill with your preferences. The global styling is applied for page or block type view with minimum and maximum 3 columns in a row. Check Create a page or check Create a block. In Page Display Settings label choose Display format HTML List of Fields; Items to display: 0 - ALL or minimum 3. Uncheck Use a pager - to implement this you need to use another method described above. To create a block, in Block Display Settings label choose the same values -> Save View. In view settings page open from Format Label -> Format -> Settings and fill</dd>
  <dd>• ow class: card-type-radius-item col-lg-4 col-sm-6</dd>
  <dd>• List class: card-type-radius-list</dd>
  <dd>Apply</dd>
  <dd>In Fields Label -> ADD and search: Title, Role, Image, Body.</dd>
  <dd>The fields order it must be: 1.TITLE  2.ROLE  3.IMAGE  4.BODY (you can rearrange from dropdown near the add field). Click on each one and configure:</dd>
  <dd>• TITLE - Check Customize field HTML, select H3 HTML Element and fill CSS Class: "card-radius-tilte".</dd>
  <dd>• ROLE  - Check Customize field HTML and fill CSS Class: "card-radius-role".</dd>
  <dd>• IMAGE - Check Customize field HTML fill CSS Class: "card-radius-image".</dd>
  <dd>• BODY - Check Customize field HTML, select P HTML Element and fill CSS Class: "card-radius-body".</dd>


TROUBLESHOOTING / KNOWN ISSUES
------------------------------
* If you're using the profile on Windows and after installation the layout is broken you need to deactivate Aggregation options. This problem persist only if you'are logged in with admin user **(users and anonymous users are not affected)**. Go to Configuration -> Performance and uncheck 'Aggregate CSS files' and 'Aggregate JavaScript files'. However, it is not recommended to uncheck these options when the site is live, so after you made changes at Drupal settings level, please check back Aggregate options.
