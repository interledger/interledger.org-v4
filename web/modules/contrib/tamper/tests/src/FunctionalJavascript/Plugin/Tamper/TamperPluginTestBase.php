<?php

namespace Drupal\Tests\tamper\FunctionalJavascript\Plugin\Tamper;

use Drupal\Core\Entity\EntityInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\entity_test\Entity\EntityTestBundle;

/**
 * Tests configuring Tamper plugins in the UI.
 */
abstract class TamperPluginTestBase extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['entity_test', 'tamper', 'tamper_test'];

  /**
   * The ID of the plugin to test.
   *
   * @var string
   */
  protected static $pluginId;

  /**
   * The config entity to add third party settings to.
   *
   * @var \Drupal\entity_test\Entity\EntityTestWithBundle
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->entity = EntityTestBundle::create([
      'id' => 'test',
      'label' => 'Test label',
      'description' => 'My test description',
    ]);
    $this->entity->save();
  }

  /**
   * Reloads an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to reload.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The reloaded entity.
   */
  protected function reloadEntity(EntityInterface $entity) {
    /** @var \Drupal\Core\Entity\ $storageEntityStorageInterface */
    $storage = $this->container->get('entity_type.manager')->getStorage($entity->getEntityTypeId());
    $storage->resetCache([$entity->id()]);
    return $storage->load($entity->id());
  }

  /**
   * Asserts that the tamper plugin settings are saved on the entity.
   *
   * @param array $expected
   *   The expected values on the entity for the current plugin.
   */
  protected function assertTamperValues(array $expected) {
    $this->entity = $this->reloadEntity($this->entity);
    $tampers = $this->entity->getThirdPartySetting('tamper_test', 'tampers');
    $this->assertSame($expected, $tampers[static::$pluginId]);
  }

}
