# 🎯 Guia para Avaliadores

Olá! Obrigado por avaliar este projeto. Este guia vai ajudá-lo a explorar rapidamente o que foi implementado.

## ⚡ Setup Rápido (3 minutos)

```bash
# 1. Clone o repositório
git clone <repo-url>
cd simplified-transfer-system

# 2. Inicie tudo
./dev.sh start
# ou: make start

# 3. Aguarde ~30 segundos enquanto os containers sobem
```

✅ API estará rodando em: **http://localhost:8080**

## 🧪 Teste Rápido

```bash
# Health check
curl http://localhost:8080

# Transferência bem-sucedida (João → Maria)
curl -X POST http://localhost:8080/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 100, "payer": 1, "payee": 2}'

# Lojista tentando enviar (deve falhar)
curl -X POST http://localhost:8080/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 50, "payer": 3, "payee": 1}'

# Saldo insuficiente (deve falhar)
curl -X POST http://localhost:8080/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 99999, "payer": 1, "payee": 2}'
```

## 📂 Principais Arquivos para Revisar

### 1️⃣ Regras de Negócio (mais importante)
- [src/Services/TransferService.php](src/Services/TransferService.php) - **Lógica principal**
- [src/Entities/User.php](src/Entities/User.php) - Modelo de usuário
- [src/Enums/UserType.php](src/Enums/UserType.php) - Tipos com comportamento

### 2️⃣ Arquitetura
- [config/container.php](config/container.php) - Dependency Injection
- [src/Repositories/](src/Repositories/) - Acesso a dados
- [src/Controllers/](src/Controllers/) - Endpoints

### 3️⃣ Qualidade
- [tests/](tests/) - Testes unitários e integração
- [phpstan.neon](phpstan.neon) - Análise estática nível 8
- [.php-cs-fixer.php](.php-cs-fixer.php) - Code style PSR-12

### 4️⃣ Infraestrutura
- [docker-compose.yml](docker-compose.yml) - Orquestração
- [Dockerfile](Dockerfile) - Imagem PHP
- [bin/migrate.php](bin/migrate.php) - Migrations + Seed

### 5️⃣ Documentação
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) - Decisões técnicas
- [docs/API.md](docs/API.md) - Endpoints
- [SUMMARY.md](SUMMARY.md) - Resumo executivo

## 🔍 Pontos de Atenção para Avaliar

### ✅ Requisitos Obrigatórios

- [ ] Endpoint POST /transfer funciona?
- [ ] Valida saldo antes de transferir?
- [ ] Bloqueia lojistas de enviar?
- [ ] Consulta serviço autorizador?
- [ ] Usa transações DB com rollback?
- [ ] Envia notificações assíncronas?
- [ ] Diferencia usuários comuns e lojistas?
- [ ] Valida campos obrigatórios?
- [ ] Retorna erros padronizados?

### 🌟 Diferenciais

- [ ] Separação de camadas (Controller → Service → Repository)
- [ ] Dependency Injection configurado
- [ ] Exceptions personalizadas por contexto
- [ ] Testes automatizados
- [ ] Análise estática (PHPStan)
- [ ] Code style (PSR-12)
- [ ] Documentação clara
- [ ] Docker production-ready

## 🧪 Rodar Testes

```bash
# Todos os testes
./dev.sh test
# ou: make test

# Análise estática
./dev.sh phpstan
# ou: make phpstan

# Verificar code style
./dev.sh cs-check
# ou: make cs-check
```

## 📊 Verificar Saúde da Aplicação

```bash
./dev.sh health
# ou: make health
```

Isso vai mostrar:
- Status dos containers
- Conectividade do MySQL
- Conectividade do Redis
- Resposta da API

## 🎯 Cenários de Teste

### ✅ Cenário 1: Transferência bem-sucedida
```bash
curl -X POST http://localhost:8080/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 100, "payer": 1, "payee": 2}'
```
**Esperado**: Status 200, transaction com status "completed"

### ❌ Cenário 2: Lojista não pode enviar
```bash
curl -X POST http://localhost:8080/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 50, "payer": 3, "payee": 1}'
```
**Esperado**: Status 422, "Lojistas não podem enviar transferências"

### ❌ Cenário 3: Saldo insuficiente
```bash
curl -X POST http://localhost:8080/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 99999, "payer": 2, "payee": 1}'
```
**Esperado**: Status 422, "Saldo insuficiente"

### ❌ Cenário 4: Validação de campos
```bash
curl -X POST http://localhost:8080/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": -50, "payer": 1}'
```
**Esperado**: Status 422, lista de erros de validação

### ❌ Cenário 5: Usuário não existe
```bash
curl -X POST http://localhost:8080/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 50, "payer": 1, "payee": 999}'
```
**Esperado**: Status 404, "Usuário não encontrado"

## 📖 Estrutura do Código

```
src/
├── Controllers/       # HTTP handlers
├── Services/          # Business logic ⭐
├── Repositories/      # Data access
├── Entities/          # Domain models
├── Enums/             # Type-safe enums
├── Exceptions/        # Custom exceptions
└── Middleware/        # HTTP interceptors
```

## 🎓 Conceitos Demonstrados

1. **SOLID**
   - Single Responsibility (cada classe uma responsabilidade)
   - Dependency Inversion (depende de abstrações)

2. **Design Patterns**
   - Repository Pattern (abstração de dados)
   - Service Layer (regras de negócio)
   - Dependency Injection (baixo acoplamento)

3. **PSRs**
   - PSR-4: Autoloading
   - PSR-7: HTTP Messages
   - PSR-11: Container
   - PSR-12: Code Style
   - PSR-15: HTTP Handlers

4. **Clean Code**
   - Nomes descritivos
   - Funções pequenas
   - Sem duplicação
   - Comentários úteis

## ⏱️ Checklist de Avaliação (5 min)

1. ⏱️ **Minute 1**: Suba o projeto (`./dev.sh start`)
2. ⏱️ **Minute 2**: Teste endpoint com curl (3 cenários)
3. ⏱️ **Minute 3**: Rode os testes (`./dev.sh test`)
4. ⏱️ **Minute 4**: Revise TransferService.php
5. ⏱️ **Minute 5**: Check PHPStan e docs

## 💬 Perguntas Frequentes

**Q: Por que Slim Framework?**
A: Minimalista, PSR-compliant, sem "magia", total controle sobre arquitetura.

**Q: Por que não usar Eloquent/Doctrine?**
A: Para demonstrar conhecimento de SQL puro, transações manuais e controle total.

**Q: Por que Redis para fila?**
A: Simplicidade, performance, sem overhead de brokers pesados.

**Q: Worker está rodando?**
A: Execute: `./dev.sh worker` para iniciar processamento de notificações.

## 🐛 Problemas?

Consulte: [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md)

Ou execute:
```bash
./dev.sh reset  # Reset completo
```

## 📞 Contato

Se tiver dúvidas durante a avaliação, todos os arquivos estão bem documentados.

**Boa avaliação! 🚀**
