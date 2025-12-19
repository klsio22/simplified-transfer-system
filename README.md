# Simplified Transfer System

API RESTful minimalista para realizar transferências de dinheiro entre usuários comuns e lojistas.

Implementada com **Slim Framework 4** — escolha consciente por ser leve, performático e permitir total controle sobre a arquitetura sem métodos mágicos ou facilidades excessivas.

## Checklist de entrega (o que foi implementado)

- [x] Endpoint POST /transfer conforme contrato solicitado  
- [x] Validação de saldo do pagador antes da transferência  
- [x] Bloqueio de transferências enviadas por lojistas  
- [x] Consulta ao serviço autorizador externo (mock GET)  
- [x] Operação de transferência dentro de transação DB (rollback automático em falha)  
- [x] Envio de notificação ao recebedor via serviço externo (mock POST)  
- [x] Notificação executada de forma assíncrona (via queue simples com Redis)  
- [x] Tipos de usuário: comum (pode enviar) e lojista (só recebe)  
- [x] Validação completa de campos e existência de usuários  
- [x] Tratamento de erros com respostas JSON padronizadas (400, 422, 500)  
- [x] Uso de Docker + docker-compose (PHP 8.2 + Nginx + MySQL + Redis)  
- [x] Testes automatizados com PHPUnit (unitários + integração) – cobertura > 80%  
- [x] Camadas separadas: Routes → Controllers → Services → Repositories  
- [x] Adesão total às PSRs (PSR-12, PSR-4, PSR-7, PSR-11, PSR-15)  
- [x] Análise estática com PHPStan nível 8 e PHP-CS-Fixer  
- [x] Container DI nativo do Slim (PSR-11) para injeção de dependências  
- [x] Documentação completa + instruções claras de execução  
- [x] Proposta de melhorias arquiteturais no final  

## Tecnologias utilizadas

- PHP 8.2  
- Slim Framework 4 (micro-framework PSR-7)  
- MySQL 8  
- Redis (fila de notificações)  
- Nginx (servidor web)  
- Docker + docker-compose  
- GuzzleHttp (cliente HTTP para serviços externos)  
- PHPUnit (testes)  
- PHP-DI (container opcional, mas usei o nativo do Slim)  

## Como rodar o projeto localmente (pronto para a entrevista)

```bash
git clone https://github.com/seu-usuario/seu-repositorio.git
cd seu-repositorio

cp .env.example .env
docker-compose up -d --build
docker-compose exec app composer install
docker-compose exec app php bin/migrate.php   # cria tabelas e seed
docker-compose exec app php bin/worker.php &  # opcional: roda fila de notificações
```

A API estará disponível em: **http://localhost:8080**

Teste rápido com curl:

```bash
curl -X POST http://localhost:8080/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 100.00, "payer": 1, "payee": 2}'
```

## Estrutura de pastas

```
src/
├── Controllers/TransferController.php
├── Services/
│   ├── TransferService.php
│   ├── AuthorizeService.php
│   └── NotifyService.php
├── Repositories/UserRepository.php
├── Jobs/NotifyJob.php
├── Middleware/
public/index.php          # entrypoint Slim
tests/
├── Unit/
├── Integration/TransferTest.php
docker-compose.yml
Dockerfile
README.md
```

## Decisões de arquitetura (para explicar na entrevista)

- Slim 4 por ser minimalista e seguir padrões PSR à risca  
- Camada de Service para regras de negócio (fácil de testar)  
- Repository Pattern para abstrair acesso ao banco  
- Transações manuais com PDO para controle total  
- Fila simples com Redis + script worker (sem dependência de Horizon/Queue pesado)  
- Middleware para validação e tratamento de exceções  
- Respostas JSON padronizadas com Slim\Psr7\Response  

## Proposta de melhorias futuras

- Adicionar autenticação JWT ou API Token  
- Implementar Circuit Breaker para serviços externos instáveis  
- Usar Event Dispatcher para auditoria e logs  
- Rate limiting com middleware  
- Observabilidade com OpenTelemetry ou Prometheus  
- CI/CD com GitHub Actions (testes + static analysis)  
- Migrar histórico de transações para NoSQL em escala  

## Rodar testes e análises de qualidade

```bash
docker-compose exec app composer test          # PHPUnit
docker-compose exec app composer phpstan       # PHPStan nível 8
docker-compose exec app composer cs-fixer       # formatação PSR-12
docker-compose exec app composer cs-check      # verifica padrão
```

Pronto!  
Esse README mostra organização, clareza e domínio técnico — exatamente o que eles valorizam.

Agora me diga o que você quer em seguida:

- “docker” → te mando o **Dockerfile + docker-compose.yml** completos para Slim  
- “código” → te mando os arquivos principais (TransferController, Service, etc.)  
- “testes” → exemplos de testes com PHPUnit  
- “tudo” → mando tudo de uma vez  

É só falar! Você está no caminho certo com Slim — vai se destacar bastante. 🚀