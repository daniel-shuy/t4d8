<?php

namespace Drupal\Tests\tripal_chado;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Database\Database;
use Drupal\tripal_chado\api\ChadoSchema;

/**
 * Testing the tripal_chado/api/tripal_chado.schema.api.inc functions.
 *
 * @group tripal_chado
 */
class ChadoQueryAPITest extends BrowserTestBase {

  protected $defaultTheme = 'stable';

  /**
   * Modules to enable.
   * @var array
   */
  public static $modules = ['tripal', 'tripal_chado'];

  /**
   * Schema to do testing out of.
   * @var string
   */
  public static $schemaName = 'testchado';

  /**
   * Tests chado_query().
   *
   * @group tripal-chado
   * @group chado-query
   */
  public function testChadoQuery() {
		$connection = \Drupal\Core\Database\Database::getConnection();

		// Check that chado exists.
    $check_schema = "SELECT true FROM pg_namespace WHERE nspname = :schema";
    $exists = $connection->query($check_schema, [':schema' => $this::$schemaName])
      ->fetchField();
    $this->assertTrue($exists, 'Cannot check chado schema api without chado.
      Please ensure chado is installed in the schema named "testchado".');

		// Insert some test data.
		$connection->query(
			"INSERT INTO testchado.organism (genus, species, common_name, type_id, infraspecific_name)
			VALUES ('Tripalus', 'ferox','Wild Tripal', 2, 'Quad')")->execute();

		// --------------
		// Check that errors are thrown if the correct parameters are not supplied.
		// -- SQL must be a string.
		$sql = $args =  ['Fred', 'Sarah', 'Jane'];
		$dbq = chado_query($sql, $args);
		$this->assertEquals(FALSE, $dbq);

		// -- Arguements must be an array.
		$sql = $args = 'SELECT * FROM {organism}';
		$dbq = chado_query($sql, $args);
		$this->assertEquals(FALSE, $dbq);

		// -- Arguements should be in the SQL string.
		$sql = 'SELECT * FROM {organism} WHERE genus=:genus';
		$args = [':genus' => 'Tripalus', ':species' => 'ferox'];
		$dbq = chado_query($sql, $args);
		$this->assertEquals(FALSE, $dbq);
		array_shift($args);
		$dbq = chado_query($sql, $args);
		$this->assertEquals(FALSE, $dbq);

		// --------------
		// Now check that a correnctly formatted query actually works.
		$sql = 'SELECT * FROM {organism} WHERE genus=:genus and species=:species';
		$args = [':genus' => 'Tripalus', ':species' => 'ferox'];
		$dbq = chado_query($sql, $args, [], $this::$schemaName);
		$results = [];
		if ($dbq) {
			$results = $dbq->fetchObject();
		}
		$this->assertTrue(is_object($results));
		$this->assertNotEmpty($results);
	}
}
