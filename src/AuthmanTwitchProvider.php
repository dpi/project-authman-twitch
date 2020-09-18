<?php

declare(strict_types = 1);

namespace Drupal\authman_twitch;

use Depotwarehouse\OAuth2\Client\Twitch\Provider\Twitch;
use League\OAuth2\Client\Token\AccessToken;


/**
 * Wraps Twitch provider so return values are valid.
 *
 * Remove after https://github.com/tpavlek/oauth2-twitch/pull/17 is committed.
 */
class AuthmanTwitchProvider extends Twitch {

  /**
   * {@inheritdoc}
   */
  protected function createResourceOwner(array $response, AccessToken $token) {
    return new AuthmanTwitchUser((array) $response);
  }

}
