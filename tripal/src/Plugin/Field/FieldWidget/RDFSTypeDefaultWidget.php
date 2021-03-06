<?php

namespace Drupal\tripal\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tripal\Plugin\Field\TripalWidgetBase;
use Drupal\tripal\Entity\TripalEntityType;

/**
 * Plugin implementation of the 'rdfs__type_widget' widget.
 *
 * @FieldWidget(
 *   id = "rdfs__type_widget",
 *   module = "tripal",
 *   label = @Translation("Tripal Content Type"),
 *   field_types = {
 *     "rdfs__type"
 *   }
 * )
 */
class RDFSTypeDefaultWidget extends TripalWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 60,
      'placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size'] = [
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // Get the value.
    $entity = $items[0]->getEntity();
    $entityType_id = $entity->getType();
    $entityType = TripalEntityType::load($entityType_id);

    $element['value'] = $element + [
      '#type' => 'textfield',
      '#default_value' => $entityType->getLabel(),
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#disabled' => TRUE,
    ];

    return $element;
  }

}
