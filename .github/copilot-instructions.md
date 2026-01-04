# Copilot Instructions for Simplified Transfer System

## Project Overview
**Simplified Transfer System** is a production-ready PHP REST API for money transfers between common users and shopkeepers, with atomic database transactions, external authorization, and async notifications. Follows Clean Architecture and SOLID principles with 84 automated tests and zero PHPStan errors.

---

## Architecture Essentials

### 4-Layer Clean Architecture Pattern
Every feature flows through these layers **in order**:

1. **Controller** (`src/Controllers/*.php`): Validates HTTP payload, catches domain exceptions, returns JSON
2. **Service** (`src/Services/*.php`): Business logic, orchestration, transaction management
3. **Repository** (`src/Repositories/*.php`): Data access, PDO transactions, balance updates
4. **Model** (`src/Models/User.php`): Domain logic (e.g., `isShopkeeper()`, `hasSufficientBalance()`)

**Key principle**: Don't skip layers. Controllers must NOT query database directly.

### Dependency Injection Container
- **DI Container**: PHP-DI (`config/dependencies.php`)
- **DatabaseManager**: Cycle ORM for schema/queries, but `UserRepository` uses raw PDO for transaction control
- **Service registration**: Use `DI\autowire()` for auto-wiring or `DI\create()` for simple instantiation

### External APIs
- **AuthorizeService**: Synchronous GET to `https://util.devi.tools/api/v2/authorize` (blocks transfer flow)
- **NotifyService**: Asynchronous POST to `https://util.devi.tools/api/v1/notify` using Guzzle's `postAsync()->wait(false)` (fire-and-forget)

---

## Critical Patterns & Conventions

### Exception Handling
- **Always throw `AppException` subclasses** from Services (see `src/Core/Exceptions.php`)
- Controllers catch `AppException` → extract message + statusCode → return JSON
- Example: `throw new BusinessRuleException('Shopkeepers cannot perform transfers', 422)`
- **Never** throw generic `Exception` from services; use typed exceptions

### Database Transactions
- **Transactions are in Repository**, not Service
- `UserRepository::executeTransfer()` wraps balance updates in `$pdo->beginTransaction()`
- On error: auto-rollback + restore in-memory balances
- **Pattern**: Preserve original values before DB updates, restore on `Throwable` (not just `Exception`)

### Async Notifications
- Use `NotifyService::notify($userId)` (async, never block)
- `notifySync()` is **test-only** — marked with deprecation warning in docblock
- Notification failures do NOT fail the transfer (fire-and-forget)

### Type Declarations
- **Strict types everywhere**: `declare(strict_types=1)` in every PHP file
- Use **type hints** on all parameters and return types
- Array types: Use `@return array<string,mixed>` for structured returns, or typed array syntax if PHP 8.0+
- Use `?Type` for nullable, never bare `null`

### Testing Conventions
- **Unit tests**: Mock dependencies (Repository, AuthorizeService, NotifyService)
- **Integration tests**: Use real database with `test` environment, mock external APIs
- Test structure: `setUp()` → mock factories → arrange → act → assert
- Mock return maps for multiple user lookups: `$repo->method('find')->willReturnMap([...])`

---

## Common Development Workflows

### Running Tests
```bash
./run test                    # All 84 tests
./run test tests/Unit/        # Unit tests only
./run test tests/Integration/ # Integration tests
```

### Code Quality Checks
```bash
./run phpfullcheck  # Does everything:
  # 1. phpcbf (PHPCS auto-fix)
  # 2. phpfmt (PHP-CS-Fixer)
  # 3. phpcs (PSR-12 check)
  # 4. phpstan (static analysis, level 8)
  # 5. phpmd (code smells)
  # 6. test (PHPUnit)
```

### Adding a New Endpoint
1. Create controller in `src/Controllers/` with proper type hints
2. Create/extend service in `src/Services/` with business logic
3. Register in `config/dependencies.php` (if new service)
4. Add route in `routes/api.php`
5. Write unit tests for service + controller
6. Run `./run phpfullcheck` before commit

### Debugging Database Issues
```bash
./run db:reset            # Reset + seed test data
docker exec -it transfer-app php bin/db-integration.php  # Test DB connection
docker exec -it transfer-mysql mysql -u transfer_user -ptransfer_pass simplified_transfer
```

---

## File Structure Reference

| Path | Purpose |
|------|---------|
| `src/Controllers/` | HTTP request handlers, payload validation, exception catching |
| `src/Services/` | Business logic, external APIs, transaction orchestration |
| `src/Repositories/` | Database access, PDO transactions, raw queries |
| `src/Models/` | Domain logic methods (e.g., balance checks, type checks) |
| `src/Entity/` | Cycle ORM entities (`Transfer`, `User` for schema) |
| `src/Core/Exceptions.php` | Custom exception hierarchy extending `AppException` |
| `config/dependencies.php` | DI Container service registration |
| `config/database.php` | Cycle ORM DatabaseManager setup |
| `routes/api.php` | Slim route definitions |
| `tests/Unit/` | Unit tests with mocked dependencies |
| `tests/Integration/` | Integration tests with real DB |
| `migrations/` | Database schema (SQL) |
| `.run` | Helper CLI script (use instead of raw docker-compose/composer) |

---

## Validation Strategy
- **Layer 1 (Controller)**: Payload format (required fields, types)
- **Layer 2 (Service)**: Business rules (saldo, shopkeeper restriction, payer ≠ payee)
- **Layer 3 (External API)**: Authorization service
- **Layer 4 (Database)**: Atomicity, foreign keys, unique constraints

---

## Environment & Dependencies
- **PHP**: 8.2/8.3 with strict_types enabled
- **Framework**: Slim Framework 4.12
- **ORM**: Cycle ORM (EntityManager in services) + raw PDO (Repository for transactions)
- **HTTP Client**: Guzzle (async notifications with postAsync)
- **Database**: MySQL 8.0
- **DI**: PHP-DI with autowiring
- **Tests**: PHPUnit 9.x with mocking

Always check `composer.json` for dependency versions before referencing APIs.

---

## Quick Decision Tree
**"Where should I add this code?"**
- Validate HTTP input? → **Controller** (`processPayload()`, `extractRequestData()`)
- Check business rules? → **Service** (`validateBusinessRules()`)
- Run SQL query? → **Repository** (`find()`, `updateBalance()`)
- Calculate logic? → **Model** (`hasSufficientBalance()`)
- Handle external API? → **Service** (AuthorizeService, NotifyService)
- Need atomicity? → **Repository** (wrap in transaction)

---

## Notes for AI Agents
1. **Always read exceptions file first** to understand domain error handling
2. **Check test files** before modifying business logic — tests are the spec
3. **Run `phpfullcheck` after changes** — it catches 80% of issues automatically
4. **Fire-and-forget is intentional** — notification failures must not fail transfers
5. **Database transactions are critical** — never move them to Service layer
6. **Mock external APIs in tests** — never call real endpoints in test suite

## Commenting Guidelines
- Write clear, concise comments explaining the **why** behind complex logic (e.g., why fire-and-forget is used)
- Avoid redundant comments that just repeat code (e.g., don't comment `$x = 5; // set x to 5`)
- Use English only, in places that genuinely aid understanding
- Example: In `NotifyService::notify()`, document **why** `postAsync()->wait(false)` is used (prevents blocking transfers)