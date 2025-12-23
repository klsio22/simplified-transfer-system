<?php

declare(strict_types=1);

use Cycle\ORM;
use Cycle\Schema;
use Cycle\Annotated;
use Cycle\Annotated\Locator\TokenizerEmbeddingLocator;
use Cycle\Annotated\Locator\TokenizerEntityLocator;
use Spiral\Tokenizer\ClassLocator;
use Symfony\Component\Finder\Finder;

// Get DatabaseManager
$dbal = require __DIR__ . '/database.php';

// Initialize Class Locator using Symfony Finder (as per official docs)
$finder = (new Finder())->files()->in([__DIR__ . '/../src']);
$classLocator = new ClassLocator($finder);

// Create embedding and entity locators
$embeddingLocator = new TokenizerEmbeddingLocator($classLocator);
$entityLocator = new TokenizerEntityLocator($classLocator);

// Compile schema (following official docs v3.x)
$schema = (new Schema\Compiler())->compile(
    new Schema\Registry($dbal),
    [
        new Schema\Generator\ResetTables(),             // Reconfigure table schemas (deletes columns if necessary)
        new Annotated\Embeddings($embeddingLocator),    // Recognize embeddable entities
        new Annotated\Entities($entityLocator),         // Identify attributed entities
        new Annotated\TableInheritance(),               // Setup Single Table or Joined Table Inheritance
        new Annotated\MergeColumns(),                   // Integrate table #[Column] attributes
        new Schema\Generator\GenerateRelations(),       // Define entity relationships
        new Schema\Generator\GenerateModifiers(),       // Apply schema modifications
        new Schema\Generator\ValidateEntities(),        // Ensure entity schemas adhere to conventions
        new Schema\Generator\RenderTables(),            // Create table schemas
        new Schema\Generator\RenderRelations(),         // Establish keys and indexes for relationships
        new Schema\Generator\RenderModifiers(),         // Implement schema modifications
        new Schema\Generator\ForeignKeys(),             // Define foreign key constraints
        new Annotated\MergeIndexes(),                   // Merge table index attributes
        new Schema\Generator\SyncTables(),              // Align table changes with the database
        new Schema\Generator\GenerateTypecast(),        // Typecast non-string columns
    ]
);

// Create and return ORM instance
return new ORM\ORM(new ORM\Factory($dbal), new ORM\Schema($schema));
