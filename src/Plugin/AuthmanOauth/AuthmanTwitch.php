<?php

declare(strict_types = 1);

namespace Drupal\authman_twitch\Plugin\AuthmanOauth;

use Depotwarehouse\OAuth2\Client\Twitch\Entity\TwitchUser;
use Drupal\authman\AuthmanOauth;
use Drupal\authman\Plugin\AuthmanOauthPluginBase;
use Drupal\authman\Plugin\KeyType\OauthClientKeyType;
use Drupal\authman_twitch\AuthmanTwitchProvider;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\key\KeyInterface;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Twitch OAuth provider.
 *
 * @AuthmanOauth(
 *   id = "authman_twitch",
 *   label = @Translation("Twitch"),
 *   refresh_token = TRUE,
 * )
 *
 * @internal
 */
class AuthmanTwitch extends AuthmanOauthPluginBase implements ConfigurableInterface, PluginFormInterface, ContainerFactoryPluginInterface {

  // todo scopes: changing scopes requires reauth...!

  /**
   * HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->httpClient = $container->get('http_client');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): void {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'scopes' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance(array $providerOptions, KeyInterface $clientKey): AuthmanOauth {
    $keyType = $clientKey->getKeyType();
    assert($keyType instanceof OauthClientKeyType);
    $provider = $this->createProvider($providerOptions, $clientKey);
    return new AuthmanOauth($provider);
  }

  /**
   * {@inheritdoc}
   */
  protected function createProvider(array $providerOptions, KeyInterface $clientKey): AbstractProvider {
    // Include 'scopes' only if its not empty.
    $scopes = $this->getConfiguration()['scopes'] ?? [];
    if (count($scopes) > 0) {
      // Combine custom scopes.
      $providerOptions['scopes'] = ($providerOptions['scopes'] ?? []) + $scopes;
    }

    $values = $clientKey->getKeyValues();
    $provider = new AuthmanTwitchProvider([
      'clientId' => $values['client_id'],
      'clientSecret' => $values['client_secret'],
    ] + $providerOptions);
    $provider->setHttpClient($this->httpClient);
    return $provider;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $options = [];
    $options['analytics:read:extensions'] = [$this->t('Analytics: extensions'), $this->t('View analytics data for your extensions.')];
    $options['analytics:read:games'] = [$this->t('Analytics: games'), $this->t('View analytics data for your games.')];
    $options['bits:read'] = [$this->t('Bits: view'), $this->t('View Bits information for your channel.')];
    $options['channel:edit:commercial'] = [$this->t('Channel: run commercials'), $this->t('Run commercials on a channel.')];
    $options['channel:manage:broadcast'] = [$this->t('Channel: manage broadcast'), $this->t('Manage your channel’s broadcast configuration, including updating channel configuration and managing stream markers and stream tags.')];
    $options['channel:manage:extensions'] = [$this->t('Channel: manage extensions'), $this->t('Manage your channel’s extension configuration, including activating extensions.')];
    $options['channel:read:hype_train'] = [$this->t('Channel: hype train'), $this->t('Gets the most recent hype train on a channel.')];
    $options['channel:read:stream_key'] = [$this->t('Channel: stream key'), $this->t('Read an authorized user’s stream key.')];
    $options['channel:read:subscriptions'] = [$this->t('Channel: subscribers'), $this->t('Get a list of all subscribers to your channel and check if a user is subscribed to your channel')];
    $options['clips:edit'] = [$this->t('Clips: manage'), $this->t('Manage a clip object.')];
    $options['user:edit'] = [$this->t('User: edit'), $this->t('Manage a user object.')];
    $options['user:edit:follows'] = [$this->t('User: edit followers'), $this->t('Edit your follows.')];
    $options['user:read:broadcast'] = [$this->t('User: read broadcast information'), $this->t('View your broadcasting configuration, including extension configurations.')];
    $options['user:read:email'] = [$this->t('User: read email address'), $this->t('Read an authorized user’s email address.')];
    $options['user_read'] = [$this->t('User: read'), $this->t('Read user information (required for resource owner testing)')];

    $form['scopes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Scopes'),
      '#description' => $this->t('Request scopes.'),
      '#options' => array_combine(array_keys($options), array_column($options, 0)),
      '#default_value' => $this->getConfiguration()['scopes'],
    ];
    foreach ($options as $key => [1 => $description]) {
      $form['scopes'][$key]['#description'] = $description;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['scopes'] = array_keys(array_filter($form_state->getValue('scopes')));
  }

  /**
   * {@inheritdoc}
   */
  public function renderResourceOwner(ResourceOwnerInterface $resourceOwner): array {
    assert($resourceOwner instanceof TwitchUser);
    \Drupal::messenger()->addMessage(\t('Success! This token is owned by @id', ['@id' => $resourceOwner->getId()]));
    return [];
  }

}
