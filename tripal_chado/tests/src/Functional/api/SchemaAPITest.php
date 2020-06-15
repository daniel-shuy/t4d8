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
class SchemaAPITest extends BrowserTestBase {

  protected $defaultTheme = 'stable';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['tripal', 'tripal_chado'];

  /**
   * Tests chado_table_exists() and chado_column_exists().
   *
   * @group tripal-chado
   * @group chado-schema
   */
  public function testChadoTableColumnExists() {
    $connection = \Drupal\Core\Database\Database::getConnection();

    // First create our table in the chado schema (if it exists).
    $check_schema = "SELECT true FROM pg_namespace WHERE nspname = 'chado'";
    $exists = $connection->query($check_schema)->fetchField();
    if (!$exists) {
      $this->markTestSkipped('Cannot check chado schema api without chado.');
    }

    // Define a table name which cannot exist.
    $table_name = 'testChadoTableExists_' . uniqid();

    // Check that the table does not exist.
    $result = chado_table_exists($table_name);
    $this->assertFalse($result,
      "The table should NOT exists because we haven't created it yet.");

    // Now create the table.
    $sql = "CREATE TABLE chado." . $table_name . " (
        cte_id     SERIAL PRIMARY KEY,
        cte_name    varchar(40)
    )";
    $connection->query($sql);

    // And check that the table is there.
    $result = chado_table_exists($table_name);
    $this->assertTrue($result,
      "The table, $table_name, should exists because we just created it.");

    // -- COLUMNS.
    // Now check that a column NOT in the table is properly detected.
    $column = 'columndoesnotexist';
    $result = chado_column_exists($table_name, $column);
    $this->assertFalse($result,
      "The column, $table_name.$column, should not exist in the table.");

    // Now check that a column in the table is properly detected.
    $column = 'cte_name';
    $result = chado_column_exists($table_name, $column);
    $this->assertTRUE($result,
      "The column, $table_name.$column, does exist in the table but we were not able to detect it.");

    // -- SEQUENCE.
    // Now check for the sequence which allows the primary key to autoincrement.
    $sequence_name = strtolower($table_name . '_cte_id_seq');
    $result = chado_sequence_exists($sequence_name);
    $this->assertTRUE($result,
      "The sequence, $sequence_name, should exist for the primary key.");

    // There is no sequence on the name so lets confirm that.
    $sequence_name = strtolower($table_name . '_cte_name_seq');
    $result = chado_sequence_exists($sequence_name);
    $this->assertFALSE($result,
      "The sequence, $sequence_name, should NOT exist for the name.");

    // -- INDEX.
    // Now check for the index on the primary key.
    $result = chado_index_exists($table_name, 'pkey', TRUE);
    $this->assertTRUE($result,
      "The index should exist for the primary key.");

    // There is no index on the name so lets confirm that.
    $index = strtolower($table_name . '_cte_name_idx');
    $result = chado_index_exists($table_name, 'cte_name', $index);
    $this->assertFALSE($result,
      "The index should NOT exist for the name.");

    // -- ADD INDEX.
    // We've already proven there is no index on the name.
    // Now we are going to add one!
    $success = chado_add_index($table_name, '_someindexname', ['cte_name']);
    $result = chado_index_exists($table_name, '_someindexname');
    $this->assertTrue($result,
      "The index we just created should be available.");

