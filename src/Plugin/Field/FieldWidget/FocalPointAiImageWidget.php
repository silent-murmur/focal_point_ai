<?php

namespace Drupal\focal_point_ai\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\file\Entity\File;
use Drupal\focal_point\Plugin\Field\FieldWidget\FocalPointImageWidget;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\focal_point_ai\Services\FocalPointAiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'image_focal_point_ai' widget.
 *
 * @FieldWidget(
 *   id = "image_focal_point_ai",
 *   label = @Translation("Image (Focal Point AI)"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class FocalPointAiImageWidget extends FocalPointImageWidget {

  /**
   * @var FocalPointAiService focalPointAiService
   *   The Service that provides functions to communicate with MS Azure.
   */
  protected $focalPointAiService;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ElementInfoManagerInterface $element_info, FocalPointAiService $focalPointAiService) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings, $element_info);
    $this->focalPointAiService = $focalPointAiService;
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
      $configuration['third_party_settings'],
      $container->get('element_info'),
      $container->get('focal_point_ai.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $element = parent::process($element, $form_state, $form);

    // @TODO insert multi file support, just works for the first file for now.
    $file = reset($element['#files']);
    if (!$file) {
      return $element;
    }

    // @TODO find a better way to check if an focal point exists.
    // Just send a request if no value is set, don't overwrite existing values.
    #if ($element['focal_point']['#default_value'] != '50,50') {
      /** @var  \Drupal\focal_point_ai\Services\FocalPointAiService */
      $service = \Drupal::service('focal_point_ai.default');
      $response = $service->analyzeImage($service->getImageData($file));
      if ($response) {
        $element['focal_point']['#default_value'] = $service->calculateFocalPoint($response);
      }
    #}

    return $element;
  }

}
