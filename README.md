# Simplified Transfer System

![PHP](https://img.shields.io/badge/PHP-8.2%2F8.3-777BB4?logo=php) ![Slim](https://img.shields.io/badge/Slim-4.12-719E40) ![Tests](https://img.shields.io/badge/Tests-84%20passing-success) ![PHPStan](https://img.shields.io/badge/PHPStan-Level%208-8892BF) ![PSR-12](https://img.shields.io/badge/PSR-12-blue)

**API RESTful de transferências** com **Slim Framework 4**, **Clean Architecture**, **SOLID** e 84 testes automatizados.

Sistema completo de pagamentos entre usuários comuns e lojistas, com transações atômicas, validação de saldo, autorização externa e notificações assíncronas.

---

## 🚀 Quick Start

```bash
# Clone e inicie
git clone <repo> && cd simplified-transfer-system
./run up

# Teste a API (aguarde 30s)
curl -X POST http://localhost:8080/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 100.00, "payer": 1, "payee": 4}'
```

---

## ✅ Requisitos do Sistema - Checklist Completo

### ✓ Regras de Negócio Implementadas

- [x] **Cadastro**: Nome, CPF/CNPJ, Email, Senha com unicidade
- [x] **Transferências**: Usuários enviam para lojistas e outros usuários
- [x] **Bloqueio**: Lojistas só recebem, não enviam
- [x] **Validação**: Saldo suficiente antes da transferência
- [x] **Autorização**: Consulta serviço externo (GET mock)
- [x] **Transação**: Operação atômica com rollback automático
- [x] **Notificação**: Envio assíncrono (POST mock)
- [x] **API RESTful**: POST /transfer conforme contrato

### ✓ Qualidade de Código

- [x] **PSRs**: PSR-4, PSR-7, PSR-11, PSR-12
- [x] **SOLID**: Single Responsibility, Dependency Inversion
- [x] **Design Patterns**: Repository, Service Layer, DI, Factory
- [x] **Testes**: 84 testes (16 controllers + 30 services + 22 repos + 16 integration)
- [x] **Análise Estática**: PHPStan level 8 (0 erros), PHPCS PSR-12, PHPMD
- [x] **Docker**: docker-compose.yml completo (PHP + Nginx + MySQL + Redis)
- [x] **CI Ready**: Script `./run phpfullcheck` para pipeline
- [x] **Documentação**: README + ARCHITECTURE.md detalhada

---


## 📁 Estrutura do Projeto

```
simplified-transfer-system/
├── src/
│   ├── Controllers/     # 4 controllers (Health, Balance, Transfer, User)
│   ├── Services/        # 5 services (Transfer, Authorize, Notify, Balance, User)
│   ├── Repositories/    # 1 repository (UserRepository)
│   ├── Models/          # 1 model (User com lógica de domínio)
│   ├── Entity/          # 2 entities Cycle ORM (User, Transfer)
│   └── Core/            # Exceções customizadas
├── tests/
│   ├── Unit/            # 68 testes (Controllers, Services, Repositories, Models)
│   └── Integration/     # 16 testes (4 arquivos de endpoints)
├── config/              # Database, DI Container, ORM
├── routes/              # api.php (definição de rotas)
├── migrations/          # Schema SQL
├── docker/              # nginx.conf
├── public/              # index.php (entrypoint)
├── bin/                 # Scripts utilitários
├── docker-compose.yml   # Orquestração (PHP + Nginx + MySQL + Redis)
├── phpstan.neon         # PHPStan level 8
├── phpunit.xml          # Configuração de testes
├── .php-cs-fixer.php    # PSR-12
└── run                  # 🚀 Script helper CLI
```

**84 testes** | **0 erros PHPStan** | **0 violações PHPCS** | **Cognitive Complexity < 15**

---

## 🛠️ Stack & Comandos

| Tecnologia     | Versão  |
| -------------- | ------- |
| PHP            | 8.2/8.3 |
| Slim Framework | 4.12    |
| MySQL          | 8.0     |
| Redis          | Alpine  |
| Nginx          | Alpine  |

### Comandos do Projeto

```bash
# Gerenciamento
./run up              # Inicia containers
./run down            # Para containers
./run ps              # Status dos containers

# Testes & Qualidade
./run test            # Roda todos os testes (84 testes)
./run phpstan         # Análise estática (level 8)
./run phpcs           # Verifica code style (PSR-12)
./run phpcbf          # Corrige code style automaticamente
./run phpfmt          # PHP-CS-Fixer
./run phpmd           # Detecta code smells
./run phpfullcheck    # Roda tudo (cbf + fmt + cs + stan + md + test)

# Banco de Dados
./run db:reset        # Reset do banco + seed de dados
./run db:crud         # Teste CRUD (create/read)
./run db:integration  # Teste de integração com DI Container
```

---

## 🎯 Regras de Negócio

**Tipos de Usuário**:

- **Common** (Comum): CPF → Pode **enviar** e **receber**
- **Shopkeeper** (Lojista): CNPJ → Só pode **receber**

**Fluxo de Transferência**:

1. Validar payload (value > 0, campos obrigatórios)
2. Verificar se payer não é lojista
3. Verificar saldo do payer
4. Consultar serviço autorizador externo
5. Iniciar transação DB → debitar + creditar + registrar
6. Commit (ou rollback se erro)
7. Notificar recebedor (assíncrono)

**Validações em 4 camadas**: Controller → Service → External → Database

---

## 🔧 Instalação & Uso

```bash
# 1. Clone e configure
git clone <repo> && cd simplified-transfer-system
cp .env.example .env

# 2. Inicie (aguarde 30s para containers ficarem prontos)
./run up

# 3. Reset banco de dados + seed de dados de teste
./run db:reset

# 4. Teste a API
curl http://localhost:8080
curl -X POST http://localhost:8080/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 100.00, "payer": 1, "payee": 4}'
```

---

## 🧪 Testes & Qualidade

```bash
./run test                # 84 testes passando
./run phpstan             # PHPStan level 8: 0 erros
./run phpcs               # PHPCS PSR-12: 0 violações
./run phpfullcheck        # Roda tudo + testes
```

**Cobertura**: 84 testes (68 unitários + 16 integração) em 4 camadas (Controllers, Services, Repositories, Models)

---

- [x] Validação de saldo do pagador antes da transferência  
- [x] Bloqueio de transferências enviadas por lojistas  
- [x] Consulta ao serviço autorizador externo (mock GET)  
- [x] Operação de transferência dentro de transação DB (rollback automático em falha)  
- [x] Envio de notificação ao recebedor via serviço externo (mock POST)  
- [x] Notificação executada de forma assíncrona (fire-and-forget)  
- [x] Tipos de usuário: comum (pode enviar) e lojista (só recebe)  
- [x] Validação completa de campos e existência de usuários  
- [x] Tratamento de erros com respostas JSON padronizadas (400, 422, 500)  
- [x] Uso de Docker + docker-compose (PHP 8.2 + Nginx + MySQL + Redis)  
- [x] Testes automatizados com PHPUnit (unitários + integração)  
- [x] Camadas separadas: Routes → Controllers → Services → Repositories  
- [x] Adesão total às PSRs (PSR-12, PSR-4, PSR-7, PSR-11, PSR-15)  
- [x] Análise estática com PHPStan nível 8 e PHP-CS-Fixer  
- [x] Container DI (PHP-DI) para injeção de dependências  
- [x] Documentação completa + instruções claras de execução  
- [x] Proposta de melhorias arquiteturais no final (ver ARCHITECTURE.md)  

---

## 📊 Dados de Teste

Para popular automaticamente o banco com dados de exemplo:

```bash
./run db:reset        # Reset + seed de dados (4 usuários de teste)
./run db:crud         # Teste CRUD básico
./run db:integration  # Teste de integração com DI Container
```

**Usuários criados automaticamente**:

| ID   | Nome            | Tipo    | CPF/CNPJ       | Email               | Saldo   |
| ---- | --------------- | ------- | -------------- | ------------------- | ------- |
| 1    | João Silva      | comum   | 12345678901    | joao@example.com    | R$ 1000 |
| 2    | Maria Santos    | comum   | 98765432100    | maria@example.com   | R$ 500  |
| 3    | Loja ABC        | lojista | 12345678000199 | loja@example.com    | R$ 0    |
| 4    | Mercado Central | lojista | 98765432000188 | mercado@example.com | R$ 0    |




## 📚 Arquitetura

**Clean Architecture** com 4 camadas:

```
Controllers (HTTP) → Services (Business Logic) → Repositories (Data) → Models (Domain)
```

**Princípios SOLID**:

- Single Responsibility: Cada classe tem uma única responsabilidade
- Dependency Inversion: Controllers dependem de Services (abstrações)
- Open/Closed: Exceções estendem `AppException`, fácil adicionar novas

**Design Patterns**:

- **Repository**: Abstrai acesso a dados (fácil trocar banco)
- **Service Layer**: Centraliza lógica de negócio (reutilizável)
- **Dependency Injection**: PHP-DI gerencia dependências
- **Factory**: `AppFactory::create()` do Slim

**Transações Atômicas**:

```php
try {
    $db->beginTransaction();
    $this->debitPayer($payer, $value);
    $this->creditPayee($payee, $value);
    $this->recordTransfer(...);
    $db->commit();
} catch (\Exception $e) {
    $db->rollBack(); // Saldo restaurado automaticamente
    throw $e;
}
```

📖 **Detalhes completos**: [ARCHITECTURE.md](ARCHITECTURE.md)

---

## 💡 Melhorias Futuras

**Curto Prazo**: Autenticação JWT, Rate Limiting, Retry Policy, Logs estruturados (Monolog)

**Médio Prazo**: Event Dispatcher, Observabilidade (Prometheus), Read Replicas, Queue (RabbitMQ)

**Longo Prazo**: CQRS + Event Sourcing, Microserviços, Kubernetes, Multi-região

---

**Desenvolvido com ❤️ usando Slim 4 + Clean Architecture + 84 testes automatizados**

### Por que Slim Framework 4?

- **Minimalista**: Sem bloat, apenas o essencial
- **PSR-compliance**: Aderência total às PSRs (4, 7, 11, 12, 15)
- **Performance**: Overhead mínimo
- **Controle**: Sem "magia", tudo explícito
- **Testável**: Fácil de mockar e testar

### Por que Repository Pattern?

- **Abstração**: Separa lógica de negócio da persistência
- **Testabilidade**: Fácil mockar para testes
- **Manutenibilidade**: Trocar banco sem afetar regras de negócio
- **Single Responsibility**: Cada repository cuida de uma entidade

### Por que Service Layer?

- **Centraliza regras de negócio**: Uma única fonte da verdade
- **Reutilizável**: Pode ser usado por controllers, CLI, jobs
- **Testável**: Testes unitários isolados
- **Orquestr ação**: Coordena repositórios e serviços externos

### Por que transações manuais com PDO?

- **Controle total**: Rollback explícito em caso de erro
- **ACID**: Garante atomicidade das operações
- **Performance**: Sem overhead de ORMs
- **Simplicidade**: Menos camadas de abstração

### Por que Redis para fila?

- **Simplicidade**: Não precisa de broker pesado
- **Performance**: Extremamente rápido
- **Confiável**: Persistência opcional
- **Familiar**: Amplamente adotado

### Por que notificações assíncronas?

- **Não bloqueante**: Resposta HTTP rápida
- **Resiliência**: Falhas não afetam transferência
- **Escalável**: Worker pode ser escalado separadamente
- **Retry**: Pode reprocessar falhas

---

## 💡 Melhorias Futuras

- **Curto Prazo**: Autenticação JWT, Rate Limiting, Circuit Breaker, Logs (Monolog), CI/CD (GitHub Actions)
- **Médio Prazo**: Event Dispatcher, Observabilidade (Prometheus), Cache Redis, Read Replicas, Queue (RabbitMQ)
- **Longo Prazo**: CQRS + Event Sourcing, Microserviços, NoSQL, Kubernetes, Multi-região

---

## 📌 Destaque - O que foi implementado

✔️ Todos os requisitos | ✔️ Clean Architecture | ✔️ SOLID + Design Patterns | ✔️ 84 testes + cobertura | ✔️ PHPStan 8 | ✔️ Docker ready | ✔️ Documentação completa

## 🛠️ Comandos Úteis Docker

```bash
# Ver status dos containers
./run ps

# Ver logs da aplicação
docker compose logs -f app

# Ver logs do nginx
docker compose logs -f nginx

# Acessar shell do container
docker compose exec app bash

# Parar os containers
./run down

# Remover containers e volumes
docker compose down -v

# Reiniciar apenas a aplicação
docker compose restart app
```
---

## � Licença

MIT License - Projeto open source de sistema de transferências simplificado.