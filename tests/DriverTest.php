<?php

use \DelightfulDB\Driver as DelightfulDB;
use \PHPUnit\Framework\TestCase as TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class DriverTest extends TestCase {
  /** Root Storage Directory **/
  public $root = '';

  /**
   * Setup For Each Process, Includes Library and Cleans Instance
   * @return Void
   */
  public function setUp(): Void
  {
    require_once(dirname(__FILE__).'/includes/setup.php');
    $this->root = testSetup();
  }

  /**
   * Validate Successfully Creating a Document
   * @return Void
   */
  public function testCreateDocument() : Void
  {
    $db = new DelightfulDB($this->root, ['docs'=>[]]);
    $id = $db->createDocument('docs', DDB_MOCK_SINGLE);

    $this->assertEquals(
      true,
      file_exists("{$this->root}/docs/$id.ddb"),
      "[$id] Document was not created.",
    );
  }

  /**
   * Validate Successfully Creating a Document with Fulltext Indexes
   * @return Void
   */
  public function testCreateDocumentFullText() : Void
  {
    $db = new DelightfulDB(
      $this->root,
      [
        'docs'=>[
          '_fulltext' => [
            'fields' => ['title', 'description'],
          ]
        ]
      ]
    );
    $id = $db->createDocument('docs', DDB_MOCK_SINGLE);

    $this->assertEquals(
      true,
      file_exists("{$this->root}/docs/indexes/_fulltext/$id._fulltext.ddi"),
      "[$id] Fulltext index was not created.",
    );
  }

  /**
   * Validate Successfully Creating a Document with Custom Indexes
   * @return Void
   */
  public function testCreateDocumentIndex() : Void
  {
    $db = new DelightfulDB(
      $this->root,
      [
        'docs'=>[
          'slug' => [],
          'tags' => [],
        ]
      ]
    );
    $id = $db->createDocument('docs', DDB_MOCK_SINGLE);

    // Build Hash Values For Slug
    $slug = DDB_MOCK_SINGLE['slug'];
    $slugHash = hash('sha256', json_encode($slug, JSON_INVALID_UTF8_SUBSTITUTE));

    // Build Hash Values For Tags
    $tagsHash = [];
    foreach (DDB_MOCK_SINGLE['tags'] as $tag) {
      $tagsHash[$tag] = hash('sha256', json_encode($tag, JSON_INVALID_UTF8_SUBSTITUTE));
    }

    // Check Slug
    $this->assertEquals(
      true,
      file_exists("{$this->root}/docs/indexes/slug/$id.$slugHash.ddi"),
      "[$slug]::[$slugHash] does not exist for index [slug] in store [docs]",
    );

    // Check Tags
    foreach($tagsHash as $tag => $hash) {
      $this->assertEquals(
        true,
        file_exists("{$this->root}/docs/indexes/tags/$id.$hash.ddi"),
        "[$tag]::[$hash] does not exist for index [tags] in store [docs]",
      );
    }

    // Check Distinct
    $this->assertEquals(
      true,
      file_exists("{$this->root}/docs/indexes/tags/$id.$hash.ddi"),
      "[$tag]::[$hash] does not exist for index [tags] in store [docs]",
    );
  }

  /**
   * Validate Successfully Fetching Document By Id
   * @return Void
   */
  public function testGetDocumentById() : Void
  {
    $db = new DelightfulDB($this->root, ['docs'=>[]]);
    $id = $db->createDocument('docs', DDB_MOCK_SINGLE);
    $doc = $db->getDocumentById('docs', $id);

    $this->assertEquals(
      $id,
      $doc['id'],
      'Invalid Document Id Returned'
    );

    $this->assertEquals(
      json_encode(DDB_MOCK_SINGLE),
      json_encode($doc['document']),
      "[$id] Invalid document returned.",
    );
  }

  /**
   * Validate Successfully Updating Document
   * @return Void
   */
  public function testUpdateDocumentById() : Void
  {
    // Create Instance
    $db = new DelightfulDB($this->root, ['docs'=>[]]);

    // Creaate Document
    $id = $db->createDocument('docs', []);

    // Build Update
    $expected = 'No matter... ...how hard you hold on. It escapes you...';
    $document = DDB_MOCK_SINGLE;
    $document['description'] = $expected;

    // Write Update
    $db->updateDocumentById('docs', $document, $id);

    // Fetch Update
    $docUpdated = $db->getDocumentById('docs', $id);

    $this->assertEquals(
      $expected,
      $docUpdated['document']['description'],
      "[$id] Document was note updated.",
    );
  }

  /**
   * Validate Deleting Document
   * @return Void
   */
  public function testDeleteDocumentById() : Void
  {
    // Create Instance
    $db = new DelightfulDB($this->root, ['docs'=>[]]);

    // Create Document
    $id = $db->createDocument('docs', []);

    // Delete Document
    $db->deleteDocumentById('docs', $id);

    $this->assertEquals(
      false,
      file_exists("{$this->root}/docs/$id.ddb"),
      "[$id] Document was not deleted from filesystem.",
    );
  }

  /**
   * Validate Fetching Documents by Fulltext Search
   * @return Void
   */
  public function testGetDocumentsByFulltextSearch() : Void
  {
    // Create Instance
    $db = new DelightfulDB(
      $this->root,
      [
        'docs'=>[
          '_fulltext' => [
            'fields' => ['title', 'description', 'tags'],
          ]
        ]
      ]
    );

    // Create Documents
    foreach(DDB_MOCKS_MULTIPLE as $doc) {
      $db->createDocument('docs', $doc);
    }

    ////////////////////////////////////////////////////
    // Test AND Queries (Default)
    ////////////////////////////////////////////////////

    $queries = [
      'Ultimecia Kefka'     => 0,
      'Ultimecia Time'      => 1,
      'Life'                => 2,
      'Final Fantasy'       => 3,
      'ファイナルファンタジ'   => 3, // Unicode Test
    ];

    foreach ($queries as $query => $expected) {
      // Test Array Return
      $docs = $db->getDocumentsByFulltextSearch('docs', $query);
      $this->assertEquals(
        $expected,
        count($docs),
        "[$query]::[$expected] Invalid count for fulltext search returned from getDocumentsByFulltextSearch",
      );

      // Test Generator Return
      $docs = [];
      $gen = $db->getDocumentsByFulltextSearchGenerator('docs', $query);
      foreach($gen as $doc) { $docs[] = $doc; }
      $this->assertEquals(
        $expected,
        count($docs),
        "[$query]::[$expected] Invalid count for fulltext search returned from getDocumentsByFulltextSearchGenerator",
      );
    }

    ////////////////////////////////////////////////////
    // Test OR Queries
    ////////////////////////////////////////////////////

    $queries = [
      'Ultimecia Kefka'     => 2,
      'Life'                => 2,
      'Final Fantasy'       => 3,
      'ff8 FF14'            => 2,
      'ff6 zidane'          => 1,
      'ファイナルファンタジ'   => 3, // Unicode Test
    ];

    foreach ($queries as $query => $expected) {
      // Test Array Return
      $docs = $db->getDocumentsByFulltextSearch('docs', $query, false);
      $this->assertEquals(
        $expected,
        count($docs),
        "[$query]::[$expected] Invalid count for fulltext search returned from getDocumentsByFulltextSearch",
      );

      // Test Generator Return
      $docs = [];
      $gen = $db->getDocumentsByFulltextSearchGenerator('docs', $query, false);
      foreach($gen as $doc) { $docs[] = $doc; }
      $this->assertEquals(
        $expected,
        count($docs),
        "[$query]::[$expected] Invalid count for fulltext search returned from getDocumentsByFulltextSearchGenerator",
      );
    }
  }

  /**
   * Validate Fetching Documents by Index Search
   * @return Void
   */
  public function testGetDocumentsByIndex() : Void
  {
    // Create Instance
    $db = new DelightfulDB(
      $this->root,
      [
        'docs'=>[
          'tags' => [],
        ]
      ]
    );

    // Create Documents
    foreach(DDB_MOCKS_MULTIPLE as $doc) {
      $db->createDocument('docs', $doc);
    }

    ////////////////////////////////////////////////////
    // Test AND Queries (Default)
    ////////////////////////////////////////////////////

    $queries = [
      'ff8'                 => 1,
      'ff14'                => 1,
      'ff7'                 => 0,
      'hydaelyn'            => 1,
      'final-fantasy'       => 3,
      'ファイナルファンタジ'   => 3, // Unicode Test
    ];

    foreach ($queries as $query => $expected) {
      // Test Array Return
      $docs = $db->getDocumentsByIndex('docs', 'tags', $query);
      $this->assertEquals(
        $expected,
        count($docs),
        "[$query]::[$expected] Invalid count for fulltext search returned from getDocumentsByIndex",
      );

      // Test Generator Return
      $docs = [];
      $gen = $db->getDocumentsByIndexGenerator('docs', 'tags', $query);
      foreach($gen as $doc) { $docs[] = $doc; }
      $this->assertEquals(
        $expected,
        count($docs),
        "[$query]::[$expected] Invalid count for fulltext search returned from getDocumentsByIndexGenerator",
      );
    }
  }

  /**
   * Validate Fetching Counts of Index
   * @return Void
   */
  public function testCountValueByIndex() : Void
  {
    // Create Instance
    $db = new DelightfulDB(
      $this->root,
      [
        'docs'=>[
          'slug' => [],
          'tags' => [],
        ]
      ]
    );

    // Create Document
    $id = $db->createDocument('docs', DDB_MOCKS_MULTIPLE[0]);

    // Count Index Without ID Exclusion
    $count = $db->countValueByIndex('docs', 'slug', 'ultimecias-final-words');
    $this->assertEquals(
      1,
      $count,
      "[ultimecias-final-words]::[1] Invalid count for fulltext search returned from countValueByIndex",
    );

    // Count Index With ID Exclusion
    $count = $db->countValueByIndex('docs', 'slug', 'ultimecias-final-words', $id);
    $this->assertEquals(
      0,
      $count,
      "[ultimecias-final-words]::[0] Invalid count for fulltext search returned from countValueByIndex",
    );

    // Test New Insert
    $db->createDocument('docs', DDB_MOCKS_MULTIPLE[1]);
    $count = $db->countValueByIndex('docs', 'tags', 'final-fantasy');
    $this->assertEquals(
      2,
      $count,
      "[ultimecias-final-words]::[1] Invalid count for fulltext search returned from countValueByIndex",
    );
  }

  /**
   * Validate Getting Distinct Counts From Index
   * @return Void
   */
  public function testDistinctByIndex() : Void
  {
    // Create Instance
    $db = new DelightfulDB(
      $this->root,
      [
        'docs'=>[
          'tags' => ['distinct' => true],
        ]
      ]
    );

    // Create Documents
    foreach(DDB_MOCKS_MULTIPLE as $doc) {
      $db->createDocument('docs', $doc);
    }

    // Distinct Object
    $distinct = $db->getDistinctByIndex('docs', 'tags');

    // Distinct Counts
    $queries = [
      'ff8'                 => 1,
      'ff14'                => 1,
      'ff7'                 => 0,
      'hydaelyn'            => 1,
      'final-fantasy'       => 3,
      'ファイナルファンタジ'   => 3, // Unicode Test
    ];

    foreach ($queries as $key => $count) {
      $distinctCount = 0;
      if (isset($distinct[$key])) {
        $distinctCount = $distinct[$key];
      }

      $value = isset($distinct[$key]) ? $distinct[$key] : 0;

      $this->assertEquals(
        $count,
        $value,
        "[$key]::[$count] Invalid count for distinct search returned from getDistinctByIndex",
      );
    }
  }

  /**
   * Validate Force Reindex
   * @return Void
   */
  public function forceReindex() : Void
  {
    // Create Instance
    $db = new DelightfulDB(
      $this->root,
      [
        'docs'=>[
          '_fulltext' => ['fields' => ['description']],
          'tags' => [],
        ]
      ]
    );

    // Create Document
    $id = $db->createDocument('docs', DDB_MOCK_SINGLE);

    // Verify Non-Existant Tags
    $docs = $db->getDocumentsByIndex('docs', 'tags', 'alexander');
    $this->assertEquals(
      0,
      count($docs),
      "The keyword 'alexander' should return nothing.",
    );

    // Verify Non-Existant Fulltext Keys
    $docs = $db->getDocumentsByFulltextSearch('docs', 'Seeking the peace of reason');
    $this->assertEquals(
      0,
      count($docs),
      "The fulltext search 'Seeking the peace of reason' should return nothing.",
    );

     // Verify Exsting Tag For Sanity
    $docs = $db->getDocumentsByIndex('docs', 'tags', 'ff8');
    $this->assertEquals(
      1,
      count($docs),
      "The keyword 'ff8' should return 1.",
    );

    // Verify Exsting Description For Sanity
    $docs = $db->getDocumentsByFulltextSearch('docs', 'it will not wait');
    $this->assertEquals(
      1,
      count($docs),
      "The fulltext search 'it will not wait' should return 1.",
    );

    // Manually Edit Document
    $doc = json_decode(file_get_contents("{$this->_root}/docs/$id.ddb"), true);
    $doc['tags'][] = 'alexander';
    $doc['description'][] = 'Seeking the peace of reason';
    file_put_contents("{$this->_root}/docs/$id.ddb", json_encode($doc));

    // Force Reindex
    $db->forceReindex();

    // Verify New Tags
    $docs = $db->getDocumentsByIndex('docs', 'tags', 'alexander');
    $this->assertEquals(
      1,
      count($docs),
      "The keyword 'alexander' should return 1.",
    );

    // Existing Tags Should Still Exist
    $docs = $db->getDocumentsByIndex('docs', 'tags', 'ff8');
    $this->assertEquals(
      1,
      count($docs),
      "The keyword 'ff8' should return 1.",
    );

    // Verify New Fulltext Keys
    $docs = $db->getDocumentsByFulltextSearch('docs', 'Seeking the peace of reason');
    $this->assertEquals(
      1,
      count($docs),
      "The fulltext search 'Seeking the peace of reason' should return 1.",
    );

    // Original Description Should Be Nuked
    $docs = $db->getDocumentsByFulltextSearch('docs', 'it will not wait');
    $this->assertEquals(
      0,
      count($docs),
      "The fulltext search 'it will not wait' should return nothing.",
    );
  }
}
