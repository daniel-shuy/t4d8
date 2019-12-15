<?php

namespace Drupal\tripal\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class TripalEntityTypeForm.
 *
 * @package Drupal\tripal\Form
 */
class TripalEntityTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $tripal_entity_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $tripal_entity_type->label(),
      '#description' => $this->t("Label for the Tripal Content type."),
      '#required' => TRUE,
    ];


    // Determine the machine name for the content type.
    if ($tripal_entity_type->isNew()) {
      // @todo set this dynamically.
      $default_id = 'bio_data_' . 1;
    }
    else {
      $default_id = $tripal_entity_type->id();
    }
    $form['name'] = [
      '#type' => 'machine_name',
      '#default_value' => $default_id,
      '#machine_name' => [
        'exists' => '\Drupal\tripal\Entity\TripalEntityType::load',
      ],
      '#disabled' => !$tripal_entity_type->isNew(),
    ];

    // We need to choose a term if this is a new content type.
    // The term cannot be changed later!
    if ($tripal_entity_type->isNew()) {
      $description = t('The Tripal controlled vocabulary term (cv) term which characterizes this content type. For example, to create a content type for storing "genes", use the "gene" term from the Sequence Ontology (SO). <strong>The Tripal CV Term must already exist; you can <a href="@termUrl">add a Tripal CV Term here</a>.</strong>',
        ['@termUrl' => Url::fromRoute('entity.tripal_vocab.collection')->toString()]);
      $form['term_id'] = [
        '#type' => 'entity_autocomplete',
        '#title' => 'Tripal Controlled Vocabulary (CV) Term',
        '#description' => $description,
        '#target_type' => 'tripal_term',
      ];
    }
    else {
      $term = $tripal_entity_type->getTerm();
      $vocab = $term->getVocab();
      // Save the term for later.
      $form['term_id'] = [
        '#type' => 'hidden',
        '#value' => $term->getId(),
      ];
      // Describe the term to the user but do not allow them to change it.
      $form['term'] = [
        '#type' => 'table',
        '#caption' => 'Controlled Vocabulary Term',
        '#rows' => [
          [
            ['header' => TRUE, 'data' => 'Vocabulary'],
            $vocab->getLabel()
          ],
          [
            ['header' => TRUE, 'data' => 'Name'],
            $term->getName()
          ],
          [
            ['header' => TRUE, 'data' => 'Accession'],
            $term->getAccession()
          ],
        ],
        '#weight' => -8,
      ];
    }

    // Allow the administrator to set help text for users.
    $form['help'] = [
      '#type' => 'textarea',
      '#title' => 'Help Text',
      '#description' => 'This is shown to administrators to further explain this Tripal content type. For example, this can be used to provide an example or site-specific instructions.',
      '#default_value' => $tripal_entity_type->getHelpText(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $tripal_entity_type = $this->entity;

    // Set the basic values of a Tripal Entity Type.
    $tripal_entity_type->setName($values['name']);
    $tripal_entity_type->setLabel($values['label']);
    $tripal_entity_type->setHelpText($values['help']);
    $tripal_entity_type->setTerm($values['term_id']);

    // Finally, save the entity we've compiled.
    $status = $tripal_entity_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Tripal Content Type.', [
          '%label' => $tripal_entity_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved changes %label Tripal Content Type.', [
          '%label' => $tripal_entity_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($tripal_entity_type->urlInfo('collection'));
  }

}
