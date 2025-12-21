# 🚀 Guia Rápido de Execução

## Pré-requisitos
- Docker & Docker Compose instalados
- Porta 8080 disponível

## 1️⃣ Iniciar o projeto

```bash
# Clone ou acesse o diretório
cd simplified-transfer-system

# Suba os containers
docker-compose up -d

# Aguarde ~30s para o MySQL inicializar
```

## 2️⃣ Testar o endpoint

```bash
# Transferência bem-sucedida (usuário comum → lojista)
curl -X POST http://localhost:8080/transfer \
  -H "Content-Type: application/json" \
  -d '{
    "value": 100.00,
    "payer": 1,
    "payee": 4
  }'

# Resposta esperada:
# {"message":"Transferência realizada com sucesso"}
```

## 3️⃣ Cenários de teste

### ✅ Transferência válida
```bash
curl -X POST http://localhost:8080/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 50.00, "payer": 1, "payee": 2}'
```

### ❌ Lojista tentando enviar (deve falhar)
```bash
curl -X POST http://localhost:8080/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 50.00, "payer": 4, "payee": 1}'

# Resposta: {"error":"Lojistas não podem realizar transferências"}
```

### ❌ Saldo insuficiente
```bash
curl -X POST http://localhost:8080/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 9999.00, "payer": 1, "payee": 2}'

# Resposta: {"error":"Saldo insuficiente"}
```

## 4️⃣ Verificar banco de dados

```bash
# Acessar MySQL
docker exec -it transfer-mysql mysql -u transfer_user -ptransfer_pass simplified_transfer

# Ver usuários e saldos
SELECT id, full_name, type, balance FROM users;

# Ver histórico de transferências
SELECT * FROM transfers ORDER BY created_at DESC LIMIT 10;
```

## 5️⃣ Executar testes

```bash
# Rodar testes dentro do container
docker exec -it transfer-app composer test

# Com cobertura de código
docker exec -it transfer-app composer test:coverage

# Análise estática (PHPStan)
docker exec -it transfer-app composer phpstan
```

## 🛠️ Comandos úteis

```bash
# Ver logs da aplicação
docker-compose logs -f app

# Parar containers
docker-compose down

# Rebuild completo
docker-compose down -v
docker-compose up -d --build
```

## 📊 Dados de teste

| ID | Nome | Tipo | Saldo Inicial |
|----|------|------|---------------|
| 1 | João Silva | common | R$ 1.000,00 |
| 2 | Maria Oliveira | common | R$ 500,00 |
| 3 | Pedro Santos | common | R$ 750,00 |
| 4 | Loja ABC Ltda | shopkeeper | R$ 0,00 |
| 5 | Comércio XYZ ME | shopkeeper | R$ 150,00 |

## 🏗️ Estrutura do projeto

```
simplified-transfer-system/
├── public/index.php              # Entrypoint
├── src/
│   ├── Controllers/              # Camada de apresentação
│   │   └── TransferController.php
│   ├── Services/                 # Lógica de negócio
│   │   ├── TransferService.php
│   │   ├── AuthorizeService.php
│   │   └── NotifyService.php
│   ├── Repositories/             # Acesso a dados
│   │   └── UserRepository.php
│   └── Models/                   # Entidades
│       └── User.php
├── config/                       # Configurações
├── routes/                       # Definição de rotas
├── migrations/                   # SQL de criação
├── tests/                        # Testes unitários/integração
├── docker-compose.yml            # Orquestração Docker
└── README.md
```

## 🎯 Checklist de features implementadas

- [x] Endpoint POST /transfer com validação completa
- [x] Bloqueio de transferências de lojistas
- [x] Validação de saldo antes da transferência
- [x] Consulta ao serviço autorizador externo
- [x] Transação DB com rollback automático
- [x] Notificação assíncrona (fire-and-forget)
- [x] Separação de camadas (MVC + Services + Repositories)
- [x] Tratamento de erros com status HTTP corretos
- [x] Docker Compose completo (PHP 8.2 + Nginx + MySQL + Redis)
- [x] Testes unitários com PHPUnit
- [x] PHPStan nível 8 + PHP-CS-Fixer
- [x] Documentação completa

## 📚 Stack Tecnológica

- **PHP 8.2** com Typed Properties e Named Arguments
- **Slim Framework 4** (minimalista, PSR-compliant)
- **PHP-DI** para injeção de dependências
- **MySQL 8.0** com transações ACID
- **Redis** para cache/queue
- **Nginx** como proxy reverso
- **Docker** para ambientes isolados

## 🔒 Segurança

- Prepared statements para prevenir SQL injection
- Validação de entrada em múltiplas camadas
- Transações DB para garantir consistência
- Logs de erros sem expor dados sensíveis

## 📈 Próximos passos / Melhorias

1. **Autenticação JWT** para proteger endpoints
2. **Rate limiting** com Redis
3. **Queue real** (RabbitMQ/SQS) para notificações
4. **Event Sourcing** para histórico completo
5. **Logs estruturados** (Monolog + ELK)
6. **Metrics** com Prometheus
7. **CI/CD** com GitHub Actions
8. **API Gateway** para throttling e versioning

---

**Desenvolvido com ❤️ usando Slim Framework 4 + Clean Architecture**
