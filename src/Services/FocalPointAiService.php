<?php

namespace Drupal\focal_point_ai\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FocalPointAiService.
 */
class FocalPointAiService implements FocalPointAiServiceInterface {

  use StringTranslationTrait;

  /**
   * The azure API endpoint key.
   *
   * @var string
   */
  protected $apiKey;

  /**
   * The Computer Vision area of interest endpoint.
   *
   * @var string
   */
  protected $azureEndpoint;

  /**
   * Messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Messenger.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * FocalPointAiService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The saved configuration from the settings form.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger interface for generating messages.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The drupal file system service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger, FileSystem $file_system) {
    $config = $config_factory->get('focal_point_ai.settings');
    $this->apiKey = $config->get('api_key');
    $this->azureEndpoint = $config->get('azure_endpoint');
    $this->messenger = $messenger;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system')
    );
  }

  /**
   * Send an image to azure and gets information about it in return.
   *
   * @param mixed $imageData
   *   Stream of the image that will be analysed.
   *
   * @return string
   *   Json response object provided by azure Computer Vision.
   */
  public function analyzeImage($imageData) {
    try {
      $response = \Drupal::httpClient()->post($this->azureEndpoint, [
        'verify' => TRUE,
        'multipart' => [
          [
            'name' => 'Image',
            'contents' => $imageData,
          ],
        ],
        'headers' => [
          'Ocp-Apim-Subscription-Key' => $this->apiKey,
        ],
      ])->getBody()->getContents();
    }
    catch (\Exception $e) {
      $this->messenger->addMessage($e->getMessage());
      $response = FALSE;
    }

    return $response;
  }

  /**
   * Generates the image binary from a file entity.
   *
   * @param \Drupal\file\Entity\File $image
   *   The file entity (image).
   *
   * @return false|resource
   *   Binary data for the images.
   */
  public function getImageData(File $image) {
    $path = $this->fileSystem->realpath(($image->get('uri')->getValue()[0]['value']));
    $handle = fopen($path, 'r');
    return $handle;
  }

  /**
   * Calculates the point of interest based on the area of interest.
   *
   * @param string $response
   *   Json response from the azure API.
   *
   * @return string
   *   Coordinates for the focal point widget.
   */
  public function calculateFocalPoint(string $response) {
    $areaOfInterest = json_decode($response);
    // The calculation of the focal point is made by calculation the "middle" of
    // the Area of Interest Box and then adding the offsets. After that,
    // transform the values into percentages.
    $aoi_x = $areaOfInterest->areaOfInterest->w / 2 + $areaOfInterest->areaOfInterest->x;
    $aoi_y = $areaOfInterest->areaOfInterest->h / 2 + $areaOfInterest->areaOfInterest->y;

    // Good old rule of three.
    $x = round($aoi_x * 100 / $areaOfInterest->metadata->width);
    $this->messenger->addMessage('X:' . $x);
    $y = round($aoi_y * 100 / $areaOfInterest->metadata->height);
    $this->messenger->addMessage('Y:' . $y);

    return $x . ',' . $y;
  }

  /**
   * Sends a test request to check if endpoint and key are correct.
   *
   * @param string $imageUrl
   *   Public reachable image url (used for testing).
   *
   * @return string
   *   Message if the connection was successful.
   */
  public function testRequest($imageUrl) {
    if ($imageUrl) {
      try {
        $response = \Drupal::httpClient()->post($this->azureEndpoint, [
          'json' => [
            'url' => $imageUrl,
          ],
          'verify' => TRUE,
          'headers' => [
            'Content-type' => 'application/json',
            'Ocp-Apim-Subscription-Key' => $this->apiKey,
          ],
        ]);
      }
      catch (\Exception $e) {
        $this->messenger->addMessage($e->getMessage());
        return $this->t('Sorry, something went wrong, see error message above.');
      }
      if ($response->getReasonPhrase() == 'OK' && $response->getStatusCode() == 200) {
        return $this->t('Congratulations, your Endpoint is reachable and your key has been accepted.');
      }

    }
  }

}
