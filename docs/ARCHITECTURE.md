# Arquitetura do Sistema

## Estrutura de Camadas

O projeto segue uma arquitetura em camadas limpa, separando responsabilidades:

```
┌─────────────────────────────────────┐
│         Controllers                 │  ← Recebe requisições HTTP
├─────────────────────────────────────┤
│          Services                   │  ← Regras de negócio
├─────────────────────────────────────┤
│        Repositories                 │  ← Acesso a dados
├─────────────────────────────────────┤
│      Entities/Models                │  ← Representação de dados
└─────────────────────────────────────┘
```

## Fluxo de Transferência

1. **Request** chega no `TransferController`
2. **Controller** delega para `TransferService`
3. **Service** executa:
   - Valida entrada
   - Busca usuários (`UserRepository`)
   - Verifica tipo de usuário
   - Inicia transação DB
   - Consulta serviço autorizador externo (`AuthorizeService`)
   - Atualiza saldos das carteiras (`WalletRepository`)
   - Registra transação (`TransactionRepository`)
   - Commit da transação
   - Enfileira notificação (`NotifyService`)
4. **Worker** processa notificação assincronamente

## Decisões Técnicas

### Por que Slim Framework?

- Minimalista e performático
- Total aderência às PSRs
- Controle total sobre arquitetura
- Sem "magia" (não usa annotations, refletion excessiva, etc)
- Fácil de testar

### Por que Repository Pattern?

- Abstrai lógica de acesso a dados
- Facilita testes (pode mockar repositórios)
- Permite trocar implementação de persistência

### Por que Service Layer?

- Centraliza regras de negócio
- Reutilizável em diferentes contextos
- Fácil de testar isoladamente

### Transações de Banco

- Usamos PDO direto para controle total
- Transações garantem atomicidade (ACID)
- Rollback automático em caso de erro

### Notificações Assíncronas

- Redis como fila simples
- Worker processa em background
- Não bloqueia a resposta HTTP
- Falhas na notificação não impedem a transferência

## Padrões de Projeto Utilizados

- **Repository Pattern**: Abstração de acesso a dados
- **Service Layer**: Encapsulamento de regras de negócio
- **Dependency Injection**: Através do container PSR-11
- **Data Transfer Object**: Entities representam dados
- **Strategy Pattern**: Enum com comportamento (UserType)

## PSRs Implementadas

- **PSR-4**: Autoloading
- **PSR-7**: HTTP Message Interface
- **PSR-11**: Container Interface
- **PSR-12**: Coding Style
- **PSR-15**: HTTP Handlers

## Segurança

- Validação de entrada rigorosa
- Prepared statements (proteção contra SQL Injection)
- Senhas hasheadas com bcrypt
- Transações de banco para consistência
- Verificação de tipo de usuário antes de transferir