    // Clean up after ourselves by dropping the table.
    \Drupal::database()->query("DROP TABLE chado." . $table_name);
  }

  /**
   * Tests chado_get_schema_name().
   *
   * @group tripal-chado
   * @group chado-schema
   */
  public function testChadoSchemaMetdata() {

    // First check the default schema.
    $schema_name = chado_get_schema_name('fred');
    $this->assertEquals('public', $schema_name,
      "The default schema is not what we expected. We expected the 'public' schema.");

    // Next check if chado is local.
    $is_local = chado_is_local();
    $this->assertIsBool($is_local, "Unable to check that chado is local.");
    $is_local_2X = chado_is_local();
    $this->assertIsBool($is_local_2X, "Unable to check that chado is local 2X.");
    $this->assertEquals($is_local, $is_local_2X,
      "When checking if chado is local we didn't get the same answer twice.");

    // Check if chado is installed.
    $installed = chado_is_installed();
    $this->assertTrue($installed, "Chado is not installed?");

    // Check the chado version.
    $version = chado_get_version();
    $this->assertGreaterThanOrEqual(1.3, $version,
      "We were unable to detect the version assuming it's 1.3");
  }

  /**
   * Tests that the class can be initiated with or without a record specified
   *
   * @group api
   * @group chado
   * @group chado-schema
   */
  public function testInitClass() {

    // Test with no parameters.
    $chado_schema = new \Drupal\tripal_chado\api\ChadoSchema();
    $this->assertNotNull($chado_schema);

    // Test with version.
    $chado_schema = new \Drupal\tripal_chado\api\ChadoSchema('1.3');
    $this->assertNotNull($chado_schema);
  }

  /**
   * Tests the ChadoSchema->getVersion() method.
   *
   * @group api
   * @group chado
   * @group chado-schema
   */
  public function testGetVersion() {

    // Generate a fake version.
    $version = rand(100,199) / 100;

    // Check version can be retrieved when we set it.
    $chado_schema = new \Drupal\tripal_chado\api\ChadoSchema($version);
    $retrieved_version = $chado_schema->getVersion();
    $this->assertEquals(
      $version,
      $retrieved_version,
      t('The version retrieved via ChadoSchema->getVersion, ":ret", should equal that set, ":set"',
        [':ret' => $retrieved_version, ':set' => $version])
    );

    // @todo Check version can be retrieved when it's looked up?
  }

  /**
   * Tests the ChadoSchema->getSchemaName() method.
   *
   * @group api
   * @group chado
   * @group chado-schema
   */
  public function testGetSchemaName() {

    // Generate a fake version.
    $version = rand(100,199) / 100;
    $schema_name = uniqid();

    // Check the schema name can be retrieved when we set it.
    $chado_schema = new \Drupal\tripal_chado\api\ChadoSchema($version, $schema_name);
    $retrieved_schema = $chado_schema->getSchemaName();
    $this->assertEquals(
      $schema_name,
      $retrieved_schema,
      t('The schema name retrieved via ChadoSchema->getSchemaName, ":ret", should equal that set, ":set"',
        [':ret' => $retrieved_schema, ':set' => $schema_name])
    );

    // @todo Check schema name can be retrieved when it's looked up?
  }

  /**
   * Tests the ChadoSchema->getSchemaDetails() method.
   *
   * @group api
   * @group chado
   * @group chado-schema
   */
  public function testGetSchemaDetails() {

    $chado_schema = new \Drupal\tripal_chado\api\ChadoSchema(1.3, 'chado');
    $schema_details = $chado_schema->getSchemaDetails();
    $this->assertIsArray($schema_details,
      "We were unable to pull out the schema details from the YAML file.");
    $this->assertArrayHasKey('cvterm', $schema_details,
      "The schema details array does not contain details about the cvterm table.");
    $this->assertArrayHasKey('organism', $schema_details,
      "The schema details array does not contain details about the organism table.");
    $this->assertArrayHasKey('feature', $schema_details,
      "The schema details array does not contain details about the feature table.");
    $this->assertArrayHasKey('stock', $schema_details,
      "The schema details array does not contain details about the stock table.");

    foreach ($schema_details as $table => $table_details) {
      $this->assertArrayHasKey('description', $table_details,
        "The $table does not have a description in the YAML.");
      $this->assertArrayHasKey('fields', $table_details,
        "The $table does not have a fields array in the YAML.");
      $this->assertArrayHasKey('primary key', $table_details,
        "The $table does not have a primary key in the YAML.");
    }
  }

  /**
   * Tests ChadoSchema->getTableNames() method.
   *
   * @dataProvider knownTableProvider
   *
   * @group api
   * @group chado
   * @group chado-schema
   */
  public function testGetTableNames($version, $known_tables) {

    // Check: Known tables for a given version are returned.
    $chado_schema = new \Drupal\tripal_chado\api\ChadoSchema($version);
    $returned_tables = $chado_schema->getTableNames();
    //print_r($returned_tables);

    foreach ($known_tables as $table_name) {
      $this->assertContains(
        $table_name,
        $returned_tables,
        t('The table, ":known", should exist in the returned tables list for version :version.',
          [':known' => $table_name, ':version' => $version])
      );
    }
  }

  /**
   * Tests ChadoSchema->getTableSchema() method.
   *
   * @group api
   * @group chado
   * @group chado-schema
   */
  public function testGetTableSchema() {

    // Check all Chado 1.3 tables.
    $version = 1.3;
    $dataset = ['acquisition', 'acquisition_relationship', 'acquisitionprop',
    'analysis', 'analysis_cvterm', 'analysis_dbxref', 'analysis_pub',
    'analysis_relationship', 'analysisfeature', 'analysisfeatureprop',
    'analysisprop', 'arraydesign', 'arraydesignprop', 'assay',
    'assay_biomaterial', 'assay_project', 'assayprop', 'biomaterial',
    'biomaterial_dbxref', 'biomaterial_relationship', 'biomaterial_treatment',
    'biomaterialprop', 'cell_line', 'cell_line_cvterm', 'cell_line_cvtermprop',
    'cell_line_dbxref', 'cell_line_feature', 'cell_line_library', 'cell_line_pub',
    'cell_line_relationship', 'cell_line_synonym', 'cell_lineprop',
    'cell_lineprop_pub', 'chadoprop', 'channel', 'contact',
    'contact_relationship', 'contactprop', 'control', 'cv', 'cvprop', 'cvterm',
    'cvterm_dbxref', 'cvterm_relationship', 'cvtermpath', 'cvtermprop',
    'cvtermsynonym', 'db', 'dbprop', 'dbxref', 'dbxrefprop', 'eimage',
    'element', 'element_relationship', 'elementresult',
    'elementresult_relationship', 'environment', 'environment_cvterm',
    'expression', 'expression_cvterm', 'expression_cvtermprop',
    'expression_image', 'expression_pub', 'expressionprop', 'feature',
    'feature_contact', 'feature_cvterm', 'feature_cvterm_dbxref',
    'feature_cvterm_pub', 'feature_cvtermprop', 'feature_dbxref',
    'feature_expression', 'feature_expressionprop', 'feature_genotype',
    'feature_phenotype', 'feature_pub', 'feature_pubprop', 'feature_relationship',
    'feature_relationship_pub', 'feature_relationshipprop',
    'feature_relationshipprop_pub', 'feature_synonym', 'featureloc',
    'featureloc_pub', 'featuremap', 'featuremap_contact', 'featuremap_dbxref',
    'featuremap_organism', 'featuremap_pub', 'featuremapprop', 'featurepos',
    'featureposprop', 'featureprop', 'featureprop_pub', 'featurerange', 'genotype',
    'genotypeprop', 'library', 'library_contact', 'library_cvterm', 'library_dbxref',
    'library_expression', 'library_expressionprop', 'library_feature',
    'library_featureprop', 'library_pub', 'library_relationship',
    'library_relationship_pub', 'library_synonym', 'libraryprop', 'libraryprop_pub',
    'magedocumentation', 'mageml', 'nd_experiment',
    'nd_experiment_analysis', 'nd_experiment_contact', 'nd_experiment_dbxref',
    'nd_experiment_genotype', 'nd_experiment_phenotype', 'nd_experiment_project',
    'nd_experiment_protocol', 'nd_experiment_pub', 'nd_experiment_stock',
    'nd_experiment_stock_dbxref', 'nd_experiment_stockprop', 'nd_experimentprop',
    'nd_geolocation', 'nd_geolocationprop', 'nd_protocol', 'nd_protocol_reagent',
    'nd_protocolprop', 'nd_reagent', 'nd_reagent_relationship', 'nd_reagentprop',
    'organism', 'organism_cvterm', 'organism_cvtermprop', 'organism_dbxref',
    'organism_pub', 'organism_relationship', 'organismprop', 'organismprop_pub',
    'phendesc', 'phenotype', 'phenotype_comparison', 'phenotype_comparison_cvterm',
    'phenotype_cvterm', 'phenotypeprop', 'phenstatement', 'phylonode',
    'phylonode_dbxref', 'phylonode_organism', 'phylonode_pub', 'phylonode_relationship',
    'phylonodeprop', 'phylotree', 'phylotree_pub', 'phylotreeprop', 'project',
    'project_analysis', 'project_contact', 'project_dbxref', 'project_feature',
    'project_pub', 'project_relationship', 'project_stock', 'projectprop',
    'protocol', 'protocolparam', 'pub', 'pub_dbxref', 'pub_relationship',
    'pubauthor', 'pubauthor_contact', 'pubprop', 'quantification',
    'quantification_relationship', 'quantificationprop', 'stock', 'stock_cvterm',
    'stock_cvtermprop', 'stock_dbxref', 'stock_dbxrefprop', 'stock_feature',
    'stock_featuremap', 'stock_genotype', 'stock_library', 'stock_pub',
    'stock_relationship', 'stock_relationship_cvterm', 'stock_relationship_pub',
    'stockcollection', 'stockcollection_db', 'stockcollection_stock',
    'stockcollectionprop', 'stockprop', 'stockprop_pub', 'study', 'study_assay',
    'studydesign', 'studydesignprop', 'studyfactor', 'studyfactorvalue',
    'studyprop', 'studyprop_feature', 'synonym', 'tableinfo', 'treatment'];

    // Check: a schema is returned that matches what we expect.
    $chado_schema = new \Drupal\tripal_chado\api\ChadoSchema($version);
    foreach ($dataset as $table_name) {
      $table_schema = $chado_schema->getTableSchema($table_name);

      $this->assertNotEmpty(
        $table_schema,
        t('Returned schema for ":table" in chado v:version should not be empty.',
          [':table' => $table_name, ':version' => $version])
      );

      $this->assertArrayHasKey(
        'fields',
        $table_schema,
        t('The schema array for ":table" should have columns listed in an "fields" array',
          [':table' => $table_name])
      );

      // Instead of asserting these keys exist. Lets assert that if they do exist,
      // they match the expected format.

      if (isset($table_schema['primary key'])) {
        $this->assertTrue(is_array($table_schema['primary key']),
          t('The primary key of the Tripal Schema definition for ":table" must be an array.',
            [':table' => $table_name]));

      }

      $this->assertArrayHasKey(
        'foreign keys',
        $table_schema,
        t('The schema array for ":table" should have foreign keys listed in an "foreign keys" array',
          [':table' => $table_name])
      );
    }
  }

  /**
   * Tests ChadoSchema->getCustomTableSchema() method.
   *
   * @dataProvider knownCustomTableProvider
   *
   * @group api
   * @group chado
   * @group chado-schema
   *
   * NOTE: Currently being skipped because the custom tables functionality is
   *  not available yet.
   */
  public function testGetCustomTableSchema($table_name) {

    $this->markTestSkipped('Custom Table functionality has not been upgraded yet.');

    // Check: a schema is returned that matches what we expect.
    $chado_schema = new \Drupal\tripal_chado\api\ChadoSchema();
    $table_schema = $chado_schema->getCustomTableSchema($table_name);

    $this->assertNotEmpty(
      $table_schema,
      t('Returned schema for ":table" in chado v:version should not be empty.',
        [':table' => $table_name, '!version' => $version])
    );

    $this->assertArrayHasKey(
      'fields',
      $table_schema,
      t('The schema array for ":table" should have columns listed in an "fields" array',
        ['!table' => $table_name])
    );

    // NOTE: Other then ensuring fields are set, we can't test further since all other
    // keys are technically optional and these arrays are set by admins.

  }

  /**
   * Tests ChadoSchema->getBaseTables() method.
   *
   * @dataProvider knownBaseTableProvider
   *
   * @group api
   * @group chado
   * @group chado-schema
   */
  public function testGetBaseTables($version, $known_tables) {

    //$this->markTestSkipped('Base table functionality not complete. Requires referring tables key to YAML.');

    // Check: Known base tables for a given version are returned.
    $chado_schema = new \Drupal\tripal_chado\api\ChadoSchema($version);
    $returned_tables = $chado_schema->getBaseTables();

    foreach ($known_tables as $table_name) {

      $found = FALSE;

      foreach ($returned_tables as $check_table) {

        if ($check_table == $table_name) {
          $found = TRUE;
        }
      }
      $this->assertTrue($found, "{$table_name} was not returned by getBaseTables for Chado v {$version}");
    }

  }

  /**********************************************
   * Data Providers:
   */

  /**
   * Data Provider: returns known tables specific to a given chado version.
   *
   * @return array
   */
  public function knownTableProvider() {
    // chado version, array of 3 tables specific to version.

    return [
      ['1.3', ['analysis_cvterm', 'dbprop', 'organism_pub']],
    ];
  }

  /**
   * Data Provider: returns known tables specific to a given chado version.
   *
   * @return array
   */
  public function knownBaseTableProvider() {
    // chado version, array of 3 tables specific to version.

    return [
      [
        '1.3',
        ['organism', 'feature', 'stock', 'project', 'analysis', 'phylotree'],
      ],
    ];
  }

  /**
   * Data Provider: returns known custom tables specific to a given chado
   * version.
   *
   * NOTE: These tables are provided by core Tripal so we should be able to
   *  depend on them. Also, for the same reason, chado version doesn't matter.
   *
   * @return array
   */
  public function knownCustomTableProvider() {

    return [
      ['library_feature_count'],
      ['organism_feature_count'],
      ['tripal_gff_temp'],
    ];
  }
}