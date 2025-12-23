# Simplified Transfer System

![PHP Version](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)
![Slim Framework](https://img.shields.io/badge/Slim-4.12-719E40?logo=slim&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-Alpine-DC382D?logo=redis&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white)
![PHPStan](https://img.shields.io/badge/PHPStan-Level%208-8892BF)
![PSR](https://img.shields.io/badge/PSR-4%20%7C%207%20%7C%2011%20%7C%2012%20%7C%2015-blue)

API RESTful minimalista para realizar transferências de dinheiro entre usuários comuns e lojistas.

Implementada com **Slim Framework 4** — escolha consciente por ser leve, performático e permitir total controle sobre a arquitetura sem métodos mágicos ou facilidades excessivas.

> 💡 **Projeto desenvolvido seguindo boas práticas de engenharia de software**, clean code, SOLID, design patterns e PSRs.

---

## 🚀 Quick Start

```bash
# 1. Clone e acesse o diretório
git clone <repo> && cd simplified-transfer-system

# 2. Inicie os containers
docker-compose up -d

# 3. Aguarde 30s para o MySQL inicializar

# 4. Faça uma transferência de teste
curl -X POST http://localhost:8080/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 100.00, "payer": 1, "payee": 4}'

# Resposta: {"message":"Transferência realizada com sucesso"}
```

📖 **Leia o [QUICKSTART.md](QUICKSTART.md) para mais detalhes e exemplos**  
🏛️ **Veja a [ARCHITECTURE.md](ARCHITECTURE.md) para entender a arquitetura**

---

## ✅ Checklist de entrega

- [x] Endpoint POST /transfer conforme contrato solicitado  
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

## 🎯 Decisões técnicas

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