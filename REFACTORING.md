# Code Refactoring - Architecture Improvements

## Overview
The codebase has been refactored to follow CakePHP conventions and establish proper separation of concerns.

## What Changed

### 1. Model Layer (NEW)
**Location**: `src/Model/`

- **Entity**: `src/Model/Entity/Test.php` - represents a single Test record
- **Table**: `src/Model/Table/TestsTable.php` - represents the tests collection/table

**Usage in Controller**:
```php
// Before - implicit model
$this->Test->save($entity);

// After - same syntax, but now backed by proper ORM structure
$this->Test->save($entity);  // Works the same way!
```

### 2. Audit Service (NEW)
**Location**: `src/Service/AuditService.php`

Centralizes all audit logging (MongoDB + Elasticsearch).

**Usage in Controller**:
```php
// Before
$mongo = new MongoService();
$payload = ['action' => 'view_test', 'test_id' => $id];
$insertResult = $mongo->collection()->insertOne($payload);
$this->indexMongoDocumentToElasticsearch($payload, (string)$insertResult->getInsertedId());

// After (clean!)
$this->Audit->logView($id);
```

### 3. Audit Component (NEW)
**Location**: `src/Controller/Component/AuditComponent.php`

Provides convenient audit methods in any controller.

**Setup in Controller**:
```php
public function initialize(): void
{
    parent::initialize();
    $this->loadComponent('Audit');  // One line!
}
```

**Available Methods**:
- `$this->Audit->logView($entityId)` - Log a view action
- `$this->Audit->logCreate($entityId, $data)` - Log a create action
- `$this->Audit->logUpdate($entityId, $data)` - Log an update action
- `$this->Audit->logDelete($entityId)` - Log a delete action

## TestController Refactoring

**Before**: 200+ lines with mixed concerns
**After**: 140 lines, clean separation

Key Changes:
```php
// ✅ NOW: Clean initialize
public function initialize(): void
{
    parent::initialize();
    $this->loadComponent('Audit');  // Enables audit logging
}

// ✅ NOW: Simple view action
public function view($id = null)
{
    $testEntity = $this->Test->get($id, contain: []);
    $this->Audit->logView($id);  // One line, clear intent!
    $this->set(compact('testEntity'));
}

// ✅ NOW: Audit on save
if ($this->Test->save($testEntity)) {
    $this->Audit->logCreate($testEntity->id, $this->request->getData());
    $this->Flash->success(__('The test has been saved.'));
    return $this->redirect(['action' => 'index']);
}
```

## MongoDB Structure

### Collection: `tests` (ORM-managed)
Used by CakePHP ORM for actual data.

### Collection: `activity_logs` (Audit)
Used by AuditService for audit trails.

**Document structure**:
```javascript
{
  _id: ObjectId,
  action: "view_test|create_test|update_test|delete_test",
  entity_id: "...",
  timestamp: ISODate,
  data: { /* optional */ }
}
```

## Migration Path

### For New Audit Logging
Just load the component and call the audit methods:

```php
class YourController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Audit');  // Enable auditing
    }

    public function myAction() {
        // ... your logic ...
        $this->Audit->logCreate($entityId, $data);  // Simple!
    }
}
```

### For Advanced Use
Use AuditService directly in services or business logic:

```php
use App\Service\AuditService;

// Inject or instantiate
$audit = new AuditService();
$mongoId = $audit->logCustomAction('custom_action', $entityId, ['field' => 'value']);
```

## Next Steps (Optional Enhancements)

1. **Table Behaviors** - Move audit logic to a behavior for automatic logging
   ```php
   // In TestsTable::initialize()
   $this->addBehavior('Timestamp');  // Auto timestamp
   $this->addBehavior('Audit');      // Auto audit logging
   ```

2. **Dependency Injection** - Use DI instead of service instantiation
   ```php
   public function __construct(AuditService $audit) {
       $this->audit = $audit;
   }
   ```

3. **Indexing** - Add MongoDB indexes for better query performance
   ```php
   // In AuditService or migration
   $auditCollection->createIndex(['timestamp' => -1]);
   $auditCollection->createIndex(['action' => 1, 'entity_id' => 1]);
   ```

## Validation
All files have been PHP linted and pass syntax validation:
- ✅ `src/Model/Entity/Test.php`
- ✅ `src/Model/Table/TestsTable.php`
- ✅ `src/Service/AuditService.php`
- ✅ `src/Controller/Component/AuditComponent.php`
- ✅ `src/Controller/TestController.php`
- ✅ `src/Service/MongoService.php`

## Questions?
Refer to CakePHP documentation:
- [Table Classes](https://book.cakephp.org/5/en/orm/table-objects.html)
- [Entity Classes](https://book.cakephp.org/5/en/orm/entities.html)
- [Components](https://book.cakephp.org/5/en/controllers/components.html)
- [Services](https://book.cakephp.org/5/en/development/services.html)
