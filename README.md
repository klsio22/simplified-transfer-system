# PicPay Simplificado - Transfer System

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

## ✅ Checklist Completo - PicPay Simplificado

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

| Tecnologia | Versão | Comando |
|------------|--------|---------|
| PHP | 8.2/8.3 | `./run php:console` |
| Slim Framework | 4.12 | - |
| MySQL | 8.0 | `./run db:console` |
| Redis | Alpine | - |
| Nginx | Alpine | - |

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
./run db:console      # Acessa MySQL CLI
./run db:reset        # Reset do banco + migrations
./run db:populate     # Popula dados de teste
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

# 2. Inicie (aguarde 30s)
./run up

# 3. Teste
curl http://localhost:8080                    # Health check
curl http://localhost:8080/balance/1          # Consultar saldo
curl -X POST http://localhost:8080/transfer \ # Transferir
  -H "Content-Type: application/json" \
  -d '{"value": 50.00, "payer": 1, "payee": 4}'
```

**Dados de teste**: User #1 (comum, R$200) → User #4 (lojista, R$0)

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

## 🛠️ Stack Tecnológica

| Camada | Tecnologia | Versão | Justificativa |
|--------|-----------|--------|---------------|
| Runtime | PHP | 8.2 | Typed properties, enums, performance |
| Framework | Slim | 4.12 | Minimalista, PSR-compliant, performático |
| Servidor Web | Nginx | Alpine | Leve, rápido, produção-ready |
| Banco de Dados | MySQL | 8.0 | Transações ACID, confiável |
| Cache/Queue | Redis | Alpine | Rápido, simples, versátil |
| HTTP Client | GuzzleHTTP | 7.8 | PSR-18, bem documentado |
| DI Container | PHP-DI | 7.0 | PSR-11, autowiring |
| Testes | PHPUnit | 10.5 | Padrão de mercado |
| Análise Estática | PHPStan | 1.10 | Nível 8, rigoroso |
| Code Style | PHP-CS-Fixer | 3.45 | PSR-12, consistência |
| Containerização | Docker | Latest | Isolamento, portabilidade |  

## ⚡ Quick Start

```bash
# Opção 1: Usando script helper
git clone <seu-repo> && cd simplified-transfer-system
cp .env.example .env
./dev.sh start

# Opção 2: Usando Makefile
make start

# Teste a API
curl http://localhost:8080
curl -X POST http://localhost:8080/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 100, "payer": 1, "payee": 2}'
```

> 💡 **Dica**: Use `./dev.sh` ou `make help` para ver todos os comandos disponíveis

## 🚀 Como rodar o projeto

### Pré-requisitos

- Docker e Docker Compose
- Git

### Instalação passo a passo

```bash
# 1. Clone o repositório
git clone <seu-repositorio>
cd simplified-transfer-system

# 2. Copie o arquivo de configuração
cp .env.example .env

# 3. Suba os containers
docker-compose up -d --build

# 4. Aguarde os containers ficarem prontos (30 segundos)
sleep 30

# 5. Instale as dependências
docker-compose exec app composer install

# 6. Execute as migrations e seed
docker-compose exec app php bin/migrate.php

# 7. (Opcional) Inicie o worker de notificações em background
docker-compose exec -d app php bin/worker.php
```

### ✅ Verificar se está funcionando

A API estará disponível em: **http://localhost:8080**

```bash
# Health check
curl http://localhost:8080

