<?php

namespace Drupal\multistep_register\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class RegisterCommand.
 */
class RegisterCommand implements CommandInterface {

  /**
   * Define registerFields.
   *
   * @var array
   */
  protected $registerFields;

  /**
   * Define construct.
   */
  public function __construct($registerFields) {
    $this->registerFields = $registerFields;
  }

  /**
   * Render custom ajax command.
   *
   * @return ajax
   *   Command function.
   */
  public function render() {
    return [
      'command' => 'registerComplete',
      'register_fields' => $this->registerFields,
    ];
  }

}
