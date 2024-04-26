<?php

namespace Drupal\glossary_tooltip\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Glossary Tooltip' formatter.
 *
 * @FieldFormatter(
 *   id = "glossary_tooltip",
 *   label = @Translation("Glossary Tooltip"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   }
 * )
 */
class GlossaryTooltipFormatter extends FormatterBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a StringFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')
    );
  }

  /**
   * Loads Glossary vocabulary terms.
   *
   * @return array
   *   Glossary vocabulary terms array.
   */
  private function loadGlossaryTerms() {
    $glossary_terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadTree('glossary', 0, NULL, TRUE);

    $glossary_terms_array = [];
    $max_tooltip_description_length = 100;

    foreach ($glossary_terms as $glossary_term) {
      $glossary_terms_array['glossary_terms'][] = $glossary_term->getName();

      // Generate tooltip markup.
      $tooltip_link = '<a class="glossary-tooltip-link">' . $glossary_term->getName() . '</a>';

      // Check if tooltip description exceeds 100 characters to add Read more button.
      $tooltip_description_text = $glossary_term->get('field_description')->value;
      if (strlen($tooltip_description_text) > $max_tooltip_description_length) {
        $glossary_term_link = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $glossary_term->id()])->toString();
        $tooltip_description_text = substr($tooltip_description_text,0,$max_tooltip_description_length) .
          '... <a class="read-more" target="_blank" href="' . $glossary_term_link . '">' . $this->t('Read more') . '</a>';
      }

      $tooltip_description = '<span class="glossary-tooltip-description hidden">' . $tooltip_description_text . '</span>';
      $glossary_terms_array['glossary_terms_elements'][] = $tooltip_link . $tooltip_description;
    }

    return $glossary_terms_array;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $glossary_terms = $this->loadGlossaryTerms();

    foreach ($items as $delta => $item) {
      $text = $item->value;

      // Replace all matches in text with tooltip markup.
      $processed_text = str_replace($glossary_terms['glossary_terms'], $glossary_terms['glossary_terms_elements'], $text);
      $elements[$delta] = [
        '#type' => 'processed_text',
        '#text' => $processed_text,
        '#format' => 'full_html',
        '#langcode' => $item->getLangcode(),
      ];
    }

    $elements['#attached']['library'][] = 'glossary_tooltip/glossary_tooltip';
    $elements['#cache']['tags'][] = 'taxonomy_term_list:glossary';
    return $elements;
  }

}
