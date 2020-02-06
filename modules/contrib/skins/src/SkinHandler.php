<?php

namespace Drupal\skins;

use Drupal\Core\Discovery\YamlDiscovery;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides the available skins based on yml files.
 *
 * To define skins you can use a $theme.skins.yml file. This file defines
 * machine names, human-readable names, style sheets, template directories,
 * and screenshot files.
 */
class SkinHandler implements SkinHandlerInterface {

  use StringTranslationTrait;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The YAML discovery class to find all .skins.yml files.
   *
   * @var \Drupal\Core\Discovery\YamlDiscovery
   */
  protected $yamlDiscovery;

  /**
   * Constructs a new SkinHandler.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler to invoke the alter hook with.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(ThemeHandlerInterface $theme_handler, TranslationInterface $string_translation) {
    $this->themeHandler = $theme_handler;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Gets the YAML discovery.
   *
   * @return \Drupal\Core\Discovery\YamlDiscovery
   *   The YAML discovery.
   */
  protected function getYamlDiscovery() {
    if (!isset($this->yamlDiscovery)) {
      $this->yamlDiscovery = new YamlDiscovery('skins', $this->themeHandler->getThemeDirectories());
    }
    return $this->yamlDiscovery;
  }

  /**
   * {@inheritdoc}
   */
  public function getSkins() {
    static $all_skins;

    if (!isset($all_skins)) {
      $all_skins = $this->buildSkinsYaml();
      $all_skins = $this->sortSkins($all_skins);
    }

    return $all_skins;
  }

  /**
   * {@inheritdoc}
   */
  public function getThemeSkins($theme_name) {
    $all_skins = $this->getSkins();

    $theme_skins = array_filter($all_skins, function($skin) use ($theme_name) {
      return $skin['provider'] === $theme_name;
    });

    return $theme_skins;
  }

  /**
   * {@inheritdoc}
   */
  public function getSkinThemes() {
    $all_skins = $this->getSkins();

    $theme_skins = array_unique(array_map(function ($skin) {
      return $skin['provider'];
    }, $all_skins));

    return $theme_skins;
  }

  /**
   * {@inheritdoc}
   */
  public function themeProvidesSkins($theme_name) {
    $theme_skins = $this->getThemeSkins($theme_name);

    return !empty($theme_skins);
  }

  /**
   * Builds all skins provided by .skins.yml files.
   *
   * @return array[]
   *   An array of skins.
   */
  protected function buildSkinsYaml() {
    $all_skins = [];

    foreach ($this->getYamlDiscovery()->findAll() as $provider => $skins) {
      foreach ($skins as $skin_id => $skin) {
        $skin['name'] = $this->t($skin['name']);
        $skin['description'] = isset($skin['description']) ? $this->t($skin['description']) : NULL;
        $skin['provider'] = $skin['provider'] ?? $provider;
        $all_skins[$skin_id] = $skin;
      }
    }

    return $all_skins;
  }

  /**
   * Sorts the given skins by provider name and skin name.
   *
   * @param array $skins
   *   The skins to be sorted.
   *
   * @return array[]
   *   An array of skins.
   */
  protected function sortSkins(array $all_skins = []) {
    // Get a list of all the themes providing skins and sort by
    // display name.
    $themes = $this->getThemeNames();

    uasort($all_skins, function (array $skin_a, array $skin_b) use ($themes) {
      if ($themes[$skin_a['provider']] == $themes[$skin_b['provider']]) {
        return $skin_a['name'] > $skin_b['name'];
      }
      else {
        return $themes[$skin_a['provider']] > $themes[$skin_b['provider']];
      }
    });

    return $all_skins;
  }

  /**
   * Returns all theme names.
   *
   * @return string[]
   *   Returns the human readable names of all themes keyed by machine name.
   */
  protected function getThemeNames() {
    $themes = [];
    foreach (array_keys($this->themeHandler->listInfo()) as $theme_name) {
      $themes[$theme_name] = $this->themeHandler->getName($theme_name);
    }
    asort($themes);
    return $themes;
  }

}
