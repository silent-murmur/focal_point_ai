<?php

namespace Drupal\focal_point_ai\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\focal_point_ai\Services\FocalPointAiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FocalPointAiSettingsForm.
 */
class FocalPointAiSettingsForm extends ConfigFormBase {

  /**
   * The focalPointAi service.
   *
   * @var Drupal\focal_point_ai\Services\FocalPointAiService
   */
  protected $focalPointAiService;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'focal_point_ai.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('focal_point_ai.default')
    );
  }

  /**
   * FocalPointAiSettingsForm constructor.
   *
   * @param Drupal\focal_point_ai\Services\FocalPointAiService $service
   *   The FocalPointAiService.
   */
  public function __construct(FocalPointAiService $service) {
    $this->focalPointAiService = $service;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('focal_point_ai.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#required' => TRUE,
      '#default_value' => $config->get('api_key'),
    ];

    $form['azure_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Azure Endpoint'),
      '#required' => TRUE,
      '#default_value' => $config->get('azure_endpoint'),
    ];

    // Testings the endpoint.
    $image = 'https://upload.wikimedia.org/wikipedia/commons/9/94/Bloodhound_Puppy.jpg';
    $response = $this->focalPointAiService->testRequest($image);

    $form['test_result'] = [
      '#title' => $this->t('Test result'),
      '#theme' => 'test_result',
      '#response' => $response,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('focal_point_ai.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('azure_endpoint', $form_state->getValue('azure_endpoint'))
      ->save();
  }

}
