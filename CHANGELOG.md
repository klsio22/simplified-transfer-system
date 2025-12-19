# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-12-19

### ✨ Added

- **Endpoint POST /transfer**: Realiza transferências entre usuários
- **Validação de saldo**: Verifica saldo antes de processar transferência
- **Bloqueio de lojistas**: Impede que lojistas enviem transferências
- **Serviço autorizador externo**: Integração com mock de autorização
- **Transações de banco**: Operações atômicas com rollback automático
- **Notificações assíncronas**: Envio via queue Redis + worker
- **Tipos de usuário**: Common e Merchant com regras diferentes
- **Validação completa**: Campos obrigatórios e tipos de dados
- **Tratamento de erros**: Respostas JSON padronizadas
- **Exceptions personalizadas**: Para cada tipo de erro de negócio
- **Camadas separadas**: Controllers, Services, Repositories, Entities
- **Docker Compose**: Setup completo com PHP, Nginx, MySQL, Redis
- **Testes automatizados**: Unitários e de integração com PHPUnit
- **Análise estática**: PHPStan nível 8
- **Code style**: PHP-CS-Fixer com PSR-12
- **Container DI**: PHP-DI com PSR-11
- **Migrations e Seed**: Script de setup do banco
- **Worker de notificações**: Processamento assíncrono de fila
- **Documentação completa**: API, arquitetura, troubleshooting
- **Script helper**: dev.sh para facilitar desenvolvimento

### 🏗️ Architecture

- Repository Pattern para abstração de dados
- Service Layer para regras de negócio
- Dependency Injection com PHP-DI
- Enums com PHP 8.2 para tipos de usuário
- Middleware para validação JSON
- Transações manuais com PDO

### 📚 Documentation

- README.md com guia completo
- docs/API.md com endpoints e exemplos
- docs/ARCHITECTURE.md com decisões técnicas
- docs/IMPROVEMENTS.md com roadmap futuro
- docs/TROUBLESHOOTING.md com soluções de problemas
- CONTRIBUTING.md com guia de contribuição

### 🧪 Testing

- Testes unitários para Entities
- Testes de integração para endpoint /transfer
- Cobertura de testes configurada
- PHPUnit 10.5 com configuração XML

### ⚙️ Configuration

- PSR-4 autoloading
- PSR-12 code style
- PHPStan level 8
- PHP-CS-Fixer rules
- Docker multi-container setup
- Environment variables com .env

### 🔐 Security

- Prepared statements (SQL Injection protection)
- Password hashing com bcrypt
- Validação rigorosa de entrada
- Transações para consistência
- Verificação de tipo de usuário

[1.0.0]: https://github.com/usuario/simplified-transfer-system/releases/tag/v1.0.0