# Teste de transferência
curl -X POST http://localhost:8080/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 100.00, "payer": 1, "payee": 2}'
```

### 📊 Dados de teste

O seed cria automaticamente:
Para rodar rapidamente os testes e popular os dados de exemplo, use os comandos abaixo:

```bash
docker compose exec app php bin/crud-test.php        # teste CRUD (create/read/update)
docker compose exec app php bin/integration-test.php # teste de integração / DI
docker compose exec app php bin/db-reset.php         # reset + seed de dados de teste
```

O seed cria automaticamente:

| ID | Nome              | Tipo     | CPF/CNPJ         | Email               | Saldo     |
|----|-------------------|----------|------------------|---------------------|-----------|
| 1  | João Silva        | comum    | 12345678901      | joao@example.com    | R$ 1000   |
| 2  | Maria Santos      | comum    | 98765432100      | maria@example.com   | R$ 500    |
| 3  | Loja ABC          | lojista  | 12345678000199   | loja@example.com    | R$ 0      |
| 4  | Mercado Central   | lojista  | 98765432000188   | mercado@example.com | R$ 0      |

## 📁 Estrutura de pastas

```
.
├── bin/
│   ├── migrate.php           # Script de migração e seed
│   └── worker.php            # Worker de notificações
├── config/
│   └── container.php         # Container DI (PSR-11)
├── docker/
│   ├── nginx/nginx.conf
│   └── php/local.ini
├── docs/
│   ├── API.md                # Documentação da API
│   └── ARCHITECTURE.md       # Decisões arquiteturais
├── public/
│   └── index.php             # Entrypoint da aplicação
├── src/
│   ├── Controllers/
│   │   └── TransferController.php
│   ├── Entities/
│   │   ├── User.php
│   │   ├── Wallet.php
│   │   └── Transaction.php
│   ├── Enums/
│   │   └── UserType.php
│   ├── Exceptions/
│   │   ├── AppException.php
│   │   ├── ValidationException.php
│   │   ├── InsufficientBalanceException.php
│   │   ├── MerchantCannotSendException.php
│   │   ├── UnauthorizedTransferException.php
│   │   └── UserNotFoundException.php
│   ├── Middleware/
│   │   └── JsonMiddleware.php
│   ├── Repositories/
│   │   ├── UserRepository.php
│   │   ├── WalletRepository.php
│   │   └── TransactionRepository.php
│   └── Services/
│       ├── AuthorizeService.php
│       ├── NotifyService.php
│       └── TransferService.php
├── tests/
│   ├── Integration/
│   │   └── TransferTest.php
│   └── Unit/
│       ├── UserTest.php
│       └── WalletTest.php
├── .env.example
├── .gitignore
├── .php-cs-fixer.php
├── composer.json
├── docker-compose.yml
├── Dockerfile
├── phpstan.neon
├── phpunit.xml
└── README.md
```


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

## 📄 Licença

MIT License - Projeto open source desenvolvido como desafio técnico PicPay Simplificado.

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

**Veja mais**: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)  

## 🚀 Proposta de melhorias futuras

### Curto prazo (MVP++)
- Autenticação JWT ou API Token  
- Rate limiting com middleware  
- Circuit Breaker para serviços externos instáveis  
- Logs estruturados com Monolog  
- CI/CD com GitHub Actions  

### Médio prazo (Escala)
- Event Dispatcher para auditoria  
- Observabilidade com OpenTelemetry/Prometheus  
- Cache com Redis para dados frequentes  
- Read replicas do MySQL  
- Queue mais robusta (RabbitMQ/SQS)  

### Longo prazo (Arquitetura)
- CQRS + Event Sourcing  
- Microserviços separados  
- Migrar histórico para NoSQL  
- Kubernetes deployment  
- Multi-região  

**Veja mais detalhes em**: [docs/IMPROVEMENTS.md](docs/IMPROVEMENTS.md)

---

## 📌 Pontos de Destaque

### ✅ O que foi implementado

- ✔️ Todos os requisitos obrigatórios do desafio
- ✔️ Arquitetura limpa e bem estruturada
- ✔️ Cobertura de testes adequada
- ✔️ Análise estática rigorosa (PHPStan nível 8)
- ✔️ Code style consistente (PSR-12)
- ✔️ Documentação completa e clara
- ✔️ Docker setup production-ready
- ✔️ Notificações assíncronas
- ✔️ Tratamento de erros robusto

### 💪 Diferenciais

- Repository Pattern para abstração de dados
- Service Layer para regras de negócio
- Dependency Injection com PHP-DI
- Enums com PHP 8.2 para type safety
- Transações atômicas com rollback
- Queue assíncrona com Redis
- Worker para processamento em background
- Middleware customizado
- Exceptions personalizadas por contexto
- Script helper para desenvolvimento
- Documentação detalhada de arquitetura
- Guia de troubleshooting
- Roadmap de melhorias futuras

### 🎓 Conceitos aplicados

- **SOLID**: Single Responsibility, Dependency Inversion
- **Design Patterns**: Repository, Service Layer, Dependency Injection
- **PSRs**: 4 (Autoload), 7 (HTTP), 11 (Container), 12 (Style), 15 (Handlers)
- **Clean Code**: Nomes descritivos, funções pequenas, sem duplicação
- **Testing**: Unitário e integração, AAA pattern
- **DevOps**: Docker, containerização, script de setup

---

## 👨‍💻 Autor

Desenvolvido como desafio técnico para demonstrar conhecimentos em:
- Arquitetura de software
- PHP moderno (8.2+)
- Boas práticas de desenvolvimento
- Testes automatizados
- DevOps e containerização

## 📄 Licença

Este projeto é de código aberto e está disponível sob a licença MIT.  

## 🧪 Testes e Qualidade de Código

### Rodar testes

```bash
# Todos os testes
docker-compose exec app composer test

# Com cobertura de código
docker-compose exec app composer test:coverage
# Relatório gerado em: coverage/index.html
```

### Análise estática

```bash
# PHPStan (nível 8)
docker-compose exec app composer phpstan

# PHP CS Fixer - Corrigir formatação
docker-compose exec app composer cs-fixer

# PHP CS Fixer - Apenas verificar
docker-compose exec app composer cs-check
```

### Cobertura esperada

- Testes unitários: Entities, Services
- Testes de integração: Endpoint /transfer
- Cobertura > 70% do código

## 📖 Documentação Adicional

- [📡 API](docs/API.md) - Endpoints, exemplos e respostas
- [🏛️ Arquitetura](docs/ARCHITECTURE.md) - Decisões técnicas e padrões
- [🚀 Melhorias](docs/IMPROVEMENTS.md) - Roadmap e features futuras
- [🔧 Troubleshooting](docs/TROUBLESHOOTING.md) - Soluções de problemas comuns
- [🤝 Contribuindo](CONTRIBUTING.md) - Como contribuir com o projeto
- [📝 Changelog](CHANGELOG.md) - Histórico de mudanças

## 🛠️ Comandos Úteis

```bash
# Ver logs da aplicação
docker-compose logs -f app

# Ver logs do nginx
docker-compose logs -f nginx

# Acessar o container
docker-compose exec app bash

# Parar os containers
docker-compose down

# Parar e remover volumes
docker-compose down -v

# Reiniciar apenas a aplicação
docker-compose restart app
```