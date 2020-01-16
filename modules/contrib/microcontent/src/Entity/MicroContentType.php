<?php

namespace Drupal\microcontent\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the microcontent type entity.
 *
 * @ConfigEntityType(
 *   id = "microcontent_type",
 *   label = @Translation("Micro-content type"),
 *   label_singular = @Translation("micro-content type"),
 *   label_plural = @Translation("micro-content types"),
 *   label_collection = @Translation("Micro-content types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count micro-content type",
 *     plural = "@count micro-content types"
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\microcontent\EntityHandlers\MicrocontentTypeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\microcontent\Form\MicroContentTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer microcontent types",
 *   config_prefix = "type",
 *   bundle_of = "microcontent",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/microcontent-types/add",
 *     "delete-form" = "/admin/structure/microcontent-types/manage/{microcontent_type}/delete",
 *     "reset-form" = "/admin/structure/microcontent-types/manage/{microcontent_type}/reset",
 *     "overview-form" = "/admin/structure/microcontent-types/manage/{microcontent_type}/overview",
 *     "edit-form" = "/admin/structure/microcontent-types/manage/{microcontent_type}",
 *     "collection" = "/admin/structure/microcontent-types",
 *   },
 *   config_export = {
 *     "name",
 *     "id",
 *     "description",
 *     "type_class",
 *   }
 * )
 */
class MicroContentType extends ConfigEntityBase implements MicroContentTypeInterface {

  /**
   * The pane set type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Name of the set type.
   *
   * @var string
   */
  protected $name;

  /**
   * Description of the pane set type.
   *
   * @var string
   */
  protected $description = '';

  /**
   * Type class.
   *
   * @var string
   */
  protected $type_class = '';

  /**
   * {@inheritdoc}
   */
  public function getDescription() : string {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeClass() : string {
    return $this->type_class;
  }

}
