<?php

declare(strict_types = 1);

namespace Drupal\authman_twitch;

use Depotwarehouse\OAuth2\Client\Twitch\Entity\TwitchUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

/**
 * Wraps TwitchUser so it implements an interface it already supports.
 *
 * Remove after https://github.com/tpavlek/oauth2-twitch/pull/17 is committed.
 */
class AuthmanTwitchUser extends TwitchUser implements ResourceOwnerInterface {

}
