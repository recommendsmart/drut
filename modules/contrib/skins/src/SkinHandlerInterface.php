<?php

namespace Drupal\skins;

/**
 * Defines an interface to list available skins.
 */
interface SkinHandlerInterface {

  /**
   * Gets all available skins.
   *
   * @return array
   *   An array whose keys are skin names and whose corresponding values
   *   are arrays containing the following key-value pairs:
   *   - name: The human-readable name of the skin, to be shown on the
   *     skin administration page.
   *   - description: (optional) A description of what the skin looks like or
   *     does.
   *   - libraries: (optional) Any libraries
   *   - libraries-override: (optional) Any libraries
   *   - libraries-extend: (optional) Any libraries
   *   - provider: (optional) The provider name of the skin.
   */
  public function getSkins();

  /**
   * Gets all available skins for a given theme.
   *
   * @param string $theme_name
   *   The theme name.
   *
   * @return array
   *   An array whose keys are skin names and whose corresponding values
   *   are arrays.
   */
  public function getThemeSkins($theme_name);

  /**
   * Gets the names of all themes that provide skins.
   *
   * @return array
   *   An array of theme machine names.
   */
  public function getSkinThemes();

  /**
   * Determines whether a theme provides some skins.
   *
   * @param string $theme_name
   *   The theme name.
   *
   * @return bool
   *   Returns TRUE if the theme provides some skins, otherwise FALSE.
   */
  public function themeProvidesSkins($theme_name);

}
