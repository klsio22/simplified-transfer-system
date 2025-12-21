# 🏛️ Arquitetura do Sistema

## Visão Geral

O **Simplified Transfer System** foi desenvolvido seguindo princípios de **Clean Architecture** e **SOLID**, utilizando o padrão **MVC** com camadas adicionais de **Services** e **Repositories**.

## Diagrama de Arquitetura

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENT (HTTP)                            │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│                     NGINX (Reverse Proxy)                        │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│                    PHP-FPM (Slim Framework)                      │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │  public/index.php (Entrypoint)                              │ │
│ │  ├── Middleware (Body Parsing, Error Handler)               │ │
│ │  └── routes/api.php                                         │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                           │                                      │
│                           ↓                                      │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │  CONTROLLERS LAYER                                          │ │
│ │  └── TransferController                                     │ │
│ │      ├── Valida payload                                     │ │
│ │      ├── Trata exceções                                     │ │
│ │      └── Retorna JSON                                       │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                           │                                      │
│                           ↓                                      │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │  SERVICES LAYER (Business Logic)                            │ │
│ │  ├── TransferService                                        │ │
│ │  │   ├── Valida regras de negócio                          │ │
│ │  │   ├── Orquestra a transferência                         │ │
│ │  │   └── Gerencia transação DB                             │ │
│ │  ├── AuthorizeService                                       │ │
│ │  │   └── Consulta API externa de autorização               │ │
│ │  └── NotifyService                                          │ │
│ │      └── Envia notificação assíncrona                       │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                           │                                      │
│                           ↓                                      │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │  REPOSITORIES LAYER (Data Access)                           │ │
│ │  └── UserRepository                                         │ │
│ │      ├── find(id)                                           │ │
│ │      ├── updateBalance()                                    │ │
│ │      └── getPdo() → transações                              │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                           │                                      │
│                           ↓                                      │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │  MODELS LAYER                                               │ │
│ │  └── User                                                   │ │
│ │      ├── isShopkeeper()                                     │ │
│ │      ├── isCommon()                                         │ │
│ │      └── hasSufficientBalance()                             │ │
│ └─────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                           │
         ┌─────────────────┼─────────────────┐
         ↓                 ↓                  ↓
    ┌─────────┐      ┌──────────┐      ┌─────────┐
    │  MySQL  │      │  Redis   │      │ External│
    │  8.0    │      │  Cache   │      │   APIs  │
    └─────────┘      └──────────┘      └─────────┘
```

## Fluxo de Transferência

```
1. [CLIENT] POST /transfer
   ↓
2. [NGINX] Proxy reverso para PHP-FPM
   ↓
3. [Slim] Roteamento → TransferController
   ↓
4. [Controller] Valida payload (campos obrigatórios, tipos)
   ↓
5. [TransferService] Validações de negócio:
   ├── ✓ Valor > 0
   ├── ✓ Payer ≠ Payee
   ├── ✓ Usuários existem
   ├── ✓ Payer não é lojista
   └── ✓ Saldo suficiente
   ↓
6. [AuthorizeService] GET https://util.devi.tools/api/v2/authorize
   └── ❌ Se negado → Exception 422
   ↓
7. [UserRepository] Inicia transação DB
   ├── BEGIN TRANSACTION
   ├── UPDATE users SET balance = balance - 100 WHERE id = payer
   ├── UPDATE users SET balance = balance + 100 WHERE id = payee
   └── COMMIT
   ↓
8. [NotifyService] POST (async) https://util.devi.tools/api/v1/notify
   └── Fire-and-forget (não bloqueia resposta)
   ↓
9. [Controller] Retorna JSON
   └── {"message": "Transferência realizada com sucesso"}
```

## Decisões Arquiteturais

### 1. **Por que Slim Framework?**
- ✅ Minimalista: sem "mágica", controle total
- ✅ PSR-compliant (PSR-7, PSR-11, PSR-15)
- ✅ Performático: overhead mínimo
- ✅ Flexível: não impõe estrutura rígida
- ❌ Contra: menos "batteries included" (escolha consciente)

### 2. **Separação em Camadas**
```
Controller → Service → Repository → Model
```

**Benefícios:**
- Fácil de testar (mocks em cada camada)
- Lógica de negócio isolada (Services)
- Troca de DB sem impacto (Repository pattern)
- Single Responsibility Principle

### 3. **Transações DB**
```php
$pdo->beginTransaction();
try {
    $payer->balance -= $value;
    $payee->balance += $value;
    $this->userRepo->updateBalance($payer);
    $this->userRepo->updateBalance($payee);
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}
```

**Garante:**
- ⚛️ Atomicidade: ou tudo acontece, ou nada
- 🔒 Consistência: saldos sempre corretos
- 🚫 Rollback automático em falha

### 4. **Notificação Assíncrona**
```php
$this->client->postAsync('...')->wait(false); // não espera
```

**Justificativa:**
- Não bloqueia a transferência
- Serviço externo instável não quebra fluxo principal
- Em produção: usar fila real (RabbitMQ, SQS, Redis Streams)

### 5. **Injeção de Dependências (PHP-DI)**
```php
public function __construct(
    private UserRepository $userRepo,
    private AuthorizeService $authorizeService,
    private NotifyService $notifyService
) {}
```

**Vantagens:**
- Fácil de testar (substituir por mocks)
- Baixo acoplamento
- Autowiring automático

## Princípios SOLID Aplicados

### 1. **Single Responsibility**
- `TransferController`: apenas recebe request e retorna response
- `TransferService`: apenas lógica de transferência
- `UserRepository`: apenas acesso a dados de usuários

### 2. **Open/Closed**
- Novos serviços externos podem ser adicionados sem modificar existentes
- Interface de Repository permite múltiplas implementações

### 3. **Liskov Substitution**
- Mocks substituem classes reais nos testes sem quebrar comportamento

### 4. **Interface Segregation**
- Services têm métodos específicos (isAuthorized, notify)
- Repository expõe apenas operações necessárias

### 5. **Dependency Inversion**
- Controller depende de abstrações (Services), não de implementações concretas

## Segurança

### 1. **SQL Injection**
```php
$stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]); // ✅ Prepared statement
```

### 2. **Validação de Entrada**
```php
// Múltiplas camadas:
// 1. Controller: valida estrutura
// 2. Service: valida regras de negócio
// 3. Repository: garante tipos corretos
```

### 3. **Tratamento de Erros**
```php
// Nunca expõe stack trace em produção
$app->addErrorMiddleware(
    displayErrorDetails: $_ENV['APP_ENV'] === 'development',
    logErrors: true
);
```

## Performance

### 1. **Transações Curtas**
- Apenas 2 UPDATEs dentro da transação
- Notificação fora da transação (não bloqueia DB)

### 2. **Prepared Statements**
- MySQL compila query uma vez, reutiliza

### 3. **Redis** (preparado para cache)
- Fácil adicionar cache de usuários
- Queue para notificações em produção

## Testes

### Estratégia de Testes

```
tests/
├── Unit/                    # Testes isolados
│   ├── UserTest.php         → Model
│   └── TransferServiceTest.php → Service (com mocks)
└── Integration/             # Testes E2E
    └── TransferApiTest.php  → HTTP → DB
```

### Cobertura Atual
- ✅ Model: 100%
- ✅ Service: validações principais
- ⏳ Repository: (requer DB de testes)
- ⏳ Controller: (requer HTTP client)

## Melhorias Futuras

### Curto Prazo
1. ✨ Adicionar índices compostos no DB
2. ✨ Implementar soft deletes
3. ✨ Adicionar campo `status` nas transferências

### Médio Prazo
1. 🚀 Migrar notificações para Redis Queue
2. 🚀 Adicionar rate limiting (Redis)
3. 🚀 Implementar JWT authentication

### Longo Prazo
1. 🌟 Event Sourcing para histórico completo
2. 🌟 CQRS (separar reads/writes)
3. 🌟 Microserviços (transferências, notificações, autenticação)

---

## Referências

- [Slim Framework Docs](https://www.slimframework.com/)
- [PHP-FIG PSRs](https://www.php-fig.org/psr/)
- [Clean Architecture (Uncle Bob)](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
